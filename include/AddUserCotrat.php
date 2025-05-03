<?php
include_once('../include/dbConn.php');
// Start output buffering
ob_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_POST['id_user'];
    $id_contrat= $_POST['license_type'];
    $Duree_Conge = $_POST['Duree_Conge'];
    $Date_Debut = $_POST['Date_Debut'];

    $Date_Conge = date('Y/m/d');

    $current_date = new DateTime();


    $current_date->modify("+$Duree_Conge days");


    
    
    

    

    try {

        $date_debut = new DateTime($Date_Debut);
        $date_debut_str = $date_debut->format('Y-m-d');

        $sql = "INSERT INTO `conge`( `ID_user`, `ID_contrat`, `Date_Conge`, `Date_Debut`, `Duree_Conge`)
         VALUES (?,?,?,?,?)";

        $stmt = $dbh->prepare($sql);
        $stmt->execute([$id_user, $id_contrat,$Date_Conge,$Date_Debut, $Duree_Conge]);
        header("Location: ../user/indexU.php");
        exit();
    } catch (PDOException $e) {
        
        echo "Error: " . $e->getMessage();
    }
}

ob_end_flush();
?>