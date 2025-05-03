<?php
include_once('../include/dbConn.php'); // Include database connection

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

if (empty($searchQuery)) {
    echo "Please enter a search query.";
    exit;
}

try {
    // Search in Users table
    $sqlUsers = "SELECT CONCAT(Prenom, ' ', Nom) AS name, CIN, Nbr_Financier FROM users WHERE CIN LIKE :search OR CONCAT(Prenom, ' ', Nom) LIKE :search";
    $stmtUsers = $dbh->prepare($sqlUsers);
    $searchParam = '%' . $searchQuery . '%';
    $stmtUsers->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $stmtUsers->execute();
    $usersResults = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // Search in Conge (Leave) table
    $sqlConge = "SELECT c.ID_Conge, CONCAT(u.Prenom, ' ', u.Nom) AS name, c.Date_Debut, c.Date_Retour FROM conge c JOIN users u ON c.ID_user = u.ID_user WHERE u.CIN LIKE :search OR CONCAT(u.Prenom, ' ', u.Nom) LIKE :search";
    $stmtConge = $dbh->prepare($sqlConge);
    $stmtConge->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $stmtConge->execute();
    $congeResults = $stmtConge->fetchAll(PDO::FETCH_ASSOC);

    // Search in Holidays table
    $sqlHolidays = "SELECT holiday_date, description, durree FROM holidays WHERE description LIKE :search";
    $stmtHolidays = $dbh->prepare($sqlHolidays);
    $stmtHolidays->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $stmtHolidays->execute();
    $holidaysResults = $stmtHolidays->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
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
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap');

        @font-face {
            font-family: 'Samir-Khouaja';
            src: url('font/Samir.Khouaja.Maghribi.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        .app-wrapper {
            position: relative;
            overflow: hidden;
        }

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

        body {
            font-family: 'Cairo', sans-serif;
        }

        .btn-container {
            display: flex;
            flex-direction: column; /* Use column direction */
            width: 100%;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px; /* Add some margin to separate sections */
        }

        .btn-container h2 {
            margin-top: 20px;
        }

        .table {
            width: 80%; /* Make tables more responsive */
            margin-bottom: 20px; /* Add margin for better spacing */
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden; /* Ensure content is properly clipped */
        }

        .table thead {
            background-color: #f9f9f9;
        }

        .table td, .table th {
            padding: 12px;
            text-align: center; /* Align text to the center */
        }

        .table th {
            font-weight: 700;
        }
    </style>
</head> 

<body class="app">   	
    <?php include_once('headerA.php');?>
    
    <div class="app-wrapper">
	    <div class="app-content pt-3 p-md-3 p-lg-4">
		    <div class="container-xl">
            <h1 class="app-page-title" style="text-align: right;">نتائج البحث عن: <?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?></h1>
            <hr class="mb-4"> 				    

                <!-- Results Section -->
			    <div class="btn-container">

                    <!-- Users Results -->
                    <?php if (!empty($usersResults)): ?>
                        <h2>المستخدمين</h2>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>CIN</th>
                                    <th>الرقم المالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usersResults as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($user['CIN'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($user['Nbr_Financier'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <!-- Conge (Leave) Results -->
                    <?php if (!empty($congeResults)): ?>
                        <h2>الرخص</h2>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>اسم المستفيد</th>
                                    <th>تاريخ البدء</th>
                                    <th>تاريخ العودة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($congeResults as $conge): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($conge['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($conge['Date_Debut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($conge['Date_Retour'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <!-- Holidays Results -->
                    <?php if (!empty($holidaysResults)): ?>
                        <h2>العطلات</h2>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>تاريخ العطلة</th>
                                    <th>الوصف</th>
                                    <th>مدة العطلة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($holidaysResults as $holiday): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($holiday['holiday_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($holiday['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($holiday['durree'], ENT_QUOTES, 'UTF-8'); ?> يوم</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                </div>
		    </div><!--//container-fluid-->
	    </div><!--//app-content-->
    </div><!--//app-wrapper-->    				

    <!-- Javascript -->          
    <script src="../assets/plugins/popper.min.js"></script>
    <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>  
    <script src="../assets/js/app.js"></script> 

</body>
</html> 
