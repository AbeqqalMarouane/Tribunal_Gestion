<?php
include('../include/dbConn.php');

function getNbrNotification($dbh) {
    try {
        $date_debut = date('Y-m-d');

        // Count Nbr_Financier notifications
        $sql = "SELECT COUNT(Nbr_Financier) FROM users u
                JOIN conge c ON u.ID_user = c.ID_user
                WHERE c.Date_Debut = :date_debut";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':date_debut', $date_debut, PDO::PARAM_STR);
        $stmt->execute();
        $CongeAuj = $stmt->fetchColumn();

        if ($CongeAuj === false) {
            $CongeAuj = 0;
        }

        $sql2 = "SELECT COUNT(*) FROM conge 
                 WHERE Contrat_Etat = 'ENCOURS'";
        $stmt2 = $dbh->prepare($sql2);
        $stmt2->execute();
        $EncoursToday = $stmt2->fetchColumn();

        if ($EncoursToday === false) {
            $EncoursToday = 0;
        }

        return $CongeAuj + $EncoursToday;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 0;
    }
}

try {
    // Begin transaction
    $dbh->beginTransaction();

    $date_Actuel = date('Y-m-d');

    // Fetch conge notifications
    $sql = "SELECT DISTINCT CONCAT(u.Prenom, ' ', u.Nom) as name, c.Duree_Conge as duree 
            FROM users u 
            JOIN conge c ON u.ID_user = c.ID_user  
            WHERE c.Date_Debut = :Date_Actuel";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':Date_Actuel', $date_Actuel, PDO::PARAM_STR);
    $stmt->execute();
    $congeNotifications = $stmt->fetchAll(PDO::FETCH_OBJ);

    // Fetch contract notifications
    $sql2 = "SELECT DISTINCT CONCAT(u.Prenom, ' ', u.Nom) as name,c.Date_Conge, u.Nbr_Financier as Nbr_Fin, Co.Type_Contrat as Type, c.Duree_Conge as Duree, c.Date_Debut as Date_Debut, c.ID_Conge as ID_Conge,c.Contrat_Etat as Contrat_Etat
             FROM users u
             JOIN conge c ON u.ID_user = c.ID_user
             JOIN contrat Co ON c.ID_contrat = Co.ID_Contrat
             WHERE c.Contrat_Etat = 'ENCOURS'";
    $stmt2 = $dbh->prepare($sql2);
    $stmt2->execute();
    $contratNotifications = $stmt2->fetchAll(PDO::FETCH_OBJ);

    $nbrNotification = getNbrNotification($dbh);

    // Commit transaction
    $dbh->commit();
} catch (PDOException $e) {
    // Rollback transaction on error
    $dbh->rollBack();
    error_log("Database error: " . $e->getMessage());
    $congeNotifications = [];
    $contractNotifications = [];
    $nbrNotification = 0;
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
    
    <!-- FontAwesome JS-->
    <script defer src="../assets/plugins/fontawesome/js/all.min.js"></script>
    
    <!-- App CSS -->  
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
    <?php include_once('headerA.php'); ?>
    
    <div class="app-wrapper">
        <div class="app-content pt-3 p-md-3 p-lg-4">
            <div class="container-xl">
                <div class="position-relative mb-3">
                    <div class="row g-3 justify-content-between">
                        <div class="col-auto">
                            <h1 class="app-page-title mb-0">الإشعارات</h1>
                        </div>
                        <hr class="mb-4"> 
                    </div>
                </div>
                <?php if (!empty($congeNotifications)): ?>
                    <?php foreach ($congeNotifications as $notification): ?>                
                        <div class="app-card app-card-notification shadow-sm mb-4">
                            <div class="app-card-header px-4 py-3">
                                <div class="row g-3 align-items-center">
                                    <div class="col-12 col-lg-auto text-center text-lg-start">                                
                                        <img class="profile-image" src="../images/M6.png" alt="">
                                    </div>
                                    <div class="col-12 col-lg-auto text-center text-lg-start">
                                        <div class="notification-type mb-2"><span class="badge bg-info">Conge</span></div>
                                        <h4 class="notification-title mb-1"><?php echo htmlspecialchars($notification->name ?? 'غير متاح'); ?></h4>
                                        <ul class="notification-meta list-inline mb-0">
                                            <li class="list-inline-item">مدة العطلة : <?php echo htmlspecialchars($notification->duree ?? 'غير متاح'); ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="app-card-body p-4">
                                <div class="notification-content">اليوم هو يوم ايقاف السيد(ة) : <?php echo htmlspecialchars($notification->name ?? 'غير متاح'); ?></div>
                            </div>
                            <div class="app-card-footer px-4 py-3">
                                <a class="action-link" href="#">View all<svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-arrow-right ms-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                                </svg></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>لا توجد إشعارات عطلة لليوم.</p>
                <?php endif; ?>
                <?php foreach ($contratNotifications as $notification): ?>                
                    <div class="app-card app-card-notification shadow-sm mb-4">
                        <div class="app-card-header px-4 py-3">
                            <div class="row g-3 align-items-center">
                                <div class="col-12 col-lg-auto text-center text-lg-start">                                
                                    <img class="profile-image" src="../images/M6.png" alt="">
                                </div>
                                <div class="col-12 col-lg-auto text-center text-lg-start">
                                    <div class="notification-type mb-2"><span class="badge bg-info">Contract</span></div>
                                    <h4 class="notification-title mb-1"><?php echo htmlspecialchars($notification->name ?? 'غير متاح'); ?></h4>
                                    <h4 class="notification-title mb-1"><?php echo htmlspecialchars($notification->ID_Conge ?? 'غير متاح'); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="app-card-body p-4">
                            <div class="notification-content">اليوم هو يوم تقديم العقد للسيد(ة) : <?php echo htmlspecialchars($notification->name ?? 'غير متاح'); ?></div>
                        </div>
                        <div class="app-card-footer px-4 py-3">
                            <a class="action-link" href="#">View all<svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-arrow-right ms-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                            </svg></a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="app-card app-card-orders-table mb-5">
                    <div class="app-card-body">
                        <div class="table-responsive">
                            <table class="table mb-0 text-left">
                                <thead>
                                    <tr>
                                        <th class="cell">رقم الطلب</th>
                                        <th class="cell">تاريخ الطلب</th>
                                        <th class="cell">رقم التأجير</th>
                                        <th class="cell">اسم الموظف</th>
                                        <th class="cell">نوع الرخصة</th>
                                        <th class="cell">المدة</th>
                                        <th class="cell">تاريخ الايقاف</th>
                                        <th class="cell">الحالة</th>
                                        <th class="cell">قبول</th>
                                        <th class="cell">رفض</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($contratNotifications)): ?>
                                        <?php foreach($contratNotifications as $contract): ?>
                                            <tr id="row-<?= htmlspecialchars($contract->ID_Conge ?? 'غير متاح'); ?>">
                                                <td class="cell" data-label="ID_Conge"><?php echo htmlspecialchars($contract->ID_Conge ?? 'غير متاح'); ?></td>
                                                <td class="cell" data-label="ID_Conge"><?php echo htmlspecialchars($contract->Date_Conge ?? 'غير متاح'); ?></td>
                                                <td class="cell" data-label="Nbr_Financier"><?= htmlspecialchars($contract->Nbr_Fin ?? 'غير متاح'); ?></td>
                                                <td class="cell" data-label="name"><?= htmlspecialchars($contract->name ?? 'غير متاح'); ?></td>
                                                <td class="cell" data-label="Type_Contrat"><?= htmlspecialchars($contract->Type ?? 'غير متاح'); ?></td>
                                                <td class="cell" data-label="Duree"><?= htmlspecialchars($contract->Duree ?? 'غير متاح'); ?></td>
                                                <td class="cell" data-label="Date_Debut"><?= htmlspecialchars($contract->Date_Debut ?? 'غير متاح'); ?></td>
                                                <td class="cell">
                                                    <span class="badge bg-danger"><?= htmlspecialchars($contract->Contrat_Etat ?? 'غير متاح'); ?></span>
                                                </td>
                                                <td class="cell">
                                                    <a class="btn btn-danger custom-hover-red" href="../include/handleAction.php?action=RefuseConge&ID_Conge=<?= urlencode($contract->ID_Conge ?? ''); ?>" onclick="return confirm('هل انت متاكد من انك تريد الرفض ؟')">رفض</a>
                                                </td>
                                                <td class="cell">
                                                    <a class="btn btn-primary" href="../include/EditForm.php?ID_Conge=<?= urlencode($contract->ID_Conge ?? ''); ?>">قبول</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="9">لا توجد إشعارات عقد لليوم.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4"><a class="btn app-btn-secondary" href="#">قراءة المزيد</a></div>
            </div>
        </div>
    </div>

    <!-- Javascript -->          
    <script src="../assets/plugins/popper.min.js"></script>
    <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>  
    <script src="../assets/js/app.js"></script> 
</body>
</html>
