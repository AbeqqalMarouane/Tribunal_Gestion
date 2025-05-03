<?php
session_start();
include('dbConn.php');

$response = ['success' => false, 'message' => ''];

if (isset($_GET['action'])) {
    if (isset($_GET['ID_Conge'])) {
        handleCongeActions($dbh, $_GET['action'], $_GET['ID_Conge']);
    } elseif (isset($_GET['ID_Contrat'])) {
        handleContratActions($dbh, $_GET['action'], $_GET['ID_Contrat']);
    } elseif (isset($_GET['Nbr_Financier'])) {
        handleUserActions($dbh, $_GET['action'], $_GET['Nbr_Financier']);
    }
}

function handleCongeActions($dbh, $action, $ID_Conge) {
    global $response;

    try {
        $sql = "SELECT c.Duree_Conge, c.Date_Debut, 
                       DATE_ADD(c.Date_Debut, INTERVAL c.Duree_Conge DAY) as Date_Retour, 
                       c.ID_user 
                FROM conge c
                WHERE c.ID_Conge = :ID_Conge";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':ID_Conge', $ID_Conge, PDO::PARAM_INT);
        $stmt->execute();
        $contract = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($contract) {
            if ($action === 'تمديد') {
                // Redirect to EditForm.php with the necessary details
                header("Location: ../include/EditForm.php?ID_Conge=$ID_Conge&action=extend");
                exit();
            }

            $response['success'] = true;
        }
    } catch (PDOException $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }

    echo json_encode($response);
}

function handleContratActions($dbh, $action, $ID_Contrat) {
    global $response;

    if ($action == 'delete') {
        try {
            $supp = 1; // Set Supp to 1 to mark the record as deleted
            $sql = "UPDATE contrat SET Supp = :supp WHERE ID_Contrat = :id_contrat";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':supp', $supp, PDO::PARAM_INT);
            $stmt->bindParam(':id_contrat', $ID_Contrat, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $response['message'] = "Record marked as deleted successfully";
                $response['success'] = true;
            } else {
                $response['message'] = "Error: " . $stmt->errorInfo()[2];
            }
        } catch (PDOException $e) {
            $response['message'] = "Error: " . $e->getMessage();
        }
        header("Location: ../admin/rokhas.php"); // Redirect back to your main page
        exit();
    }

    if ($action == 'DeleteUserContrat') {
        $supp = 1; // Set Supp to 1 to mark the record as deleted
        $sql = "UPDATE conge SET Supp = :supp WHERE ID_Conge = :id_contrat";
        $query = $dbh->prepare($sql);

        $query->bindParam(':supp', $supp, PDO::PARAM_INT);
        $query->bindParam(':id_contrat', $ID_Contrat, PDO::PARAM_INT);

        if ($query->execute()) {
            $message = "Record marked as deleted successfully";
        } else {
            $message = "Error: " . $query->errorInfo()[2];
        }

        header("Location: ../user/IndexU.php"); // Redirect back to your main page
        exit();
    }
}

function handleUserActions($dbh, $action, $Nbr_Financier) {
    global $response;

    try {
        if ($action == 'deleteUser') {
            $sql = "UPDATE users SET Supp = 1 WHERE Nbr_Financier = :Nbr_Financier";
        } elseif ($action == 'RestoreUser') {
            $sql = "UPDATE users SET Supp = 0 WHERE Nbr_Financier = :Nbr_Financier";
        }

        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':Nbr_Financier', $Nbr_Financier, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response['message'] = "User status updated successfully";
            $response['success'] = true;
        } else {
            $response['message'] = "Error: " . $stmt->errorInfo()[2];
        }
    } catch (PDOException $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }

    header("Location: ../admin/users.php"); // Redirect back to your main page
    exit();
}
?>
