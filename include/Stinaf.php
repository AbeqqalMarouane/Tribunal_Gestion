<?php 
session_start();
include_once('../include/dbConn.php');

function fetchHolidays($dbh) {
    try {
        $sql = "SELECT holiday_date, durree FROM holidays";
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $holidays;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function isWeekend($date) {
    return (date('N', strtotime($date)) >= 6);
}

function isHoliday($date, $holidays) {
    foreach ($holidays as $holiday) {
        if ($date == $holiday['holiday_date']) {
            return true;
        }
    }
    return false;
}

$response = ['success' => false, 'message' => ''];

if (isset($_GET['ID_Conge'])) {
    $ID_Conge = htmlspecialchars($_GET['ID_Conge'], ENT_QUOTES, 'UTF-8');

    try {
        // Fetch contract details
        $sql = "SELECT c.Duree_Conge, c.Date_Debut, c.Date_Retour, c.ID_user
                FROM conge c
                WHERE c.ID_Conge = :ID_Conge";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':ID_Conge', $ID_Conge, PDO::PARAM_INT);
        $stmt->execute();
        $contract = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($contract) {
            $duree_conge = $contract['Duree_Conge'];
            $date_debut = $contract['Date_Debut'];
            $date_retour = $contract['Date_Retour'];
            $ID_user = $contract['ID_user'];
            $today = date('Y-m-d');

            // Fetch holidays
            $holidays = fetchHolidays($dbh);

            // Debugging step: Log values for inspection
            error_log("Today: $today, Date_Retour: $date_retour");

            // If today is before the return date, calculate the remaining leave days
            if ($today < $date_retour) {
                $date = strtotime($today);
                $end_date = strtotime($date_retour);
                $days_diff = 0;

                while ($date < $end_date) {
                    $date = strtotime('+1 day', $date);
                    $formatted_date = date('Y-m-d', $date);

                    if (!isWeekend($formatted_date) && !isHoliday($formatted_date, $holidays)) {
                        $days_diff++;
                    }
                }

                // Update the user's Rest_Temp
                $sql = "UPDATE users SET Rest_Temp = Rest_Temp + :days_diff WHERE ID_user = :ID_user";
                $stmt = $dbh->prepare($sql);
                $stmt->bindParam(':days_diff', $days_diff, PDO::PARAM_INT);
                $stmt->bindParam(':ID_user', $ID_user, PDO::PARAM_INT);
                $stmt->execute();

                // Update the conge record to set the new end date to today
                $sql = "UPDATE conge SET Date_Retour = :today WHERE ID_Conge = :ID_Conge";
                $stmt = $dbh->prepare($sql);
                $stmt->bindParam(':today', $today);
                $stmt->bindParam(':ID_Conge', $ID_Conge, PDO::PARAM_INT);

                // Debugging step: Check if the update statement executes
                if ($stmt->execute()) {
                    error_log("Date_Retour updated successfully for ID_Conge: $ID_Conge");
                } else {
                    error_log("Failed to update Date_Retour for ID_Conge: $ID_Conge");
                }

                // Log the action in the history table
                logAction($dbh, $ID_user, 'استئناف', $days_diff);

                // Call genPDF.php to generate PDF after processing is done
                header("Location: GenPDF.php?ID_Conge=$ID_Conge"); // Redirect to genPDF.php with the necessary ID parameter
                exit;
            } else {
                error_log("No update required as today is not before the Date_Retour");
            }

            $response['success'] = true;
        } else {
            $response['message'] = 'No contract found';
        }
    } catch (PDOException $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }
}

// Redirect to talabat.php if no updates or if the ID_Conge is not set
header("Location: ../admin/talabat.php");
exit;

function logAction($dbh, $ID_user, $action, $days) {
    $sql = "INSERT INTO action_history (ID_user, action, days, action_date) VALUES (:ID_user, :action, :days, NOW())";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':ID_user', $ID_user, PDO::PARAM_INT);
    $stmt->bindParam(':action', $action, PDO::PARAM_STR);
    $stmt->bindParam(':days', $days, PDO::PARAM_INT);
    $stmt->execute();
}
?>
