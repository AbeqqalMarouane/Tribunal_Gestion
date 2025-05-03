<?php
ob_start();
include_once('../include/dbConn.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



function GetContrats($dbh, $offset, $limit, $status = null, $searchTerm = null){
    try {
        $sql = "SELECT 
            u.Nbr_Financier,
            u.CIN,
            u.Prenom,
            u.Nom,
            u.Metier,
            u.Departement,
            u.Rest_Temp,
            CASE 
                WHEN u.supp = 0 THEN 'المستمرين'
                ELSE 'المتوقفين'
            END as status
        FROM users u";
        
        $conditions = [];
        if ($status !== null) {
            $conditions[] = "u.supp = :status";
        }
        if ($searchTerm !== null) {
            $conditions[] = "u.Nbr_Financier LIKE :searchTerm";
        }
        
        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " LIMIT :offset, :limit";
        
        $stmt = $dbh->prepare($sql);
        if ($status !== null) {
            $stmt->bindValue(':status', $status, PDO::PARAM_INT);
        }
        if ($searchTerm !== null) {
            $stmt->bindValue(':searchTerm', "%$searchTerm%", PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $th) {
        echo "Error: " . $th->getMessage();
        return [];
    }
}

function GetTotalContrats($dbh, $status = null, $searchTerm = null){
    try {
        $sql = "SELECT COUNT(*) as total FROM users";
        
        $conditions = [];
        if ($status !== null) {
            $conditions[] = "supp = :status";
        }
        if ($searchTerm !== null) {
            $conditions[] = "Nbr_Financier LIKE :searchTerm";
        }
        
        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $dbh->prepare($sql);
        if ($status !== null) {
            $stmt->bindValue(':status', $status, PDO::PARAM_INT);
        }
        if ($searchTerm !== null) {
            $stmt->bindValue(':searchTerm', "$searchTerm%", PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (\Throwable $th) {
        echo "Error: " . $th->getMessage();
        return 0;
    }
}

$limit = 16;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
$searchTerm = isset($_GET['searchorders']) ? $_GET['searchorders'] : null;
$offset = ($page - 1) * $limit;

$status = null;
if ($tab == 'paid') {
    $status = 0;
} elseif ($tab == 'pending') {
    $status = 1;
}

$total_contracts = GetTotalContrats($dbh, $status, $searchTerm);
$total_pages = ceil($total_contracts / $limit);

$contracts = GetContrats($dbh, $offset, $limit, $status, $searchTerm);

?>

<?php


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prenom = $_POST['Prenom'];
    $nom = $_POST['Nom'];
    $cin = $_POST['CIN'];
    $nbrFinancier = $_POST['Nbr_Financier'];
    $metier = $_POST['Metier'];
    $departement = $_POST['Departement'];
    $restTemp = $_POST['Rest_Temp'];
    $nom_complet_fr = $_POST['nom_complet_fr'];
    $hashedPassword = password_hash($cin, PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $sex = $_POST['sex'];
    $date_naissance = $_POST['date_naissance'];
    $adresse = $_POST['adresse'];
    $numero_telephone = $_POST['numero_telephone'];
    

    if ($prenom && $nom && $cin && $nbrFinancier && $metier && $departement && $restTemp && $hashedPassword && $role && $sex && $date_naissance && $adresse && $numero_telephone && $nom_complet_fr) {
        try {
            // Insert user data
            $sql = "INSERT INTO users (Prenom, Nom, CIN, Nbr_Financier, Metier, Departement, Rest_Temp, Supp,password_hash, role, sex, date_naissance, adresse, numero_telephone, nom_complet_fr) 
                    VALUES (:prenom, :nom, :cin, :nbrFinancier, :metier, :departement, :restTemp, 0, :hashedPassword, :role, :sex, :date_naissance, :adresse, :numero_telephone, :nom_complet_fr)";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
            $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $stmt->bindParam(':cin', $cin, PDO::PARAM_STR);
            $stmt->bindParam(':nbrFinancier', $nbrFinancier, PDO::PARAM_INT);
            $stmt->bindParam(':metier', $metier, PDO::PARAM_STR);
            $stmt->bindParam(':departement', $departement, PDO::PARAM_STR);
            $stmt->bindParam(':restTemp', $restTemp, PDO::PARAM_INT);
            $stmt->bindParam(':hashedPassword', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
            $stmt->bindParam(':date_naissance', $date_naissance, PDO::PARAM_STR);
            $stmt->bindParam(':adresse', $adresse, PDO::PARAM_STR);
            $stmt->bindParam(':numero_telephone', $numero_telephone, PDO::PARAM_STR);
            $stmt->bindParam(':nom_complet_fr', $nom_complet_fr, PDO::PARAM_STR);
            $stmt->execute();

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = "حدث خطأ ما.";
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            $error = "حدث خطأ ما.";
        }
    } else {
        $error = "يرجى ملء جميع الحقول.";
    }
}
?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <title>المستعملين</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../images/M6.png">
    <script defer src="../assets/plugins/fontawesome/js/all.min.js"></script>
    <link id="theme-style" rel="stylesheet" href="../assets/css/portal.css">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        .app-content {
            position: relative;
            overflow: hidden;
        }
        .app-content::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../images/coveeerZellige.png') repeat;
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
                        <h1 class="app-page-title mb-0">قائمة الموظفين</h1>
                    </div>
                    <div class="col-auto">
                        <div class="page-utilities">
                            <div class="row g-2 justify-content-start justify-content-md-end align-items-center">

                                <div class="col-auto">
                                    <form class="table-search-form row gx-1 align-items-center">
                                        <div class="col-auto">
                                            <input type="text" id="search-orders" name="searchorders" class="form-control search-orders" placeholder="Search">
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn app-btn-secondary">Search</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="col-auto">
                                    <a class="btn app-btn-secondary" href="../include/CSV.php?filter=<?= isset($_GET['filter']) ? $_GET['filter'] : 'all' ?>">
                                        <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-download me-1" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                            <path fill-rule="evenodd" d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                        </svg>
                                        CSV تحميل الجدول 
                                    </a>
                                </div>

                                <div class="col-auto">
                                    <button class="btn btn-primary " type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasBoth" aria-controls="offcanvasBoth">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                                        </svg>  
                                         إضافة موضف
                                    </button>
                                
                                </div>

                                <!-- Enable Scrolling & Backdrop Offcanvas -->
                                <div class="col-auto">
                                    <div class="mt-3">
                                        
                                        <div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="offcanvasBoth" aria-labelledby="offcanvasBothLabel">
                                            <div class="offcanvas-header">
                                                <h5 id="offcanvasBothLabel" class="offcanvas-title">معلومات الموضف</h5>
                                                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                                            </div>
                                            <div class="offcanvas-body my-auto mx-0 flex-grow-0">
                                                <form action="" method="POST">
                                                    <div class="mb-3">
                                                        <label for="prenom" class="form-label">الاسم الشخصي</label>
                                                        <input type="text" class="form-control" id="prenom" name="Prenom" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="nom" class="form-label">الإسم العائلي</label>
                                                        <input type="text" class="form-control" id="nom" name="Nom" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="cin" class="form-label">رقم البطاقة الوطنية</label>
                                                        <input type="text" class="form-control" id="cin" name="CIN" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="nbrFinancier" class="form-label">رقم التأجير</label>
                                                        <input type="number" class="form-control" id="nbrFinancier" name="Nbr_Financier" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="metier" class="form-label">المهنة</label>
                                                        <input type="text" class="form-control" id="metier" name="Metier" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="departement" class="form-label">القسم</label>
                                                        <input type="text" class="form-control" id="departement" name="Departement" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="restTemp" class="form-label">الوقت المتبقي</label>
                                                        <input type="number" class="form-control" id="restTemp" name="Rest_Temp" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="role" class="form-label">الدور</label>
                                                        <select class="form-select" id="role" name="role" required>
                                                            <option value="user">مستخدم</option>
                                                            <option value="admin">مشرف</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="sex" class="form-label">الجنس</label>
                                                        <select class="form-select" id="sex" name="sex" required>
                                                            <option value="male">ذكر</option>
                                                            <option value="female">أنثى</option>
                                                        
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="date_naissance" class="form-label">تاريخ الإزدياد</label>
                                                        <input type="date" class="form-control" id="date_naissance" name="date_naissance" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="adresse" class="form-label">العنوان</label>
                                                        <input type="text" class="form-control" id="adresse" name="adresse" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="numero_telephone" class="form-label">رقم الهاتف</label>
                                                        <input type="text" class="form-control" id="numero_telephone" name="numero_telephone" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="nom_complet_fr" class="form-label">الاسم الكامل بالفرنسية</label>
                                                        <input type="text" class="form-control" id="nom_complet_fr" name="nom_complet_fr" required>
                                                    </div>
                                                    <div class="text-center">
                                                        <button type="submit" class="btn btn-primary mb-2 d-grid w-100">إضافة</button>
                                                        <button type="button" class="btn btn-outline-secondary d-grid w-100" data-bs-dismiss="offcanvas">إلغاء</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                
                            </div>
                        </div>
                    </div>
                </div>

                <nav id="orders-table-tab" class="orders-table-tab app-nav-tabs nav shadow-sm flex-column flex-sm-row mb-4">
                    <a class="flex-sm-fill text-sm-center nav-link <?= $tab == 'all' ? 'active' : ''; ?>" href="?tab=all&page=1">الكل</a>
                    <a class="flex-sm-fill text-sm-center nav-link <?= $tab == 'paid' ? 'active' : ''; ?>" href="?tab=paid&page=1">المستمرين</a>
                    <a class="flex-sm-fill text-sm-center nav-link <?= $tab == 'pending' ? 'active' : ''; ?>" href="?tab=pending&page=1">المتوقفين</a>
                </nav>

                <div class="tab-content" id="orders-table-tab-content">
                    <div class="tab-pane fade <?= $tab == 'all' ? 'show active' : ''; ?>" id="orders-all" role="tabpanel" aria-labelledby="orders-all-tab">
                        <div class="app-card app-card-orders-table shadow-sm mb-5">
                            <div class="app-card-body">
                                <div class="table-responsive">
                                    <table class="table app-table-hover mb-0 text-left">
                                        <thead>
                                            <tr>
                                                <th class="cell">الرقم المالي</th>
                                                <th class="cell">CIN</th>
                                                <th class="cell">الاسم الشخصي</th>
                                                <th class="cell">الاسم العائلي</th>
                                                <th class="cell">الاطار</th>
                                                <th class="cell">المصلحة</th>
                                                <th class="cell">عدد الأيام المتبقية</th>
                                                <th class="cell">الحالة</th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($contracts as $contract): ?>
                                            <tr id="row-<?= htmlspecialchars($contract['Nbr_Financier'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <td class="cell" data-label="Nbr_Financier"><?= htmlspecialchars($contract['Nbr_Financier'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="CIN"><?= htmlspecialchars($contract['CIN'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Prenom"><?= htmlspecialchars($contract['Prenom'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Nom"><?= htmlspecialchars($contract['Nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Metier"><?= htmlspecialchars($contract['Metier'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Departement"><?= htmlspecialchars($contract['Departement'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Reste_Conge"><?= htmlspecialchars($contract['Rest_Temp'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell">
                                                <?php 
                                                    if ($contract['status'] == 'المستمرين') {
                                                        echo '<span class="badge bg-success">المستمرين</span>';
                                                    } elseif ($contract['status'] == 'المتوقفين') {
                                                        echo '<span class="badge bg-danger">المتوقفين</span>';
                                                    } 
                                                    ?>
                                                </td>
                                                <td class="cell"><button class="btn app-btn-primary modify_btn" title="تعديل">تعديل</button></td>
                                                <td class="cell">
                                                <?php if ($contract['status'] == 'المستمرين')
                                                    echo "<a class='btn btn-danger custom-hover-red' href='../include/handleAction.php?action=deleteUser&ID_Contrat=" . urlencode($contract['Nbr_Financier']) . "' onclick='return confirm(\"هل انت متاكد من انك تريد الحذف ؟\")'>حذف</a>";
                                                    if ($contract['status'] == 'المتوقفين') 
                                                    echo "<a class='btn btn-warning custom-hover-red' href='../include/handleAction.php?action=RestoreUser&ID_Contrat=" . urlencode($contract['Nbr_Financier']) . "' onclick='return confirm(\"هل انت متاكد من انك تريد الاستعادة ؟\")'>استعادة</a>";
                                                ?>
                                                </td>

                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        
                    </div>

                    <div class="tab-pane fade <?= $tab == 'paid' ? 'show active' : ''; ?>" id="orders-paid" role="tabpanel" aria-labelledby="orders-paid-tab">
                        <div class="app-card app-card-orders-table mb-5">
                            <div class="app-card-body">
                                <div class="table-responsive">
                                    <table class="table mb-0 text-left">
                                        <thead>
                                            <tr>
                                                <th class="cell">الرقم المالي</th>
                                                <th class="cell">CIN</th>
                                                <th class="cell">الاسم الشخصي</th>
                                                <th class="cell">الاسم العائلي</th>
                                                <th class="cell">الاطار</th>
                                                <th class="cell">المصلحة</th>
                                                <th class="cell">عدد الأيام المتبقية</th>
                                                <th class="cell">الحالة</th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($contracts as $contract): ?>
                                            <tr id="row-<?= htmlspecialchars($contract['Nbr_Financier'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <td class="cell" data-label="Nbr_Financier"><?= htmlspecialchars($contract['Nbr_Financier'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="CIN"><?= htmlspecialchars($contract['CIN'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Prenom"><?= htmlspecialchars($contract['Prenom'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Nom"><?= htmlspecialchars($contract['Nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Metier"><?= htmlspecialchars($contract['Metier'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Departement"><?= htmlspecialchars($contract['Departement'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Reste_Conge"><?= htmlspecialchars($contract['Rest_Temp'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell">
                                                <?php 
                                                    if ($contract['status'] == 'المستمرين') {
                                                        echo '<span class="badge bg-success">المستمرين</span>';
                                                    } elseif ($contract['status'] == 'المتوقفين') {
                                                        echo '<span class="badge bg-danger">المتوقفين</span>';
                                                    } 
                                                    ?>
                                                </td>
                                                <td class="cell"><button class="btn app-btn-primary modify_btn" title="تعديل">تعديل</button></td>
                                                <td class="cell">
                                                <?php if ($contract['status'] == 'المستمرين')
                                                    echo "<a class='btn btn-danger custom-hover-red' href='../include/handleAction.php?action=deleteUser&ID_Contrat=" . urlencode($contract['Nbr_Financier']) . "' onclick='return confirm(\"هل انت متاكد من انك تريد الحذف ؟\")'>حذف</a>";
                                                    if ($contract['status'] == 'المتوقفين') 
                                                    echo "<a class='btn btn-warning custom-hover-red' href='../include/handleAction.php?action=RestoreUser&ID_Contrat=" . urlencode($contract['Nbr_Financier']) . "' onclick='return confirm(\"هل انت متاكد من انك تريد الاستعادة ؟\")'>استعادة</a>";
                                                ?>
                                                </td>

                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        
                    </div>

                    <div class="tab-pane fade <?= $tab == 'pending' ? 'show active' : ''; ?>" id="orders-pending" role="tabpanel" aria-labelledby="orders-pending-tab">
                        <div class="app-card app-card-orders-table mb-5">
                            <div class="app-card-body">
                                <div class="table-responsive">
                                    <table class="table mb-0 text-left">
                                        <thead>
                                            <tr>
                                                <th class="cell">الرقم المالي</th>
                                                <th class="cell">CIN</th>
                                                <th class="cell">الاسم الشخصي</th>
                                                <th class="cell">الاسم العائلي</th>
                                                <th class="cell">الاطار</th>
                                                <th class="cell">المصلحة</th>
                                                <th class="cell">عدد الأيام المتبقية</th>
                                                <th class="cell">الحالة</th>
                                                <th class="cell"></th>
                                                <th class="cell"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($contracts as $contract): ?>
                                            <tr id="row-<?= htmlspecialchars($contract['Nbr_Financier'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <td class="cell" data-label="Nbr_Financier"><?= htmlspecialchars($contract['Nbr_Financier'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="CIN"><?= htmlspecialchars($contract['CIN'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Prenom"><?= htmlspecialchars($contract['Prenom'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Nom"><?= htmlspecialchars($contract['Nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Metier"><?= htmlspecialchars($contract['Metier'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Departement"><?= htmlspecialchars($contract['Departement'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell" data-label="Reste_Conge"><?= htmlspecialchars($contract['Rest_Temp'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="cell">
                                                <?php 
                                                    if ($contract['status'] == 'المستمرين') {
                                                        echo '<span class="badge bg-success">المستمرين</span>';
                                                    } elseif ($contract['status'] == 'المتوقفين') {
                                                        echo '<span class="badge bg-danger">المتوقفين</span>';
                                                    } 
                                                    ?>
                                                </td>
                                                <td class="cell"><button class="btn app-btn-primary modify_btn" title="تعديل">تعديل</button></td>
                                                <td class="cell">
                                                <?php if ($contract['status'] == 'المستمرين')
                                                    echo "<a class='btn btn-danger custom-hover-red' href='../include/handleAction.php?action=deleteUser&ID_Contrat=" . urlencode($contract['Nbr_Financier']) . "' onclick='return confirm(\"هل انت متاكد من انك تريد الحذف ؟\")'>حذف</a>";
                                                    if ($contract['status'] == 'المتوقفين') 
                                                    echo "<a class='btn btn-warning custom-hover-red' href='../include/handleAction.php?action=RestoreUser&ID_Contrat=" . urlencode($contract['Nbr_Financier']) . "' onclick='return confirm(\"هل انت متاكد من انك تريد الاستعادة ؟\")'>استعادة</a>";
                                                ?>
                                                </td>

                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        
                    </div>
                    <nav class="app-pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tab=<?= $tab; ?>&page=<?= $page - 1; ?>" tabindex="-1" aria-disabled="true">Previous</a>
                        </li>
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active' : ''; ?>"><a class="page-link" href="?tab=<?= $tab; ?>&page=<?= $i; ?>"><?= $i; ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tab=<?= $tab; ?>&page=<?= $page + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h1 class="modal-title fs-5" id="staticBackdropLabel">تحديث بيانات المستخدم</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <form id="updateUserForm" action="./update_user.php" method="POST">
                <input type="hidden" id="id_user" name="id_user">
                <div class="mb-3">
                    <label for="Nbr_Financier" class="form-label">رقم مالي</label>
                    <input type="text" class="form-control" id="Nbr_Financier" name="Nbr_Financier" required>
                </div>
                <div class="mb-3">
                    <label for="CIN" class="form-label">رقم بطاقة الهوية الوطنية</label>
                    <input type="text" class="form-control" id="CIN" name="CIN" required>
                </div>
                <div class="mb-3">
                    <label for="Prenom" class="form-label">الاسم الأول</label>
                    <input type="text" class="form-control" id="Prenom" name="Prenom" required>
                </div>
                <div class="mb-3">
                    <label for="Nom" class="form-label">الاسم العائلي</label>
                    <input type="text" class="form-control" id="Nom" name="Nom" required>
                </div>
                <div class="mb-3">
                    <label for="Metier" class="form-label">المهنة</label>
                    <input type="text" class="form-control" id="Metier" name="Metier" required>
                </div>
                <div class="mb-3">
                    <label for="Departement" class="form-label">القسم</label>
                    <input type="text" class="form-control" id="Departement" name="Departement" required>
                </div>
                <div class="mb-3">
                    <label for="Date_Naissance" class="form-label">تاريخ الميلاد</label>
                    <input type="date" class="form-control" id="Date_Naissance" name="Date_Naissance" required>
                </div>
                <div class="mb-3">
                    <label for="Gender" class="form-label">الجنس</label>
                    <select class="form-control" id="Gender" name="Gender" required>
                        <option value="">اختر الجنس</option>
                        <option value="male">ذكر</option>
                        <option value="female">أنثى</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="Adresse" class="form-label">العنوان</label>
                    <input type="text" class="form-control" id="Adresse" name="Adresse" required>
                </div>
                <div class="mb-3">
                    <label for="Phone" class="form-label">رقم الهاتف</label>
                    <input type="text" class="form-control" id="Phone" name="Phone" required>
                </div>
                <div class="mb-3">
                    <label for="Nom_Complet" class="form-label">الاسم الكامل (بالفرنسية)</label>
                    <input type="text" class="form-control" id="Nom_Complet" name="Nom_Complet" required>
                </div>
                <div class="mb-3">
                    <label for="Reste_Conge" class="form-label">باقي العطلة</label>
                    <input type="text" class="form-control" id="Reste_Conge" name="Reste_Conge" required>
                </div>
                <button type="submit" class="btn btn-primary" onclick='return confirm("هل أنت متأكد؟");'>حفظ التغييرات</button>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
        </div>
    </div>
</div>

</div>

    <script>
document.addEventListener('DOMContentLoaded', (event) => {
    const modifyButtons = document.querySelectorAll('.modify_btn');

    modifyButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            const id_user = row.id.split('-')[1];
            // console.log(id_user);
            const Nbr_Financier = row.querySelector('[data-label="Nbr_Financier"]').innerText;
            const CIN = row.querySelector('[data-label="CIN"]').innerText;
            const Prenom = row.querySelector('[data-label="Prenom"]').innerText;
            const Nom = row.querySelector('[data-label="Nom"]').innerText;
            const Metier = row.querySelector('[data-label="Metier"]').innerText;
            const Departement = row.querySelector('[data-label="Departement"]').innerText;
            const Reste_Conge = row.querySelector('[data-label="Reste_Conge"]').innerText;

            document.getElementById('id_user').value = id_user;
            document.getElementById('Nbr_Financier').value = Nbr_Financier;
            document.getElementById('CIN').value = CIN;
            document.getElementById('Prenom').value = Prenom;
            document.getElementById('Nom').value = Nom;
            document.getElementById('Metier').value = Metier;
            document.getElementById('Departement').value = Departement;
            document.getElementById('Reste_Conge').value = Reste_Conge;

            const modal = new bootstrap.Modal(document.getElementById('staticBackdrop'));
            modal.show();
        });
    });
});


</script>
</body>
</html>

