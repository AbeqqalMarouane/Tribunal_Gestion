<?php
session_start();
include_once('../include/dbConn.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['Nbr_Financier'];
    $password = $_POST['password'];

    if ($email && $password) {
        try {
            $sql = "SELECT ID_user, password_hash, role FROM users WHERE Nbr_Financier = :Nbr_Financier";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':Nbr_Financier', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['ID_user'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['isloged'] = 'true';

                    $sql = "INSERT INTO login_attempts (user_id, success) VALUES (:user_id, 1)";
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindParam(':user_id', $user['ID_user'], PDO::PARAM_INT);
                    $stmt->execute();

                    if ($user['role'] == 'admin') {
                        header('Location: ../admin/indexA.php?id=' . $user['ID_user']);
                    } else {
                        header('Location: ../user/indexU.php?id=' . $user['ID_user']);
                    }
                    exit;
                } else {
                    $error = ".كلمة المرور خاطئة";
                }
            } else {
                $error = ".المعرف خاطئ";
            }

            // Log the unsuccessful attempt
            $sql = "INSERT INTO login_attempts (user_id, success) VALUES ((SELECT ID_user FROM users WHERE Nbr_Financier = :Nbr_Financier), 0)";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':Nbr_Financier', $email, PDO::PARAM_STR);
            $stmt->execute();

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = ".حدث خطأ ما";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en"> 
<head>
    <title>برنامج تنظيم العطل</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../images/M6.png"> 
    <script defer src="../assets/plugins/fontawesome/js/all.min.js"></script>
    <link id="theme-style" rel="stylesheet" href="../assets/css/portal.css">
</head> 
<body class="app app-login p-0">    	
    <div class="row g-0 app-auth-wrapper">
        <div class="col-12 col-md-7 col-lg-6 auth-main-col text-center p-5">
            <div class="d-flex flex-column align-content-end">
                <div class="app-auth-body mx-auto">	
                    <div class="app-auth-branding mb-4"><a class="app-logo" href="#"><img class="logo-icon me-2" src="../images/M6.svg" alt="logo"></a></div>
                    <h2 class="auth-heading text-center mb-5">رئاسة النيابة العامة</h2>
                    <div class="auth-form-container text-start">
                        <form class="auth-form login-form" action="login.php" method="POST">         
                            <div class="email mb-3">
                                <label class="sr-only" for="signin-email">Email</label>
                                <input id="Nbr_Financier" name="Nbr_Financier" type="text" class="form-control signin-email" placeholder="المعرف الخاص" required="required">
                            </div>
                            <div class="password mb-3">
                                <label class="sr-only" for="signin-password">Password</label>
                                <input id="signin-password" name="password" type="password" class="form-control signin-password" placeholder="كلمة المرور" required="required">
                                <div class="extra mt-3 row justify-content-between">
                                    <div class="col-6">
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn app-btn-primary w-100 theme-btn mx-auto">تسجيل الدخول</button>
                            </div>
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger mt-3" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>	
                </div>	
            </div>   
        </div>
        <div class="col-12 col-md-5 col-lg-6 h-100 auth-background-col">
            <div class="auth-background-holder"></div>
            <div class="auth-background-mask"></div>
        </div>
    </div>
</body>
</html>
