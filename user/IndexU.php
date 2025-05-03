<?php
session_start();
include_once('../include/dbConn.php');

$total_employees = $working_employees = $leaving_today = $joining_today = 0;

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id === null) {
    header('Location: ../auth/login.php');
    exit();
}

try {
    $sql = "
        SELECT *, DATEDIFF(Date_Debut, CURDATE()) AS days_remaining
        FROM conge
        WHERE ID_user = :ID_user AND Date_Debut >= CURDATE() AND supp = 0
        ORDER BY ABS(DATEDIFF(Date_Debut, CURDATE()))
        LIMIT 1
    ";

    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':ID_user', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $numero = $row['ID_Conge'];

        $date_debut = new DateTime($row['Date_Debut']);
        $day = $date_debut->format('d');
        $month_number = $date_debut->format('n');

        $arabic_months = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'ماي',
            6 => 'يونيو',
            7 => 'يوليوز',
            8 => 'غشت',
            9 => 'شتنبر',
            10 => 'أكتوبر',
            11 => 'نونبر',
            12 => 'دجنبر'
        ];

        $formatted_date = $day . ' ' . $arabic_months[$month_number];
        $days_remaining = $row['days_remaining'];
    } else {
        $days_remaining = "-";
    $formatted_date = "-";
    $numero = "-";
    }

    $sql = "
        SELECT * 
        FROM users
        WHERE ID_user = :ID_user
    ";

    $stmt2 = $dbh->prepare($sql);
    $stmt2->bindParam(':ID_user', $user_id, PDO::PARAM_INT);
    $stmt2->execute();


    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);

    

    if ($row2) {
        $full_name = $row2['Nom'] . ' ' . $row2['Prenom'];
    } else {
        $full_name = "Unknown User";
    }

    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php

function GetContrats($dbh, $user_id, $offset, $limit) {
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
                WHERE c.ID_user = :ID_user AND c.Supp = 0
                LIMIT :offset, :limit";
        
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':ID_user', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

function GetTotalContrats($dbh, $user_id) {
    try {
        $sql = "SELECT COUNT(*) as total FROM conge WHERE ID_user = :ID_user";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':ID_user', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (\Throwable $th) {
        echo "Error: " . $th->getMessage();
        return 0;
    }
}

$limit = 16;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_contracts = GetTotalContrats($dbh, $user_id);
$total_pages = ceil($total_contracts / $limit);

$contracts = GetContrats($dbh, $user_id, $offset, $limit);

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
    error_log("Database error: " . $e->getMessage());
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
    <?php include_once('headerU.php'); ?>
    <div class="app-wrapper">
        <div class="app-content pt-3 p-md-3 p-lg-4">
        <div class="row justify-content-between">
                <div class="col-4">
                <button class="btn btn-warning Add_btn" data-bs-toggle="modal" data-bs-target="#staticBackdrop3">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-plus-fill" viewBox="0 0 16 16">
                    <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M8.5 7v1.5H10a.5.5 0 0 1 0 1H8.5V11a.5.5 0 0 1-1 0V9.5H6a.5.5 0 0 1 0-1h1.5V7a.5.5 0 0 1 1 0"/>
                    </svg>
                </button>
                </div>
                <div class="col-4">
                <h1 style="direction: rtl;">الرئيسية:</h1>
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
                        <h2>مرحبا بالسيد(ة) <?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></h2><br>
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
                                            <th class="cell">الرد</th>
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
                                            <?php 
                                                    if ($contract['Contrat_Etat'] == 'ENCOURS') {
                                                        echo '<span class="badge bg-warning">' . "قيد المعالجة" . '</span>';
                                                    } elseif ($contract['Contrat_Etat'] == 'NON') {
                                                        echo '<span class="badge bg-danger">' . "رفض" . '</span>';
                                                    } else {
                                                        echo '<span class="badge bg-success">' ."موافقة". '</span>';
                                                    }
                                                    ?>
                                            </td>
                                            <td class="cell">
                                                <a class='btn btn-danger custom-hover-red' href='../include/handleAction.php?action=DeleteUserContrat&ID_Contrat=<?= urlencode($contract['ID_Conge']) ?>' onclick='return confirm("هل انت متاكد من انك تريد الحذف ؟")'>حذف</a>
                                            </td>
                                            <td class="cell"><button class="btn btn-warning modify_btn" title="تعديل">تعديل</button></td>
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
                                <a class="page-link" href="?page=<?= $page - 1; ?>" tabindex="-1" aria-disabled="true">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page == $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?= $page + 1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- modal -->

    <div class="modal fade" id="staticBackdrop2" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">تعديل العطلة</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateUserForm" action="../include/UpdateUserContrat.php" method="POST">
                    <input type="hidden" id="Nbr_Conge" name="Nbr_Conge">
                    <div class="mb-3">
                        <label for="Duree_Conge" class="form-label">مدة العطلة</label>
                        <input type="text" class="form-control" id="Duree_Conge" name="Duree_Conge" required>
                    </div>
                    <div class="mb-3">
                        <label for="Date_Debut" class="form-label">تاريخ العطلة</label>
                        <input type="date" class="form-control" id="Date_Debut" name="Date_Debut" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" onclick = ' return confirm("هل انت متاكد؟");'>حفظ </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">اغلاق</button>
            </div>
        </div>
    </div>
</div>

    <!-- modal -->

    <div class="modal fade" id="staticBackdrop3" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">اضافة عطلة</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateUserForm" action="../include/AddUserCotrat.php" method="POST">
                <input type="hidden" id="id_user" name="id_user" value="<?php echo $user_id; ?>">
                    <div class="nice-form-group">
                        <label>نوع الرخصة</label>
                        <select name="license_type" class="form-select" id="license_type" required>
                            <?php foreach ($license_types as $license): ?>
                                <option value="<?php echo htmlspecialchars($license['ID_Contrat']); ?>">
                                    <?php echo htmlspecialchars($license['Type_Contrat']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select><br>
                    </div>

                    <div class="mb-3">
                        <label for="Duree_Conge" class="form-label">مدة العطلة</label>
                        <input type="text" class="form-control" id="Duree_Conge" name="Duree_Conge" required>
                    </div>
                    <div class="mb-3">
                        <label for="Date_Debut" class="form-label">تاريخ العطلة</label>
                        <input type="date" class="form-control" id="Date_Debut" name="Date_Debut" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" onclick = ' return confirm("هل انت متاكد؟");'>حفظ </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">اغلاق</button>
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

    <script>
document.addEventListener('DOMContentLoaded', (event) => {
    const modifyButtons = document.querySelectorAll('.modify_btn');

    modifyButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            const Nbr_Conge = row.querySelector('[data-label="Nbr_Conge"]').innerText;
            const Duree_Conge = row.querySelector('[data-label="Duree_Conge"]').innerText;
            const Date_Debut = row.querySelector('[data-label="Date_Debut"]').innerText;

            document.getElementById('Nbr_Conge').value = Nbr_Conge;
            document.getElementById('Duree_Conge').value = Duree_Conge;
            document.getElementById('Date_Debut').value = Date_Debut;

            const modal = new bootstrap.Modal(document.getElementById('staticBackdrop2'));
            modal.show();
        });
    });
});
</script>
<script src="../assets/plugins/popper.min.js"></script>
    <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
    <script src="../assets/js/app.js"></script> 
</body>
</html>
