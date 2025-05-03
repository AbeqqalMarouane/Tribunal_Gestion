<?php
include('dbConn.php');

function getResteCongeByNbrFinancier($nbr_Financier, $dbh) {
    try {
        $sql = "SELECT Rest_Temp FROM users WHERE Nbr_Financier = :nbr_Financier";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':nbr_Financier', $nbr_Financier, PDO::PARAM_INT);
        $stmt->execute();
        $resteConge = $stmt->fetch(PDO::FETCH_COLUMN);
        if ($resteConge !== false) {
            return $resteConge;
        } else {
            return 0;
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 0;
    }
}

// Prevent immediate execution when included
if (isset($_POST['nbr_Financier'])) {
    $nbr_Financier = $_POST['nbr_Financier'];
    echo getResteCongeByNbrFinancier($nbr_Financier, $dbh);
}
?>
