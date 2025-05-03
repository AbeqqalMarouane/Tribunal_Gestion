<?php
session_start();
include_once('../include/dbConn.php');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id === null) {
    // Redirect to login if user is not logged in
    header('Location: ../auth/login.php');
    exit();
}

try {
    // Fetch user data
    $sql = "SELECT * FROM users WHERE ID_user = :user_id";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "حدث خطأ ما.";
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $error = "حدث خطأ ما.";
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
    <style>
        
    </style>
</head> 

<body class="app">    
    <?php include_once('headerU.php');?>
    <div class="app-wrapper">
        <div class="app-content pt-3 p-md-3 p-lg-4">
            <div class="container-xl">
                <h1 class="app-page-title">حسابي</h1>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php else: ?>
                <hr class="mb-4"> 
                <div class="row gy-4">
                    <div class="col-12 col-lg-6">
                        <div class="app-card app-card-account shadow-sm d-flex flex-column align-items-start">
                            <div class="app-card-header p-3 border-bottom-0">
                                <div class="row align-items-center gx-3">
                                    <div class="col-auto">
                                        <div class="app-icon-holder">
                                            <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-person" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M10 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm6 5c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                                            </svg>
                                        </div><!--//icon-holder-->
                                    </div><!--//col-->
                                    <div class="col-auto">
                                        <h4 class="app-card-title">الملف الشخصي</h4>
                                    </div><!--//col-->
                                </div><!--//row-->
                            </div><!--//app-card-header-->
                            <div class="app-card-body px-4 w-100">
                                <div class="item border-bottom py-3">
                                    <div class="row justify-content-between align-items-center">
                                        <div class="col-auto">
                                            <div class="item-label mb-2"><strong>الصورة</strong></div>
                                            <div class="item-data"><img class="profile-image" src="../images/M6.png" alt=""></div>
                                        </div><!--//col-->
                                    </div><!--//row-->
                                </div><!--//item-->
                                <div class="item border-bottom py-3">
                                    <div class="row justify-content-between align-items-center">
                                        <div class="col-auto">
                                            <div class="item-label"><strong>الاسم الشخصي</strong></div>
                                            <div class="item-data"><?php echo htmlspecialchars($user['Prenom']); ?></div>
                                        </div><!--//col-->
                                    </div><!--//row-->
                                </div><!--//item-->
                                <div class="item border-bottom py-3">
                                    <div class="row justify-content-between align-items-center">
                                        <div class="col-auto">
                                            <div class="item-label"><strong>الإسم العائلي</strong></div>
                                            <div class="item-data"><?php echo htmlspecialchars($user['Nom']); ?></div>
                                        </div><!--//col-->
                                    </div><!--//row-->
                                </div><!--//item-->
                                <div class="item border-bottom py-3">
                                    <div class="row justify-content-between align-items-center">
                                        <div class="col-auto">
                                            <div class="item-label"><strong>رقم البطاقة الوطنية</strong></div>
                                            <div class="item-data"><?php echo htmlspecialchars($user['CIN']); ?></div>
                                        </div><!--//col-->
                                    </div><!--//row-->
                                </div><!--//item-->
                                <div class="item border-bottom py-3">
                                    <div class="row justify-content-between align-items-center">
                                        <div class="col-auto">
                                            <div class="item-label"><strong>رقم التأجير</strong></div>
                                            <div class="item-data"><?php echo htmlspecialchars($user['Nbr_Financier']); ?></div>
                                        </div><!--//col-->
                                    </div><!--//row-->
                                </div><!--//item-->
                            </div><!--//app-card-body-->
                        </div><!--//app-card-->
                    </div><!--//col-->
                    <div class="col-12 col-lg-6">
                        <div class="app-card app-card-account shadow-sm d-flex flex-column align-items-start">
                            <div class="app-card-header p-3 border-bottom-0">
                                <div class="row align-items-center gx-3">
                                    <div class="col-auto">
                                        <div class="app-icon-holder">
                                            <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-sliders" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M11.5 2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM9.05 3a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0V3h9.05zM4.5 7a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zM2.05 8a2.5 2.5 0 0 1 4.9 0H16v1H6.95a2.5 2.5 0 0 1-4.9 0H0V8h2.05zm9.45 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm-2.45 1a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0v-1h9.05z"/>
                                            </svg>
                                        </div><!--//icon-holder-->
                                    </div><!--//col-->
                                    <div class="col-auto">
                                        <h4 class="app-card-title">التفضيلات</h4>
                                    </div><!--//col-->
                                </div><!--//row-->
                            </div><!--//app-card-header-->
                            <div class="app-card-body px-4 w-100">
                                <div class="item border-bottom py-3">
                                    <div class="row justify-content-between align-items-center">
                                        <div class="col-auto">
                                            <div class="item-label"><strong>اللغة</strong></div>
                                            <div class="item-data"><?php echo htmlspecialchars($user['Departement']); ?></div>
                                        </div><!--//col-->
                                    </div><!--//row-->
                                </div><!--//item-->
                                <div class="item border-bottom py-3">
                                    <div class="row justify-content-between align-items-center">
                                        <div class="col-auto">
                                            <div class="item-label"><strong>العنوان</strong></div>
                                            <div class="item-data"><?php echo htmlspecialchars($user['adresse']); ?></div>
                                        </div><!--//col-->
                                    </div><!--//row-->
                                </div><!--//item-->
                                <div class="item border-bottom py-3">
                                    <div class="row justify-content-between align-items-center">
                                        <div class="col-auto">
                                            <div class="item-label"><strong>تاريخ الإزدياد</strong></div>
                                            <div class="item-data"><?php echo htmlspecialchars($user['date_naissance']); ?></div>
                                        </div><!--//col-->
                                    </div><!--//row-->
                                </div><!--//item-->
                                <div class="item border-bottom py-3">
                                    <div class="row justify-content-between align-items-center">
                                        <div class="col-auto">
                                            <div class="item-label"><strong>رقم الهاتف</strong></div>
                                            <div class="item-data"><?php echo htmlspecialchars($user['numero_telephone']); ?></div>
                                        </div><!--//col-->
                                    </div><!--//row-->
                                </div><!--//item-->
                            </div><!--//app-card-body-->
                        </div><!--//app-card-->
                    </div><!--//col-->
                </div><!--//row-->
                <?php endif; ?>
            </div><!--//container-fluid-->
        </div><!--//app-content-->    
    </div><!--//app-wrapper-->     
    <!-- Javascript -->          
    <script src="../assets/plugins/popper.min.js"></script>
    <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>  
    <!-- Page Specific JS -->
    <script src="../assets/js/app.js"></script> 
</body>
</html>
