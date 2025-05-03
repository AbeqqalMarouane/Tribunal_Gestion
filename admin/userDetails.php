<?php
session_start();
include_once('../include/dbConn.php');

$total_employees = $working_employees = $leaving_today = $joining_today = 0;

// Check if ID_user is provided in the URL
if (isset($_GET['ID_user'])) {
    $id_user = htmlspecialchars($_GET['ID_user'], ENT_QUOTES, 'UTF-8'); // Sanitize input

    try {
        // Fetch upcoming leave details
        $sql = "
            SELECT *, DATEDIFF(Date_Debut, CURDATE()) AS days_remaining
            FROM conge
            WHERE ID_user = :ID_user
            ORDER BY ABS(DATEDIFF(Date_Debut, CURDATE()))

        ";

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':ID_user', $id_user, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch user details
        $sql = "
            SELECT * 
            FROM users
            WHERE ID_user = :ID_user
        ";

        $stmt2 = $dbh->prepare($sql);
        $stmt2->bindParam(':ID_user', $id_user, PDO::PARAM_INT);
        $stmt2->execute();

        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);

        // Initialize variables for display
        $days_remaining = "-";
        $formatted_date = "-";
        $numero = "-";

        if ($row2) {
            $full_name = htmlspecialchars($row2['Nom'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($row2['Prenom'], ENT_QUOTES, 'UTF-8');
        } else {
            $full_name = "المستخدم غير معروف";
        }

        if ($row) {
            $numero = htmlspecialchars($row['ID_Conge'], ENT_QUOTES, 'UTF-8');

            $date_debut = new DateTime($row['Date_Debut']);
            $day = $date_debut->format('d');
            $month_number = $date_debut->format('n');

            $arabic_months = [
                1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'ماي', 6 => 'يونيو',
                7 => 'يوليوز', 8 => 'غشت', 9 => 'شتنبر', 10 => 'أكتوبر', 11 => 'نونبر', 12 => 'دجنبر'
            ];

            $formatted_date = $day . ' ' . $arabic_months[$month_number];
            $days_remaining = htmlspecialchars($row2['Rest_Temp'], ENT_QUOTES, 'UTF-8');
        } else {
            echo "لم يتم العثور على إجازة قادمة.";
        }
    } catch (PDOException $e) {
        echo "خطأ في قاعدة البيانات: " . $e->getMessage();
        exit;
    }
} else {
    echo "لم يتم تقديم معرف المستخدم.";
    exit;
}

function GetContrats($dbh, $id_user, $offset, $limit) {
    try {
        $sql = "SELECT c.*, 
                       u2.Nom, 
                       u2.Prenom,
                       CONCAT(u2.Nom, ' ', u2.Prenom) as NomPrenom,
                       c2.Type_Contrat AS Type_Conge,
                       CASE 
                           WHEN CURDATE() BETWEEN c.Date_Debut AND c.Date_Retour THEN 'قيد التنفيد'
                           WHEN CURDATE() < c.Date_Debut THEN 'قبل التنفيد'
                           ELSE 'اكتمل التنفيد'
                       END as status
                FROM conge c 
                LEFT JOIN users u2 ON c.ID_Vice = u2.ID_user
                LEFT JOIN contrat c2 ON c.ID_Contrat = c2.ID_Contrat
                WHERE c.ID_user = :ID_user 
                LIMIT :offset, :limit";
        
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':ID_user', $id_user, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "خطأ في قاعدة البيانات: " . $e->getMessage();
        return [];
    }
}

function GetTotalContrats($dbh, $id_user) {
    try {
        $sql = "SELECT COUNT(*) as total FROM conge WHERE ID_user = :ID_user";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':ID_user', $id_user, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        echo "خطأ في قاعدة البيانات: " . $e->getMessage();
        return 0;
    }
}

$limit = 16;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_contracts = GetTotalContrats($dbh, $id_user); // Corrected $user_id to $id_user
$total_pages = ceil($total_contracts / $limit);

$contracts = GetContrats($dbh, $id_user, $offset, $limit);

$license_types = [];
$rasmiyaContrats = []; // Store RASMIYA contracts
try {
    $sql = "SELECT ID_Contrat, Type_Contrat, Rasmiya FROM contrat WHERE Supp=0";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $license_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($license_types as $license) {
        if ($license['Rasmiya'] == 1) {
            $rasmiyaContrats[] = $license['ID_Contrat'];
        }
    }
} catch (PDOException $e) {
    error_log("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <title>برنامج تنظيم العطل</title>
    <!-- Meta -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../images/M6.png">
    <script defer src="../assets/plugins/fontawesome/js/all.min.js"></script>
    <link id="theme-style" rel="stylesheet" href="../assets/css/portal.css">
    <link rel="stylesheet" href="../assets/css/bg.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap');
        @font-face {
            font-family: 'Samir-Khouaja';
            src: url('font/Samir.Khouaja.Maghribi.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        body {
            font-family: 'Cairo', sans-serif;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2),
                        0 6px 20px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease-in-out;
            width: 500px;
        }
        .card-block {
            margin: 35px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
        }
        .user-card2 .risk-rate span {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 6px dashed #d6d6d6;
            border-top-color: #0ce11a;
            border-bottom-color: transparent;
            padding: 45px;
            display: block;
            position: relative;
        }
        *, ::after, ::before {
            box-sizing: border-box;
        }
        .text-center {
            text-align: center;
        }
        .text-muted {
            width: 4em;
        }
        .container-xl {
            display: flex;
            gap: 100px;
        }
        .welcome {
            width: 600px;
            direction: rtl;
        }
    </style>
</head>
<body class="app">
    <?php include_once('headerA.php'); ?>
    <div class="app-wrapper">
        <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="row justify-content-between">
                <div class="col-4">
                </div>
                <div class="col-4">
                <h2 style="direction: rtl;">معطيات اجازات الموضف:</h2>
                </div>
                </div>
            <hr>
            <br>
            <div class="container-xl">
                <div class="col-xl-3 col-md-12">
                    <div class="card user-card2">
                        <div class="card-block text-center">
                            <div class="risk-rate">
                                <h6 class="m-b-15">الاجازة</h6>
                                <span>
                                    <h2><b><?php echo htmlspecialchars($days_remaining, ENT_QUOTES, 'UTF-8'); ?></b></h2>
                                </span>
                                <h6 class="m-b-10 m-t-10">يوم متبقي</h6>
                            </div>
                            <div class="row justify-content-center m-t-10 b-t-default m-1-0 m-r-0">
                                <a href="#!" class="text-c-yellow b-b-warning">معلومات حول الاجازة</a>
                                <span>-----</span>
                                <div class="col">
                                    <h6 style="color: lightgray;">الرقم</h6>
                                    <h6><?php echo htmlspecialchars($numero, ENT_QUOTES, 'UTF-8'); ?></h6>
                                </div>
                                <div class="col">
                                    <h6 style="color: lightgray;">بتاريخ</h6>
                                    <h6><?php echo htmlspecialchars($formatted_date, ENT_QUOTES, 'UTF-8'); ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-12">
                    <div class="welcome">
                        <h2>معلومات السيد(ة) <?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></h2><br>
                        <h3>نتمنى لك يوم سعيد</h3>
                    </div>
                </div>
            </div>
            <br>
            <br>
            <div class="tab-content" id="orders-table-tab-content">
                <div class="tab-pane fade show active" id="orders-all" role="tabpanel" aria-labelledby="orders-all-tab">
                    <div class="app-card app-card-orders-table shadow-sm mb-5">
                        <div class="app-card-body">
                            <div class="table-responsive">
                                <table class="table app-table-hover mb-0 text-left">
                                    <thead>
                                        <tr>
                                            <th class="cell">رقم الرخصة</th>
                                            <th class="cell">نوع الرخصة</th>
                                            <th class="cell">مدة العطلة</th>
                                            <th class="cell">تاريخ الإيقاف</th>
                                            <th class="cell">تاريخ الاستئناف</th>
                                            <th class="cell">النائب</th>
                                            <th class="cell">الحالة</th>
                                            <th class="cell"></th>
                                            <th class="cell"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($contracts as $contract): ?>
                                        <tr>
                                            <td class="cell" data-label="Nbr_Conge"><?= htmlspecialchars($contract['ID_Conge'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="cell" data-label="Type_Conge"><?= htmlspecialchars($contract['Type_Conge'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="cell" data-label="Duree_Conge"><?= htmlspecialchars($contract['Duree_Conge'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="cell" data-label="Date_Debut"><?= htmlspecialchars($contract['Date_Debut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="cell" data-label="Date_Retour"><?= htmlspecialchars($contract['Date_Retour'], ENT_QUOTES, 'UTF-8'); ?></td>

                                            <td class="cell" data-label="NomPrenom">
                                                <?= htmlspecialchars($contract['NomPrenom'] ?? '---', ENT_QUOTES, 'UTF-8'); ?>
                                            </td>

                                            
                                            <td class="cell">
                                                    <?php 
                                                    if ($contract['status'] == 'قيد التنفيد') {
                                                        echo '<span class="badge bg-warning">' . $contract['status'] . '</span>';
                                                    } elseif ($contract['status'] == 'قبل التنفيد') {
                                                        echo '<span class="badge bg-primary">' . $contract['status'] . '</span>';
                                                    } else {
                                                        echo '<span class="badge bg-success">' . $contract['status'] . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                            <td class="cell">

                                            <td class="cell"> 
                                            <a class="btn-sm app-btn-secondary" href="../include/GenPDF.php?ID_Conge=<?= urlencode($contract['ID_Conge']); ?>" onclick="disableButton(this)">الإيقاف</a>                                                </td>
                                                <td class="cell">
                                                        <a class="btn-sm app-btn-secondary" href="../include/GenPDF.php?ID_Conge=<?= urlencode($contract['ID_Conge']); ?>" onclick="disableButton(this)">الإستئناف</a>
                                                </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>                                                                                                                                                                                                                           
                                </table>
                            </div>
                        </div>
                    </div>
                    <nav class="app-pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page == 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?= $page - 1; ?>" tabindex="-1" aria-disabled="true">السابق</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page == $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?= $page + 1; ?>">التالي</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>



    <!-- Javascript -->
    <script src="../assets/plugins/popper.min.js"></script>
    <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
    <!-- Charts JS -->
    <script src="../assets/plugins/chart.js/chart.min.js"></script>
    <script src="../assets/js/index-charts.js"></script>
    <!-- Page Specific JS -->
    <script src="../assets/js/app.js"></script>


<script src="../assets/plugins/popper.min.js"></script>
    <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
    <script src="../assets/js/app.js"></script> 
</body>
</html>
