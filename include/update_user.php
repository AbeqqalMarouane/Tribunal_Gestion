<?php

ob_start();

include_once('../include/dbConn.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_POST['id_user'];
    $Nbr_Financier = $_POST['Nbr_Financier'];
    $CIN = $_POST['CIN'];
    $Prenom = $_POST['Prenom'];
    $Nom = $_POST['Nom'];
    $Metier = $_POST['Metier'];
    $Departement = $_POST['Departement'];
    $Reste_Conge = $_POST['Reste_Conge'];
    $Date_Naissance = $_POST['Date_Naissance'];
    $Gender = $_POST['Gender'];
    $Adresse = $_POST['Adresse'];
    $Phone = $_POST['Phone'];
    $Nom_Complet = $_POST['Nom_Complet'];

    try {
        $sql2 = "UPDATE users SET 
                Nbr_Financier = ?,
                CIN = ?,
                Prenom = ?,
                Nom = ?,
                Metier = ?,
                Departement = ?,
                Rest_Temp = ?,
                Date_Naissance = ?,
                sex = ?,
                Adresse = ?,
                numero_telephone = ?,
                nom_complet_fr = ?
            WHERE Nbr_Financier = ?";

        $stmt = $dbh->prepare($sql2);
        $stmt->execute([$Nbr_Financier, $CIN, $Prenom, $Nom, $Metier, $Departement, $Reste_Conge, $Date_Naissance, $Gender, $Adresse, $Phone, $Nom_Complet, $id_user]);
        
        header("Location: users.php"); 
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}


ob_end_flush();
?>
