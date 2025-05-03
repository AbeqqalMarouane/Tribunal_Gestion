<?php
include_once('../include/dbConn.php');

ob_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $Nbr_Conge = $_POST['Nbr_Conge'];
    $Duree_Conge = $_POST['Duree_Conge'];
    $Date_Debut = $_POST['Date_Debut'];
    
    
    

    

    try {
        $date_debut = new DateTime($Date_Debut);
        $date_debut_str = $date_debut->format('Y-m-d');

        $sql2 = "UPDATE conge SET 
                Duree_Conge = ?,
                Date_Debut = ?,
                Contrat_Etat = 'ENCOURS'
            WHERE ID_Conge = ?";

        $stmt = $dbh->prepare($sql2);
        $stmt->execute([$Duree_Conge, $date_debut_str, $Nbr_Conge]);
        header("Location: ../user/indexU.php"); 
        exit();
    } catch (PDOException $e) {
        
        echo "Error: " . $e->getMessage();
    }
}

ob_end_flush();
?>