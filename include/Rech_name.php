<?php
include('dbConn.php');

if (isset($_POST['nbr_Financier'])) {
    $nbr_Financier = $_POST['nbr_Financier'];
    echo getFullNameByNbrFinancier($nbr_Financier, $dbh);
}

function getFullNameByNbrFinancier($nbr_Financier, $dbh) {
    try {
        $sql = "SELECT CONCAT(u.Prenom, ' ', u.Nom) AS full_name 
                FROM users u 
                WHERE u.Nbr_Financier = :nbr_Financier";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':nbr_Financier', $nbr_Financier, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_COLUMN);
        if ($user) {
            return htmlspecialchars($user, ENT_QUOTES, 'UTF-8');
        } else {
            return 'No user found';
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 'Error occurred';
    }
}
?>
