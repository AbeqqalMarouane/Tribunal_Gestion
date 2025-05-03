<?php
include_once('../include/dbConn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['Type_Contrat'] ?? '';
    $duration = $_POST['Duree_Contrat'] ?? 0;
    $validity = $_POST['Duree_Validité'] ?? 0;
    $date_update = $_POST['Date_Update'] ?? '';

    try {
        $sql = "INSERT INTO contrat (Type_Contrat, Duree_Contrat, Duree_Validité, Date_Update, Supp) VALUES (:type, :duration, :validity, :date_update, 0)";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
        $stmt->bindParam(':validity', $validity, PDO::PARAM_INT);
        $stmt->bindParam(':date_update', $date_update, PDO::PARAM_STR);
        $stmt->execute();

        $ID_Contrat = $dbh->lastInsertId();

        $response = [
            'ID_Contrat' => $ID_Contrat,
            'Type_Contrat' => $type,
            'Duree_Contrat' => $duration,
            'Duree_Validité' => $validity,
            'Date_Update' => $date_update,
            'Supp' => 0 // Assuming 0 means not deleted
        ];

        echo json_encode($response);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}
?>
