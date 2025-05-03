<?php
include_once('../include/dbConn.php');

function GetContrats($dbh, $offset, $limit, $search = '') {
    try {
        // Modify the SQL to include a WHERE clause if a search term is provided
        $sql = "SELECT c.ID_Conge, u.CIN, u.Nbr_Financier,
                       CONCAT(u.Prenom, ' ', u.Nom) AS name,
                       c.Date_Conge AS date,
                       c.Date_Debut AS start_date,
                       c.Date_Retour AS end_date,
                       c.Duree_Conge AS duree,
                       cn.Type_Contrat AS kind,
                       CASE 
                           WHEN CURDATE() = DATE(c.Date_Retour) THEN 'اكتمل التنفيد'
                           WHEN CURDATE() BETWEEN DATE(c.Date_Debut) AND DATE(c.Date_Retour) THEN 'قيد التنفيد'
                           WHEN CURDATE() < DATE(c.Date_Debut) THEN 'قبل التنفيد'
                           ELSE 'اكتمل التنفيد'
                       END AS status
                FROM users u 
                JOIN conge c ON c.ID_user = u.ID_user
                JOIN contrat cn ON cn.ID_Contrat = c.ID_Contrat";
        
        // Add search condition if a search query is provided
        if (!empty($search)) {
            $sql .= " WHERE u.CIN LIKE :search OR CONCAT(u.Prenom, ' ', u.Nom) LIKE :search";
        }

        $sql .= " LIMIT :offset, :limit";

        $stmt = $dbh->prepare($sql);

        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
        }

        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

function GetTotalContrats($dbh, $search = '') {
    try {
        $sql = "SELECT COUNT(*) AS total FROM conge c JOIN users u ON c.ID_user = u.ID_user";
        
        // Add search condition if a search query is provided
        if (!empty($search)) {
            $sql .= " WHERE u.CIN LIKE :search OR CONCAT(u.Prenom, ' ', u.Nom) LIKE :search";
        }

        $stmt = $dbh->prepare($sql);

        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return 0;
    }
}

$limit = 16; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get search query from the request
$searchQuery = isset($_GET['searchorders']) ? trim($_GET['searchorders']) : '';

$total_contracts = GetTotalContrats($dbh, $searchQuery);
$total_pages = ceil($total_contracts / $limit);

$contracts = GetContrats($dbh, $offset, $limit, $searchQuery);
?>


<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $holiday_date = $_POST['holiday_date'];
    $description = $_POST['description'];
    $durree = $_POST['durree'];

    try {
        $sql = "INSERT INTO holidays (holiday_date, description, durree) VALUES (:holiday_date, :description, :durree)";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':holiday_date', $holiday_date, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':durree', $durree, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['success'] = "Holiday added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: form.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="ar"> 
<head>
    <title>برنامج تنظيم العطل</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../images/M6.png">
    <script defer src="../assets/plugins/fontawesome/js/all.min.js"></script>
    <link id="theme-style" rel="stylesheet" href="../assets/css/portal.css">
    <style>
        .app-wrapper::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../images/zelij.jpg') repeat;
            background-size: cover;
            opacity: 0.2;
            z-index: -1; 
        }
    </style>
</head> 

<body class="app">    
    <?php include_once('headerA.php');?>

    <div class="app-wrapper">
        <div class="app-content pt-3 p-md-3 p-lg-4">
            <div class="container-xl">
                <div class="row g-3 mb-4 align-items-center justify-content-between">
                    <div class="col-auto">
                        <h1 class="app-page-title mb-0">الطلبات</h1>
                    </div>
                    <hr class="mb-4"> 
                    <div class="col-auto">
                        <div class="page-utilities">
                            <div class="row g-2 justify-content-start justify-content-md-end align-items-center">
                                <div class="col-auto">
                                <form class="table-search-form row gx-1 align-items-center" method="get" action="">
                                    <div class="col-auto">
                                        <input type="text" id="search-orders" name="searchorders" class="form-control search-orders" placeholder="اكتب اسم المستفيد او رقم البطاقة الوطنية" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn app-btn-secondary">بحث</button>
                                    </div>
                                </form>

                                </div>
                                <div class="col-auto">
                                    <select class="form-select w-auto">
                                        <option selected value="option-1">الكل</option>
                                        <option value="option-2">هذا الأسبوع</option>
                                        <option value="option-3">هذا الشهر</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    
                                </div>
                                <div class="col-auto">
                                    <a class="btn app-btn-secondary" href="form.php">إضافة طلب</a>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCenter">إضافة عطلة</button>
                                </div>
                                
                            </div>
                            <div class="modal fade" id="modalCenter" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalCenterTitle">إضافة عطلة جديدة</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="addHolidayForm" action="" method="post">
                                                <div class="mb-3">
                                                    <label for="holiday_date" class="form-label">تاريخ العطلة</label>
                                                    <input type="date" id="holiday_date" name="holiday_date" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="description" class="form-label">وصف العطلة</label>
                                                    <input type="text" id="description" name="description" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="durree" class="form-label">مدة العطلة (بالأيام)</label>
                                                    <input type="number" id="durree" name="durree" class="form-control" required>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                    <button type="submit" class="btn btn-primary">إضافة</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <nav id="orders-table-tab" class="orders-table-tab app-nav-tabs nav shadow-sm flex-column flex-sm-row mb-4">
                    <a class="flex-sm-fill text-sm-center nav-link active" id="orders-all-tab" data-bs-toggle="tab" href="#orders-all" role="tab" aria-controls="orders-all" aria-selected="true">الكل</a>
                    <a class="flex-sm-fill text-sm-center nav-link" id="orders-paid-tab" data-bs-toggle="tab" href="#orders-paid" role="tab" aria-controls="orders-paid" aria-selected="false">اكتمل التنفيد</a>
                    <a class="flex-sm-fill text-sm-center nav-link" id="orders-pending-tab" data-bs-toggle="tab" href="#orders-pending" role="tab" aria-controls="orders-pending" aria-selected="false">قيد التنفيد</a>
                    <a class="flex-sm-fill text-sm-center nav-link" id="orders-cancelled-tab" data-bs-toggle="tab" href="#orders-cancelled" role="tab" aria-controls="orders-cancelled" aria-selected="false">قبل التنفيد</a>
                </nav>

                <div class="tab-content" id="orders-table-tab-content">
                    <div class="tab-pane fade show active" id="orders-all" role="tabpanel" aria-labelledby="orders-all-tab">
                        <div class="app-card app-card-orders-table shadow-sm mb-5">
                            <div class="app-card-body">
                                <div class="table-responsive">
                                    <table class="table app-table-hover mb-0 text-left">
                                        <thead>
                                            <tr>
                                                <th class="cell">CIN</th>
                                                <th class="cell">نوع الرخصة</th>
                                                <th class="cell">المستفيد</th>
                                                <th class="cell">التوقيت</th>
                                                <th class="cell">الحالة</th>
                                                <th class="cell">مدة العطلة</th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($contracts as $contract): ?>
                                            <tr class="<?= htmlspecialchars($contract['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <td class="cell"><?= htmlspecialchars($contract['CIN'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell"><?= htmlspecialchars($contract['kind'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell"><?= htmlspecialchars($contract['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell"><?= htmlspecialchars($contract['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell">
                                                    <?php 
                                                    if ($contract['status'] == 'قيد التنفيد') {
                                                        echo '<span class="badge bg-warning">' . htmlspecialchars($contract['status'], ENT_QUOTES, 'UTF-8') . '</span>';
                                                    } elseif ($contract['status'] == 'قبل التنفيد') {
                                                        echo '<span class="badge bg-primary">' . htmlspecialchars($contract['status'], ENT_QUOTES, 'UTF-8') . '</span>';
                                                    } else {
                                                        echo '<span class="badge bg-success">' . htmlspecialchars($contract['status'], ENT_QUOTES, 'UTF-8') . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="cell"><?= htmlspecialchars($contract['duree'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell">
                                                   
                                                    <a class="btn-sm" href="../include/EditForm.php?ID_Conge=<?= urlencode($contract['ID_Conge']); ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M21 12a1 1 0 0 0-1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h6a1 1 0 0 0 0-2H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1m-15 .76V17a1 1 0 0 0 1 1h4.24a1 1 0 0 0 .71-.29l6.92-6.93L21.71 8a1 1 0 0 0 0-1.42l-4.24-4.29a1 1 0 0 0-1.42 0l-2.82 2.83l-6.94 6.93a1 1 0 0 0-.29.71m10.76-8.35l2.83 2.83l-1.42 1.42l-2.83-2.83ZM8 13.17l5.93-5.93l2.83 2.83L10.83 16H8Z"/></svg>
                                                    </a>
                                                </td>
                                                <td class="cell">
                                                    <?php if ($contract['status'] == 'قيد التنفيد' && date('Y-m-d') > $contract['start_date']): ?>
                                                        <a class="btn-sm app-btn-secondary" href="../include/handleAction.php?action=تمديد&ID_Conge=<?= urlencode($contract['ID_Conge']); ?>" onclick="disableButton(this)">تمديد</a>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="cell">
                                                    <?php if ($contract['status'] == 'قيد التنفيد' && date('Y-m-d') > $contract['start_date']): ?>
                                                        <a class="btn-sm app-btn-secondary" href="../include/Stinaf.php?ID_Conge=<?= urlencode($contract['ID_Conge']); ?>" onclick="disableButton(this)">استئناف</a>

                                                    <?php endif; ?>
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

                    <div class="tab-pane fade" id="orders-paid" role="tabpanel" aria-labelledby="orders-paid-tab">
                        <div class="app-card app-card-orders-table shadow-sm mb-5">
                            <div class="app-card-body">
                                <div class="table-responsive">
                                    <table class="table app-table-hover mb-0 text-left">
                                        <thead>
                                            <tr>
                                                <th class="cell">CIN</th>
                                                <th class="cell">نوع الرخصة</th>
                                                <th class="cell">المستفيد</th>
                                                <th class="cell">التوقيت</th>
                                                <th class="cell">الحالة</th>
                                                <th class="cell">مدة العطلة</th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($contracts as $contract): ?>
                                                <?php if($contract['status'] == 'اكتمل التنفيد'): ?>
                                                <tr>
                                                    <td class="cell"><?= htmlspecialchars($contract['CIN'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['kind'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><span class="badge bg-success"><?= htmlspecialchars($contract['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['duree'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell">
                                                
                                                        <a class="btn-sm" href="../include/EditForm.php?ID_Conge=<?= urlencode($contract['ID_Conge']); ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M21 12a1 1 0 0 0-1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h6a1 1 0 0 0 0-2H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1m-15 .76V17a1 1 0 0 0 1 1h4.24a1 1 0 0 0 .71-.29l6.92-6.93L21.71 8a1 1 0 0 0 0-1.42l-4.24-4.29a1 1 0 0 0-1.42 0l-2.82 2.83l-6.94 6.93a1 1 0 0 0-.29.71m10.76-8.35l2.83 2.83l-1.42 1.42l-2.83-2.83ZM8 13.17l5.93-5.93l2.83 2.83L10.83 16H8Z"/></svg>
                                                        </a>
                                                    </td>
                                                    <td class="cell">
                                                        <?php if ($contract['status'] == 'قيد التنفيد' && date('Y-m-d') > $contract['start_date']): ?>
                                                            <a class="btn-sm app-btn-secondary" href="../include/handleAction.php?action=تمديد&ID_Conge=<?= urlencode($contract['ID_Conge']); ?>" onclick="disableButton(this)">تمديد</a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="cell">
                                                        <?php if ($contract['status'] == 'قيد التنفيد' && date('Y-m-d') > $contract['start_date']): ?>
                                                            <a class="btn-sm app-btn-secondary" href="../include/Stinaf.php?ID_Conge=<?= urlencode($contract['ID_Conge']); ?>" onclick="disableButton(this)">استئناف</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="orders-pending" role="tabpanel" aria-labelledby="orders-pending-tab">
                        <div class="app-card app-card-orders-table shadow-sm mb-5">
                            <div class="app-card-body">
                                <div class="table-responsive">
                                    <table class="table app-table-hover mb-0 text-left">
                                        <thead>
                                            <tr>
                                                <th class="cell">CIN</th>
                                                <th class="cell">نوع الرخصة</th>
                                                <th class="cell">المستفيد</th>
                                                <th class="cell">التوقيت</th>
                                                <th class="cell">الحالة</th>
                                                <th class="cell">مدة العطلة</th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($contracts as $contract): ?>
                                                <?php if($contract['status'] == 'قيد التنفيد'): ?>
                                                <tr>
                                                    <td class="cell"><?= htmlspecialchars($contract['CIN'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['kind'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><span class="badge bg-warning"><?= htmlspecialchars($contract['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['duree'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell">
                                                        
                                                        <a class="btn-sm" href="../include/EditForm.php?ID_Conge=<?= urlencode($contract['ID_Conge']); ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M21 12a1 1 0 0 0-1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h6a1 1 0 0 0 0-2H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1m-15 .76V17a1 1 0 0 0 1 1h4.24a1 1 0 0 0 .71-.29l6.92-6.93L21.71 8a1 1 0 0 0 0-1.42l-4.24-4.29a1 1 0 0 0-1.42 0l-2.82 2.83l-6.94 6.93a1 1 0 0 0-.29.71m10.76-8.35l2.83 2.83l-1.42 1.42l-2.83-2.83ZM8 13.17l5.93-5.93l2.83 2.83L10.83 16H8Z"/></svg>
                                                        </a>
                                                    </td>
                                                    <td class="cell">
                                                        <?php if ($contract['status'] == 'قيد التنفيد' && date('Y-m-d') > $contract['start_date']): ?>
                                                            <a class="btn-sm app-btn-secondary" href="../include/handleAction.php?action=تمديد&ID_Conge=<?= urlencode($contract['ID_Conge']); ?>" onclick="disableButton(this)">تمديد</a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="cell">
                                                        <?php if ($contract['status'] == 'قيد التنفيد' && date('Y-m-d') > $contract['start_date']): ?>
                                                            <a class="btn-sm app-btn-secondary" href="../include/Stinaf.php?ID_Conge=<?= urlencode($contract['ID_Conge']); ?>" onclick="disableButton(this)">استئناف</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="orders-cancelled" role="tabpanel" aria-labelledby="orders-cancelled-tab">
                        <div class="app-card app-card-orders-table shadow-sm mb-5">
                            <div class="app-card-body">
                                <div class="table-responsive">
                                    <table class="table app-table-hover mb-0 text-left">
                                        <thead>
                                            <tr>
                                                <th class="cell">CIN</th>
                                                <th class="cell">نوع الرخصة</th>
                                                <th class="cell">المستفيد</th>
                                                <th class="cell">التوقيت</th>
                                                <th class="cell">الحالة</th>
                                                <th class="cell">مدة العطلة</th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($contracts as $contract): ?>
                                                <?php if($contract['status'] == 'قبل التنفيد'): ?>
                                                <tr>
                                                    <td class="cell"><?= htmlspecialchars($contract['CIN'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['kind'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell"><span class="badge bg-primary"><?= htmlspecialchars($contract['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    <td class="cell"><?= htmlspecialchars($contract['duree'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="cell">
                                                        
                                                        <a class="btn-sm" href="../EditForm.php?ID_Conge=<?= urlencode($contract['ID_Conge']); ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M21 12a1 1 0 0 0-1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h6a1 1 0 0 0 0-2H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1m-15 .76V17a1 1 0 0 0 1 1h4.24a1 1 0 0 0 .71-.29l6.92-6.93L21.71 8a1 1 0 0 0 0-1.42l-4.24-4.29a1 1 0 0 0-1.42 0l-2.82 2.83l-6.94 6.93a1 1 0 0 0-.29.71m10.76-8.35l2.83 2.83l-1.42 1.42l-2.83-2.83ZM8 13.17l5.93-5.93l2.83 2.83L10.83 16H8Z"/></svg>
                                                        </a>
                                                    </td>
                                                    <td class="cell">
                                                        <?php if ($contract['status'] == 'قيد التنفيد' && date('Y-m-d') > $contract['start_date']): ?>
                                                            <a class="btn-sm app-btn-secondary" href="../include/handleAction.php?action=تمديد&ID_Conge=<?= urlencode($contract['ID_Conge']); ?>" onclick="disableButton(this)">تمديد</a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="cell">
                                                        <?php if ($contract['status'] == 'قيد التنفيد' && date('Y-m-d') > $contract['start_date']): ?>
                                                            <a class="btn-sm app-btn-secondary" href="../include/Stinaf.php?ID_Conge=<?= urlencode($contract['ID_Conge']); ?>" onclick="disableButton(this)">استئناف</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .modal-content {
            height: 100%;
        }
        .modal-dialog {
            height: 90vh; 
            display: hide;
        }
    </style>

    <script src="../assets/plugins/popper.min.js"></script>
    <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
    <script src="../assets/js/app.js"></script> 
    <script src="../assets/js/script.js"></script> 
    <script>
    function disableButton(button) {
        button.onclick = function() { return false; }; 
        button.innerHTML = '...جاري المعالجة';
        button.classList.add('disabled');

        setTimeout(function() {
            location.reload(); 
        }, 3000); 
    }
    </script>

</body>
</html>
