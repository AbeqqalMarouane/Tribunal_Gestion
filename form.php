<?php
session_start();
error_reporting(0);
include('include/Rest_Conge.php');

function fetchHolidays($year, $country_code, $api_key) {
    $url = "https://calendarific.com/api/v2/holidays?&api_key={$api_key}&country={$country_code}&year={$year}";

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return [];
    } else {
        $data = json_decode($response, true);
        $holidays = [];

        if (isset($data['response']['holidays'])) {
            foreach ($data['response']['holidays'] as $holiday) {
                $holidays[] = $holiday['date']['iso'];
            }
        }

        return $holidays;
    }
}

function isWeekend($date) {
    return (date('N', strtotime($date)) >= 6);
}

function isHoliday($date, $holidays) {
    return in_array($date, $holidays);
}

function calculateDateRetour($start_date, $duration, $holidays) {
    $date = strtotime($start_date);
    $days_added = 0;

    while ($days_added < $duration) {
        $date = strtotime('+1 day', $date);
        $formatted_date = date('Y-m-d', $date);

        if (!isWeekend($formatted_date) && !isHoliday($formatted_date, $holidays)) {
            $days_added++;
        }
    }

    return date('Y-m-d', $date);
}

$api_key = 'baa9dc110aa712sd3a9fa2a3dwb6c01d4c875950dc32vs';
$country_code = 'MA'; // Morocco country code
$year = date('Y');

$holidays = fetchHolidays($year, $country_code, $api_key);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nbr_Financier = $_POST['nbr_Financier'];
    $nbr_FinancierV = $_POST['nbr_FinancierV'];
    $service = $_POST['service'];
    $stop_date = $_POST['stop_date'];
    $license_duration = $_POST['license_duration'];
    $note = isset($_POST['note']) ? $_POST['note'] : null;
    $license_type = $_POST['license_type'];

    $sql = "SELECT Id_user, Reste_Conge FROM users WHERE Nbr_Financier = :nbr_Financier";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':nbr_Financier', $nbr_Financier, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "Error: Nbr_Financier does not exist in users table";
        header("Location: form.php");
        exit;
    }

    $Id_user = $user['Id_user'];
    $Rest_Conge = $user['Reste_Conge'];
    $new_Reste_Conge = $Rest_Conge - $license_duration;

    if ($license_type == 'سنوية' && $new_Reste_Conge < 0) {
        $_SESSION['error'] = "Error: License duration exceeds remaining leave.";
        header("Location: form.php");
        exit;
    }

    $date_debut = date('Y-m-d', strtotime($stop_date));
    if ($license_type == 'سنوية') {
        $date_retour = calculateDateRetour($date_debut, $license_duration, $holidays);
    } else {
        $date_retour = date('Y-m-d', strtotime($stop_date . ' + ' . $license_duration . ' days'));
    }
    $date_actuel = date('Y-m-d');

    try {
        $dbh->beginTransaction();

        $sql = "INSERT INTO contrat (Nbr_Financier, Type_Conge, Duree_Conge, Date_Conge, Date_Debut, Date_Retour, Nbr_Vice, note, Id_user) 
                VALUES (:nbr_Financier, :license_type, :license_duration, :date_actuel, :date_debut, :date_retour, :nbr_FinancierV, :note, :Id_user)";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':nbr_Financier', $nbr_Financier);
        $stmt->bindParam(':license_type', $license_type);
        $stmt->bindParam(':license_duration', $license_duration);
        $stmt->bindParam(':date_actuel', $date_actuel);
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_retour', $date_retour);
        $stmt->bindParam(':nbr_FinancierV', $nbr_FinancierV);
        $stmt->bindParam(':note', $note);
        $stmt->bindParam(':Id_user', $Id_user);
        $stmt->execute();

        if ($license_type == 'سنوية') {
            $sql = "UPDATE users SET Reste_Conge = :newResteConge WHERE Nbr_Financier = :nbr_Financier";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':newResteConge', $new_Reste_Conge, PDO::PARAM_INT);
            $stmt->bindParam(':nbr_Financier', $nbr_Financier, PDO::PARAM_INT);
            $stmt->execute();
        }

        $dbh->commit();

        $_SESSION['success'] = "Operation completed successfully!";
    } catch (PDOException $e) {
        $dbh->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: form.php");
    exit;
}

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
    <title>استمارة التسجيل</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="./style.css">
    <link rel="website icon" type="png" href="images/M6.png">
</head>
<body>
    <form action="form.php" method="post" id="registration-form">
        <div class="demo-page">
            <main class="demo-page-content">
                <section>
                    <div class="href-target" id="input-types"></div>
                    <h1>
                        استمارة التسجيل 
                        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 48 48">
                        <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="4">
                                <path d="M24 24v-5L39 4ل5 5ل-15 15ز" clip-rule="evenodd"/>
                                <path d="M16 24H9a5 5 0 0 0 0 10h30a5 5 0 0 1 0 10H18"/>
                            </g>
                        </svg>
                    </h1>
                    <div class="nice-form-group">
                        <label>الرقم لمالي</label>
                        <input type="search" id="nbr-Financier" placeholder="" name="nbr_Financier" required autocomplete="off" />
                        <div class="options"></div>
                    </div>
                    <div class="nice-form-group">
                        <label>الاسم الكامل</label>
                        <input type="text" id="full_name" name="full_name" disabled placeholder="الاسم الكامل" value="" />
                    </div>
                    <fieldset class="nice-form-group">
                        <div class="nice-form-group">
                            <label for="r-1"><span style="padding-right: 6px;">مصلحة المنتدبين القضائيين والأطر التقنية</span><input type="radio" name="service" value="مصلحة المنتدبين القضائيين والأطر التقنية" id="r-1" required /></label>
                        </div>
                        <div class="nice-form-group">
                            <label for="r-2">
                                <span style="padding-right: 6px;">مصلحة كتاب الضبط والمحررين القضائيين</span><input type="radio" name="service" value="مصلحة كتاب الضبط والمحررين القضائيين" id="r-2" required />
                            </label>
                        </div>
                    </fieldset>
                    <div class="nice-form-group">
                        <label>نوع الرخصة</label>
                        <select name="license_type" id="license_type" required>
                            <option value="سنوية">رخصة سنوية</option>
                            <option value="لوفاة">رخصة لوفاة</option>
                            <option value="استثنائية">رخصة استثنائية</option>
                            <option value="لأغراض شخصية">رخصة لأغراض شخصية</option>
                            <option value="الحج">رخصة الحج</option>
                            <option value="الزفاف">رخصة الزفاف</option>
                            <option value="الولادة">رخصة الولادة</option>
                            <option value="العقيقة">العقيقة</option>
                            <option value="الأبوة">رخصة الأبوة</option>
                            <option value="تغيب">رخصة تغيب</option>
                        </select>
                    </div>
                    <div class="nice-form-group">
                        <label>تاريخ الإيقاف</label>
                        <input type="date" name="stop_date" value="2018-07-22" required />
                    </div>
                    <div class="nice-form-group">
                        <label>مدة الرخصة</label>
                        <input type="number" id="license_duration" name="license_duration" placeholder="1234" required /><br>
                        <span class="error-message" style="color: red;display: none;padding-right: 5px 0 0 0;font-family:'Cairo' !important;font-size:12px;" id="error-message">المدة المدخلة أكبر من الرصيد المتبقي من الرخصة</span>
                    </div>
                    <div class="nice-form-group ">
                        <label>الرقم لمالي</label>
                        <input type="search" id="nbr-Financier2" placeholder="" name="nbr_FinancierV" required autocomplete="off" />
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
                var licenseType = $("#license_type").val();
                var licenseDuration = parseInt($("#license_duration").val(), 10);

                if (licenseType === "سنوية") {
                    if (licenseDuration > resteConge) {
                        $("#error-message").show();
                        $("#submit-btn").attr("disabled", "disabled");
                        $("#license_duration").attr("max", resteConge);
                    } else {
                        $("#error-message").hide();
                        $("#submit-btn").removeAttr("disabled");
                    }
                } else {
                    $("#error-message").hide();
                    $("#submit-btn").removeAttr("disabled");
                    $("#license_duration").removeAttr("max");
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
                        validateLicenseDuration();
                    }
                });
            }
        });
    </script>
      <script>
    <?php if (isset($_SESSION['success'])) { ?>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?php echo $_SESSION['success']; ?>',
            confirmButtonColor: '#20c997',
            customClass: {
                popup: 'colored-toast'
            }
        });
        <?php unset($_SESSION['success']); ?>
    <?php } ?>

    <?php if (isset($_SESSION['error'])) { ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo $_SESSION['error']; ?>',
            confirmButtonColor: '#dc3545',
            customClass: {
                popup: 'colored-toast'
            }
        });
        <?php unset($_SESSION['error']); ?>
    <?php } ?>
</script>

<style>
    .swal2-popup.colored-toast {
        border-radius: 10px;
        background: url('images/selij.png') no-repeat center center, rgba(255, 255, 255, 0.9);
        background-size: cover;
    }
</style>


</body>
</html>
