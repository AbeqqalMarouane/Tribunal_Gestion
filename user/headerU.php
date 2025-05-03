<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once('../include/dbConn.php');
include_once('../include/Conge_Auj.php');

$id_user = $_SESSION['user_id']; 
try {
    $sql = "SELECT DISTINCT CONCAT(u.Prenom, ' ', u.Nom) as name, c.Duree_Conge as duree 
            FROM users u 
            JOIN conge c ON u.ID_user = c.ID_user  
            WHERE c.Date_Debut = :Date_Actuel";
    $stmt = $dbh->prepare($sql);
    $date_Actuel = date('Y-m-d');
    $stmt->bindParam(':Date_Actuel', $date_Actuel, PDO::PARAM_STR);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_OBJ);

    $nbrNotification = getNbrNotification($dbh);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $notifications = [];
    $nbrNotification = 0;
}
?>

<?php
include_once('../include/dbConn.php');
include_once('../include/Conge_Auj.php');

$ID_user = 3;

try {
    $sql = "SELECT n.*
            FROM notifications n
            JOIN conge c ON n.ID_Conge = c.ID_Conge
            WHERE n.ID_user = :ID_user 
              AND n.supp = 0
              AND CURDATE() < c.Date_Debut";
    $stmt = $dbh->prepare($sql);
    
    $stmt->bindParam(':ID_user', $ID_user, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_OBJ);

    $nbrNotification = count($notifications);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $notifications = [];
    $nbrNotification = 0;
}
?>

<header class="app-header fixed-top">     
    <div class="app-header-inner">  
        <div class="container-fluid py-2">
            <div class="app-header-content">
                <div class="row justify-content-between align-items-center">
                
                <div class="col-auto">
                    <a id="sidepanel-toggler" class="sidepanel-toggler d-inline-block d-xl-none" href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30" role="img"><title>Menu</title><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2" d="M4 7h22M4 15h22M4 23h22"></path></svg>
                    </a>
                </div><!--//col-->
                <div class="search-mobile-trigger d-sm-none col">
                    <i class="search-mobile-trigger-icon fa-solid fa-magnifying-glass"></i>
                </div><!--//col-->
                <div class="app-search-box col">
                    <form class="app-search-form">   
                        <input type="text" placeholder="Search..." name="search" class="form-control search-input">
                        <button type="submit" class="btn search-btn btn-primary" value="Search"><i class="fa-solid fa-magnifying-glass"></i></button> 
                    </form>
                </div><!--//app-search-box-->
                
                <div class="app-utilities col-auto">
                    <div class="app-utility-item app-notifications-dropdown dropdown">    
                        <a class="dropdown-toggle no-toggle-arrow" id="notifications-dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false" title="Notifications">
                            <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-bell icon" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2z"/>
                                  <path fill-rule="evenodd" d="M8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
                            </svg>
                            <span class="icon-badge"><?php echo $nbrNotification ?></span>
                        </a><!--//dropdown-toggle-->

                        <div class="dropdown-menu p-0" aria-labelledby="notifications-dropdown-toggle">
                            <div class="dropdown-menu-header p-3">
                                <h5 class="dropdown-menu-title mb-0">الإشعارات</h5>
                            </div><!--//dropdown-menu-title-->
                            <div class="dropdown-menu-content">
                                <?php if ($nbrNotification > 0): ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="item p-3">
                                            <div class="row gx-2 justify-content-between align-items-center">
                                                <div class="col-auto">
                                                    <div class="app-icon-holder">
                                                        <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-receipt" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                            <!-- SVG paths -->
                                                        </svg>
                                                    </div>
                                                </div><!--//col-->
                                                <div class="col">
                                                    <div class="info"> 
                                                        <div class="desc"><?php echo htmlspecialchars($notification->message) .' رقم: '.$notification->ID_Conge; ?></div>
                                                        <div class="meta"> بتوقيت <?php echo htmlspecialchars($notification->created_at); ?></div>
                                                    </div>
                                                </div><!--//col-->
                                            </div><!--//row-->
                                            <a class="link-mask" href="notifications.php"></a>
                                        </div><!--//item-->
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="item p-3">
                                        <div class="row gx-2 justify-content-between align-items-center">
                                            <div class="col">
                                                <div class="info">
                                                    <div class="desc"> لا إشعارات</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div><!--//dropdown-menu-content-->
                        </div><!--//dropdown-menu--> 
                    </div><!--//app-utility-item-->
                    <div class="app-utility-item app-user-dropdown dropdown">
                        <a class="dropdown-toggle" id="user-dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"><img src="../images/M6.png" alt="user profile"></a>
                        <ul class="dropdown-menu" aria-labelledby="user-dropdown-toggle">
                            <li><a class="dropdown-item" href="Account.php">حسابي</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">تسجيل الخروج</a></li>
                        </ul>
                    </div><!--//app-user-dropdown--> 
                </div><!--//app-utilities-->
            </div><!--//row-->
        </div><!--//app-header-content-->
    </div><!--//container-fluid-->
</div><!--//app-header-inner-->

<div id="app-sidepanel" class="app-sidepanel"> 
    <div id="sidepanel-drop" class="sidepanel-drop"></div>
    <div class="sidepanel-inner d-flex flex-column">
        <a href="#" id="sidepanel-close" class="sidepanel-close d-xl-none">&times;</a>
        <div class="app-branding">
            <a class="app-logo" href="index.html"><img class="logo-icon me-2" src="../images/M6.svg" alt="logo"><span class="logo-text">رئاسة النيابة العامة</span></a>
        </div><!--//app-branding-->  
        
        <nav id="app-nav-main" class="app-nav app-nav-main flex-grow-1">
            <ul class="app-menu list-unstyled accordion" id="menu-accordion">
                <li class="nav-item">
                    <a class="nav-link active" href="indexU.php">
                        <span class="nav-icon">
                            <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-house-door" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.646 1.146a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 .146.354v7a.5.5 0 0 1-.5.5H9.5a.5.5 0 0 1-.5-.5v-4H7v4a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5v-7a.5.5 0 0 1 .146-.354l6-6zM2.5 7.707V14H6v-4a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v4h3.5V7.707L8 2.207l-5.5 5.5z"/>
                                <path fill-rule="evenodd" d="M13 2.5V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z"/>
                            </svg>
                        </span>
                        <span class="nav-link-text" style="font-family:'Cairo' !important;">الرئيسية</span>
                    </a><!--//nav-link-->
                </li><!--//nav-item-->
                
                <li class="nav-item">
                    <a class="nav-link" href="Account.php">
                        <span class="nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linejoin="round" d="M4 18a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><circle cx="12" cy="7" r="3"/></g></svg>
                        </span>
                        <span class="nav-link-text">حسابي</span>
                    </a><!--//nav-link-->
                </li><!--//nav-item-->                
            </ul><!--//app-menu-->
        </nav><!--//app-nav-->
    </div><!--//sidepanel-inner-->
</div><!--//app-sidepanel-->
</header><!--//app-header-->
