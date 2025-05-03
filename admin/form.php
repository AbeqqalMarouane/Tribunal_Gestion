<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../include/dbConn.php');

function fetchHolidays($dbh) {
    $sql = "SELECT holiday_date, durree FROM holidays";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $holidays;
}

function isWeekend($date) {
    return (date('N', strtotime($date)) >= 6);
}

function isHoliday($date, $holidays) {
    foreach ($holidays as $holiday) {
        if ($date == $holiday['holiday_date']) {
            return $holiday['durree'];
        }
    }
    return false;
}

function calculateDateRetour($start_date, $duration, $holidays) {
    $date = strtotime($start_date);
    $days_added = 0;

    while ($days_added < $duration) {
        $date = strtotime('+1 day', $date);
        $formatted_date = date('Y-m-d', $date);

        if (!isWeekend($formatted_date)) {
            $holiday_duration = isHoliday($formatted_date, $holidays);
            if ($holiday_duration) {
                $date = strtotime('+' . ($holiday_duration - 1) . ' days', $date);  // Adjust for holiday duration
            } else {
                $days_added++;
            }
        }
    }

    return date('Y-m-d', $date);
}

$holidays = fetchHolidays($dbh);
error_log("Fetched Holidays: " . json_encode($holidays));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nbr_Financier = $_POST['nbr_Financier'];
    $nbr_FinancierV = $_POST['nbr_FinancierV'];
    $service = isset($_POST['service']) ? $_POST['service'] : null; // Handling missing service field
    $stop_date = $_POST['stop_date'];
    $license_duration = $_POST['license_duration'];
    $note = isset($_POST['note']) ? $_POST['note'] : null;
    $license_type = $_POST['license_type'];
    
    // Fetch user details
    $sql = "SELECT ID_user, Rest_Temp FROM users WHERE Nbr_Financier = :nbr_Financier";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':nbr_Financier', $nbr_Financier, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "خطأ: الرقم المالي غير موجود في جدول المستخدمين";
        header("Location: form.php");
        exit;
    }

    $ID_user = $user['ID_user'];
    $Rest_Conge = $user['Rest_Temp'];

    // Fetch vice user details
    $sql = "SELECT ID_user FROM users WHERE Nbr_Financier = :nbr_FinancierV";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':nbr_FinancierV', $nbr_FinancierV, PDO::PARAM_INT);
    $stmt->execute();
    $vice_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vice_user) {
        $_SESSION['error'] = "خطأ: الرقم المالي الاحتياطي غير موجود في جدول المستخدمين";
        header("Location: form.php");
        exit;
    }

    $ID_userV = $vice_user['ID_user'];

    // Fetch license type details to check if it's "رسمية" and "Supp" is 0
    $sql = "SELECT Rasmiya, Supp FROM contrat WHERE ID_Contrat = :license_type";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':license_type', $license_type, PDO::PARAM_INT);
    $stmt->execute();
    $license = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($license && $license['Rasmiya'] == 1 && $license['Supp'] == 0) {
        // Check if remaining leave is sufficient
        $new_Reste_Conge = $Rest_Conge - $license_duration;
        if ($new_Reste_Conge < 0) {
            $_SESSION['error'] = "خطأ: مدة الرخصة تتجاوز الرصيد المتبقي.";
            header("Location: form.php");
            exit;
        }
    }

    $date_debut = date('Y-m-d', strtotime($stop_date));
    if ($license && $license['Rasmiya'] == 1 && $license['Supp'] == 0) {
        $date_retour = calculateDateRetour($date_debut, $license_duration, $holidays);
    } else {
        $date_retour = date('Y-m-d', strtotime($stop_date . ' + ' . $license_duration . ' days'));
    }
    $date_actuel = date('Y-m-d');

    try {
        $dbh->beginTransaction();

        $sql = "INSERT INTO conge (ID_user, ID_Vice, ID_Contrat, Duree_Conge, Date_Conge, Date_Debut, Date_Retour, Remarque) 
                VALUES (:ID_user, :ID_Vice, :ID_Contrat, :Duree_Conge, :Date_Conge, :Date_Debut, :Date_Retour, :Remarque)";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':ID_user', $ID_user, PDO::PARAM_INT);
        $stmt->bindParam(':ID_Vice', $ID_userV, PDO::PARAM_INT);
        $stmt->bindParam(':ID_Contrat', $license_type, PDO::PARAM_INT);
        $stmt->bindParam(':Duree_Conge', $license_duration, PDO::PARAM_INT);
        $stmt->bindParam(':Date_Conge', $date_actuel);
        $stmt->bindParam(':Date_Debut', $date_debut);
        $stmt->bindParam(':Date_Retour', $date_retour);
        $stmt->bindParam(':Remarque', $note);
        $stmt->execute();

        $ID_conge = $dbh->lastInsertId(); // Fetch the last inserted ID

        // Update user's remaining leave and log the previous remaining leave
        $sql = "UPDATE users SET Rest_Temp = :newResteConge WHERE Nbr_Financier = :nbr_Financier";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':newResteConge', $new_Reste_Conge, PDO::PARAM_INT);
        $stmt->bindParam(':nbr_Financier', $nbr_Financier, PDO::PARAM_INT);
        $stmt->execute();

        $dbh->commit();

        $_SESSION['success'] = "تمت العملية بنجاح!";
        
        // Redirect to PDF generation script with ID_conge
        header("Location: ../include/StopPDF.php?ID_conge=" . $ID_conge);
        exit;

    } catch (PDOException $e) {
        $dbh->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        error_log($e->getMessage());
    }
}

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

$financiers = [];
try {
    $sql = "SELECT Nbr_Financier FROM users";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $financiers = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>استمارة التسجيل</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="website icon" type="png" href="../images/M6.png">
    <style>
        body {
            background: url('../images/zelij.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.8); /* White background with transparency */
            padding: 20px;
            border-radius: 10px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0px 0px 10px 0px #000;
        }
        .form-container h1 {
            text-align: center;
        }
    </style>
</head>
<body>
    
    <form action="form.php" method="post" id="registration-form">
        <div class="demo-page">
            <main class="demo-page-content">
                <section>
                    <div class="href-target" id="input-types"></div>
                    <h1>استمارة التسجيل</h1>
                    <div class="nice-form-group">
                        <label>الرقم لمالي</label>
                        <input type="search" id="nbr-Financier" name="nbr_Financier" required autocomplete="off" />
                        <div class="options"></div>
                    </div>
                    <div class="nice-form-group">
                        <label>الاسم الكامل</label>
                        <input type="text" id="full_name" name="full_name" disabled placeholder="الاسم الكامل" value="" />
                    </div>
                    <div class="nice-form-group">
                        <label>نوع الرخصة</label>
                        <select name="license_type" id="license_type" required>
                            <?php foreach ($license_types as $license): ?>
                                <option value="<?php echo htmlspecialchars($license['ID_Contrat']); ?>">
                                    <?php echo htmlspecialchars($license['Type_Contrat']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="nice-form-group">
                        <label>تاريخ الإيقاف</label>
                        <input type="date" name="stop_date" required />
                    </div>
                    <div class="nice-form-group">
                        <label>مدة الرخصة</label>
                        <input type="number" id="license_duration" name="license_duration" required /><br>
                        <span class="error-message" id="error-message">المدة المدخلة أكبر من الرصيد المتبقي من الرخصة</span>
                    </div>
                    <div class="nice-form-group">
                        <label>الرقم لمالي</label>
                        <input type="search" id="nbr-Financier2" name="nbr_FinancierV" required autocomplete="off" />
                        <div class="options"></div>
                    </div>
                    <div class="nice-form-group">
                        <label>الاسم الكامل</label>
                        <input type="text" id="full_name2" name="full_name2" disabled placeholder="الاسم الكامل" value="" />
                    </div>
                    <div class="nice-form-group">
                        <label>ملاحضة</label>
                        <textarea rows="5" name="note" placeholder="اختياري"></textarea>
                    </div>
                    <details>
                        <summary>
                            <div class="toggle-code">
                                <input type="reset" value="الغاء" class="styled-button" id="btn-reset">
                                <input type="submit" value="حفظ" class="styled-button" id="submit-btn">
                            </div>
                        </summary>
                    </details>
                </section>
            </main>
        </div>
    </form>

<script>
    $(document).ready(function() {
        var data = <?php echo json_encode($financiers); ?>.map(String);
        var resteConge = 0;
        var rasmiyaContrats = <?php echo json_encode($rasmiyaContrats); ?>;

        $("#nbr-Financier").autocomplete({
            source: data,
            select: function(event, ui) {
                $("#nbr-Financier").val(ui.item.value);
                fetchFullName(ui.item.value);
                fetchResteConge(ui.item.value);
                return false;
            }
        });

        $("#nbr-Financier2").autocomplete({
            source: data,
            select: function(event, ui) {
                $("#nbr-Financier2").val(ui.item.value);
                fetchFullName2(ui.item.value);
                return false;
            }
        });

        // Validate the license duration
        function validateLicenseDuration() {
            var licenseType = $("#license_type").val();
            var licenseDuration = parseInt($("#license_duration").val(), 10);

            console.log("License Type: ", licenseType);
            console.log("License Duration: ", licenseDuration);
            console.log("Remaining Leave: ", resteConge);
            console.log("Rasmiya Contracts: ", rasmiyaContrats);

            if (rasmiyaContrats.includes(parseInt(licenseType))) {
                console.log("Entered Rasmiya condition");

                if (licenseDuration > resteConge) {
                    console.log("License duration exceeds remaining leave");
                    $("#error-message").show();
                    $("#submit-btn").attr("disabled", "disabled");
                    $("#license_duration").attr("max", resteConge);
                } else {
                    console.log("License duration is within remaining leave");
                    $("#error-message").hide();
                    $("#submit-btn").removeAttr("disabled");
                }
            } else {
                console.log("Entered non-Rasmiya condition");
                $("#error-message").hide();
                $("#submit-btn").removeAttr("disabled");
                $("#license_duration").removeAttr("max");
            }
        }

        // Event listeners for input changes
        $("#license_duration").on("input", function() {
            validateLicenseDuration();
        });

        $("#license_type").on("change", function() {
            validateLicenseDuration();
        });

        function fetchFullName(nbr_Financier) {
            $.ajax({
                type: "POST",
                url: "../include/Rech_name.php",
                data: { nbr_Financier: nbr_Financier },
                success: function(response) {
                    $("#full_name").val(response);
                }
            });
        }

        function fetchFullName2(nbr_Financier) {
            $.ajax({
                type: "POST",
                url: "../include/Rech_name.php",
                data: { nbr_Financier: nbr_Financier },
                success: function(response) {
                    $("#full_name2").val(response);
                }
            });
        }

        function fetchResteConge(nbr_Financier) {
            $.ajax({
                type: "POST",
                url: "../include/Rest_Conge.php",
                data: { nbr_Financier: nbr_Financier },
                success: function(response) {
                    console.log("Reste Conge Response: ", response);
                    resteConge = parseInt(response, 10);
                    validateLicenseDuration();
                }
            });
        }
        $("#registration-form").on("submit", function(event) {
            var confirmation = confirm("هل أنت متأكد أنك تريد إرسال النموذج؟");
            if (!confirmation) {
                event.preventDefault(); 
            }
        });
    });
</script>
</body>
</html>
