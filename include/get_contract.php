<?php
include_once('dbConn.php');

if (isset($_GET['ID_Contrat'])) {
    $id_contrat = $_GET['ID_Contrat'];
    $sql = "SELECT * FROM contrat WHERE ID_Contrat = :id_contrat AND Supp = 0";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id_contrat', $id_contrat, PDO::PARAM_INT);
    $query->execute();

    $contract = $query->fetch(PDO::FETCH_ASSOC);
    if ($contract) {
        echo json_encode($contract);
    } else {
        echo json_encode(['error' => 'No contract found']);
    }
}
?>
