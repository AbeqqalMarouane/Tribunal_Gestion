<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('include/Rest_Conge.php');
include_once('include/dbConn.php');

$ID_Conge = isset($_GET['ID_Conge']) ? htmlspecialchars($_GET['ID_Conge'], ENT_QUOTES, 'UTF-8') : '';
$action = isset($_GET['action']) ? htmlspecialchars($_GET['action'], ENT_QUOTES, 'UTF-8') : '';

// Fetch user information based on ID_Conge
$user_info = [];
if ($ID_Conge) {
    try {
        $sql = "SELECT c.ID_Conge, u.Nbr_Financier, CONCAT(u.Prenom, ' ', u.Nom) as name, co.Type_Contrat as license_type, c.Date_Debut as stop_date, c.Duree_Conge , c.ID_Vice as nbr_FinancierV, c.Remarque    
                FROM users u 
                JOIN conge c ON c.Id_user = u.Id_user
                JOIN contrat co ON c.Id_contrat = co.Id_contrat
                WHERE c.ID_Conge = :ID_Conge";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':ID_Conge', $ID_Conge, PDO::PARAM_INT);
        $stmt->execute();
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

function fetchHolidays($dbh) {
    $sql = "SELECT holiday_date, durree FROM holidays";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to check if a date is a weekend
function isWeekend($date) {
    return (date('N', strtotime($date)) >= 6);
}

// Function to check if a date is a holiday
function isHoliday($date, $holidays) {
    foreach ($holidays as $holiday) {
        if ($date == $holiday['holiday_date']) {
            return $holiday['durree'];
        }
    }
    return false;
}

// Function to calculate the return date considering weekends and holidays
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
    $ID_Conge = $_POST['ID_Conge'];
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
        $_SESSION['error'] = "Error: Nbr_Financier does not exist in users table";
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
        $_SESSION['error'] = "Error: Nbr_FinancierV does not exist in users table";
        header("Location: EditForm.php");
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
            $_SESSION['error'] = "Error: License duration exceeds remaining leave.";
            header("Location: EditForm.php");
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

        // Update user's remaining leave and log the previous remaining leave
        $sql = "UPDATE users SET Rest_Temp = :newResteConge WHERE Nbr_Financier = :nbr_Financier";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':newResteConge', $new_Reste_Conge, PDO::PARAM_INT);
        $stmt->bindParam(':nbr_Financier', $nbr_Financier, PDO::PARAM_INT);
        $stmt->execute();

        $dbh->commit();

        $_SESSION['success'] = "Operation completed successfully!";
    } catch (PDOException $e) {
        $dbh->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        error_log($e->getMessage());
    }

    header("Location: talabat.php");
    exit;
}

// Fetch license types
$license_types = [];
try {
    $sql = "SELECT ID_Contrat, Type_Contrat FROM contrat";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $license_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch the data for the autocomplete
$financiers = array();
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
  <title>استمارة التعديل</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <link rel="stylesheet" href="./style.css">
  <link rel="website icon" type="png" href="images/M6.png">
  <style>
        body {
            background: url('images/zelij.jpg') no-repeat center center fixed;
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
    <form action="EditForm.php" method="post" id="registration-form">
        <input type="hidden" name="ID_Conge" value="<?= $ID_Conge; ?>">
        <input type="hidden" name="nbr_Financier" value="<?= $user_info['Nbr_Financier'] ?? ''; ?>">
        <div class="demo-page">
            <main class="demo-page-content">
                <section>
                    <div class="href-target" id="input-types"></div>
                    <h1>استمارة التسجيل</h1>
                    <div class="nice-form-group">
                        <label>الرقم لمالي</label>
                        <input type="text" id="nbr-Financier" placeholder="" name="nbr_Financier_display" required autocomplete="off" value="<?= $user_info['Nbr_Financier'] ?? ''; ?>" disabled />
                        <div class="options"></div>
                    </div>
                    <div class="nice-form-group">
                        <label>الاسم الكامل</label>
                        <input type="text" id="full_name" name="full_name" placeholder="الاسم الكامل" value="<?= $user_info['name'] ?? ''; ?>" disabled />
                    </div>
                    <div class="nice-form-group">
                        <label>نوع الرخصة</label>
                        <select name="license_type" id="license_type" required>
                            <?php foreach ($license_types as $license): ?>
                                <option value="<?= htmlspecialchars($license['ID_Contrat']); ?>" <?= (isset($user_info['license_type']) && $user_info['license_type'] == $license['Type_Contrat']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($license['Type_Contrat']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="nice-form-group">
                        <label>تاريخ الإيقاف</label>
                        <input type="date" name="stop_date" value="<?= $user_info['stop_date'] ?? ''; ?>" required />
                    </div>
                    <div class="nice-form-group">
                        <label>مدة الرخصة</label>
                        <input type="number" id="license_duration" name="license_duration" value="<?= $user_info['Duree_Conge'] ?? ''; ?>" required /><br>
                        <span class="error-message" style="color: red; display: none;" id="error-message">المدة المدخلة أكبر من الرصيد المتبقي من الرخصة</span>
                    </div>
                    <div class="nice-form-group">
                        <label>الرقم لمالي</label>
                        <input type="search" id="nbr-Financier2" name="nbr_FinancierV" value="<?= $user_info['nbr_FinancierV'] ?? ''; ?>" required autocomplete="off" />
                        <div class="options"></div>
                    </div>
                    <div class="nice-form-group">
                        <label>الاسم الكامل</label>
                        <input type="text" id="full_name2" name="full_name2" disabled placeholder="الاسم الكامل" value="" />
                    </div>
                    <div class="nice-form-group">
                        <label>ملاحضة</label>
                        <textarea rows="5" name="note" placeholder="اختياري"><?= $user_info['Remarque'] ?? ''; ?></textarea>
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

            $("#license_duration").on("input", function() {
                validateLicenseDuration();
            });

            $("#license_type").on("change", function() {
                validateLicenseDuration();
            });

            function validateLicenseDuration() {
                var licenseDuration = parseInt($("#license_duration").val(), 10);
                
                // Ensure resteConge is not zero if the response is delayed
                if (resteConge == 0) {
                    fetchResteConge($("#nbr-Financier").val());
                }

                if (licenseDuration > resteConge) {
                    $("#error-message").show();
                    $("#submit-btn").attr("disabled", "disabled");
                } else {
                    $("#error-message").hide();
                    $("#submit-btn").removeAttr("disabled");
                }
            }

            function fetchFullName(nbr_Financier) {
                $.ajax({
                    type: "POST",
                    url: "include/Rech_name.php",
                    data: { nbr_Financier: nbr_Financier },
                    success: function(response) {
                        $("#full_name").val(response);
                    }
                });
            }

            function fetchFullName2(nbr_Financier) {
                $.ajax({
                    type: "POST",
                    url: "include/Rech_name.php",
                    data: { nbr_Financier: nbr_Financier },
                    success: function(response) {
                        $("#full_name2").val(response);
                    }
                });
            }

            function fetchResteConge(nbr_Financier) {
                $.ajax({
                    type: "POST",
                    url: "include/Rest_Conge.php",
                    data: { nbr_Financier: nbr_Financier },
                    success: function(response) {
                        resteConge = parseInt(response, 10);
                        $("#license_duration").attr("max", resteConge);
                        validateLicenseDuration();
                    }
                });
            }
        });
    </script>
</body>
</html>
