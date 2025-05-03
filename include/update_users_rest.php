<?php
include_once('../include/dbConn.php');

if (isset($_GET['ID_Contrat'])) {
    $id_contrat = $_GET['ID_Contrat'];

    try {
        // Begin transaction
        $dbh->beginTransaction();

        // Fetch the contract duration for the selected contract
        $sql = "SELECT Duree_Contrat FROM contrat WHERE ID_Contrat = :id_contrat ";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id_contrat', $id_contrat, PDO::PARAM_INT);
        $query->execute();
        $contract = $query->fetch(PDO::FETCH_ASSOC);

        if ($contract) {
            // Get the contract duration
            $duree_contrat = $contract['Duree_Contrat'];

            // Update the Rest_Temp column for all users where Supp = 0 (active users)
            $sql_update = "UPDATE users SET Rest_Temp = :duree_contrat WHERE Supp = 0";
            $query_update = $dbh->prepare($sql_update);
            $query_update->bindParam(':duree_contrat', $duree_contrat, PDO::PARAM_INT);

            if ($query_update->execute()) {
                // Commit transaction
                $dbh->commit();
                echo json_encode(['success' => true]);
            } else {
                // Rollback transaction in case of error
                $dbh->rollBack();
                echo json_encode(['success' => false, 'error' => $query_update->errorInfo()[2]]);
            }
        } else {
            // Rollback transaction if contract not found or not rasmiya
            $dbh->rollBack();
            echo json_encode(['success' => false, 'error' => 'Contract not found or not rasmiya']);
        }
    } catch (Exception $e) {
        // Rollback transaction if an exception occurs
        $dbh->rollBack();
        echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid contract ID']);
}
?>
