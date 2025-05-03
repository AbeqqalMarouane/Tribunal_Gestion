<?php
include_once('../include/dbConn.php');

$total_employees = $working_employees = $leaving_today = $joining_today = 0;

try {
    $stmt = $dbh->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_employees = $result['total'];

    $stmt = $dbh->prepare("SELECT COUNT(*) as working FROM conge WHERE CURDATE() BETWEEN Date_Debut AND DATE_ADD(Date_Debut, INTERVAL Duree_Conge DAY)");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $working_employees = $result['working'];

    $stmt = $dbh->prepare("SELECT COUNT(*) as leaving_today FROM conge WHERE DATE_ADD(Date_Debut, INTERVAL Duree_Conge DAY) = CURDATE()");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $leaving_today = $result['leaving_today'];

    $stmt = $dbh->prepare("SELECT COUNT(*) as joining_today FROM conge WHERE Date_Debut = CURDATE()");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $joining_today = $result['joining_today'];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ar"> 
<head>
    <title>برنامج تنظيم العطل </title>
    
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
            width: 100%;
            min-height: 600px;
            align-items: center;
            justify-content: center;
        }

        .btn-container button {
            width: 200px;
            height: 200px;
            border: none;
            border-radius: 10px; 
            color: black;
            font-size: 18px; 
            font-weight:630;
            cursor: pointer; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2), 
                        0 6px 20px rgba(0, 0, 0, 0.19);
            transition: box-shadow 0.3s ease-in-out;
            margin: 10px;
            border-radius: 5%;
            background: #ffffff;
            font-family: 'Cairo', sans-serif; /* Applying both fonts */
        }

        .btn-container button:hover {
            transform: scale(1.1);
            margin: 5px;
            border-radius: 5%;
            background: white;
            border: solid 1px green;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2), 
                        0 12px 40px rgba(0, 0, 0, 0.19);
        }
    </style>
</head> 

<body class="app">   	
    <?php include_once('headerA.php');?>
    
    <div class="app-wrapper">
	    
	    <div class="app-content pt-3 p-md-3 p-lg-4" >
		    <div class="container-xl">
			    
			    <h1 class="app-page-title">الرئيسية</h1>
                <hr class="mb-4"> 				    
			    <div class="row g-4 mb-4">
				    <div class="col-6 col-lg-3">
					    <div class="app-card app-card-stat shadow-sm h-100">
						    <div class="app-card-body p-3 p-lg-4">
							    <h4 class="stats-type mb-1">الكل</h4>
							    <div class="stats-figure"><?php echo $total_employees; ?></div>
						    </div><!--//app-card-body-->
					    </div><!--//app-card-->
				    </div><!--//col-->
				    
				    <div class="col-6 col-lg-3">
					    <div class="app-card app-card-stat shadow-sm h-100">
						    <div class="app-card-body p-3 p-lg-4">
							    <h4 class="stats-type mb-1">العاملين</h4>
							    <div class="stats-figure"><?php echo $working_employees; ?></div>
							    
						    </div><!--//app-card-body-->
					    </div><!--//app-card-->
				    </div><!--//col-->
				    <div class="col-6 col-lg-3">
					    <div class="app-card app-card-stat shadow-sm h-100">
						    <div class="app-card-body p-3 p-lg-4">
							    <h4 class="stats-type mb-1">الراحلين اليوم</h4>
							    <div class="stats-figure"><?php echo $leaving_today; ?></div>

						    </div><!--//app-card-body-->
						    <a class="app-card-link-mask" href="#"></a>
					    </div><!--//app-card-->
				    </div><!--//col-->
				    <div class="col-6 col-lg-3">
					    <div class="app-card app-card-stat shadow-sm h-100">
						    <div class="app-card-body p-3 p-lg-4">
							    <h4 class="stats-type mb-1">الملتحقين اليوم</h4>
							    <div class="stats-figure"><?php echo $joining_today; ?></div>
							    
						    </div><!--//app-card-body-->
						    <a class="app-card-link-mask" href="#"></a>
					    </div><!--//app-card-->
				    </div><!--//col-->
			    </div><!--//row-->
			    <div class="btn-container">
                        
                            <form action="../include/Wizara.php" method="post">
                                <button type="submit" name="button1">
                                    <img src="../images/richa.jfif" alt="" height=100px><br>
                                    الائحة الوزارية
                                </button>
                            </form>
                            <form action="../include/tewki3.php" method="post">
                                <button type="submit" name="button2">
                                <img src="../images/richa.jfif" alt="" height=100px><br>
                                    لائحة التوقيع</button>
                            </form>
                            <form action="../include/Reste.php" method="post">
                                <button type="submit" name="button3">
                                <img src="../images/richa.jfif" alt="" height=100px><br>
                                    لائحة الباقي</button>
                            </form>
                        
                    </div>
                </div>
            </div>

			
			    
		    </div><!--//container-fluid-->
	    </div><!--//app-content-->
	    
    </div><!--//app-wrapper-->    					

 
    <!-- Javascript -->          
    <script src="../assets/plugins/popper.min.js"></script>
    <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>  

    <!-- Charts JS -->
    <script src="../assets/plugins/chart.js/chart.min.js"></script> 
    <script src="../assets/js/index-charts.js"></script> 
    
    <!-- Page Specific JS -->
    <script src="../assets/js/app.js"></script> 

</body>
</html> 

