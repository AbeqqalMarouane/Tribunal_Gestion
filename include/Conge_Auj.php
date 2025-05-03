<?php 
include_once('dbConn.php');

function getNbrNotification($dbh) {
    try {
        $date_debut = date('Y-m-d'); // Store the date in a variable

        // Count Nbr_Financier notifications
        $sql = "SELECT COUNT(Nbr_Financier) FROM users u
                JOIN conge c ON u.ID_user = c.ID_user
                WHERE c.Date_Debut = :date_debut";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':date_debut', $date_debut, PDO::PARAM_STR);
        $stmt->execute();
        $CongeAuj = $stmt->fetch(PDO::FETCH_COLUMN); // Fetch the result

        // If no results, set to 0
        if ($CongeAuj === false) {
            $CongeAuj = 0;
        }

        // Count conge with Status ENCOURS and Date_Conge as today
        $sql2 = "SELECT COUNT(*) FROM conge 
                 WHERE Status = 'ENCOURS' AND Date_Conge = :date_conge";
        $stmt2 = $dbh->prepare($sql2);
        $stmt2->bindParam(':date_conge', $date_debut, PDO::PARAM_STR);
        $stmt2->execute();
        $EncoursToday = $stmt2->fetch(PDO::FETCH_COLUMN); // Fetch the result

        // If no results, set to 0
        if ($EncoursToday === false) {
            $EncoursToday = 0;
        }

        return array(
            'Nbr_Financier' => $CongeAuj,
            'Encours_Today' => $EncoursToday
        );
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return array(
            'Nbr_Financier' => 0,
            'Encours_Today' => 0
        );
    }
}
?>
