<?php
require '../vendor/autoload.php';
require 'dbConn.php'; 

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();

    $sql = "INSERT INTO users (Nbr_Financier, CIN, Prenom, Nom, Metier, Departement, Rest_Temp, Supp, password_hash, role, sex, date_naissance, adresse, numero_telephone, nom_complet_fr, previous_Rest_Temp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $dbh->prepare($sql);

 
    foreach ($worksheet->getRowIterator() as $row) {
        if ($row->getRowIndex() == 1) continue;
        $rowData = [];
        foreach ($row->getCellIterator() as $cell) {
            $rowData[] = $cell->getValue();
        }

        $nbr_financier = $rowData[0];
        $cin = $rowData[1];
        $prenom = $rowData[2];
        $nom = $rowData[3];
        $metier = $rowData[4];
        $departement = $rowData[5];
        $rest_temp = $rowData[6];
        $supp = $rowData[7];
        $sex = $rowData[8];
        $date_naissance = $rowData[9];
        $adresse = $rowData[10];
        $numero_telephone = $rowData[11];
        $nom_complet_fr = $rowData[12];
        $previous_rest_temp = $rowData[13];

        $hashedPassword = password_hash($cin, PASSWORD_BCRYPT);

        $stmt->execute([$nbr_financier, $cin, $prenom, $nom, $metier, $departement, $rest_temp, $supp, $hashedPassword, 'user', $sex, $date_naissance, $adresse, $numero_telephone, $nom_complet_fr, $previous_rest_temp]);
    }

    echo "Data inserted successfully!";
} else {
    echo "No file uploaded or invalid file format.";
}
?>
