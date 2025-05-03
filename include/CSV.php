<?php
include_once('dbConn.php');

function GetContrats($dbh, $filter){
    try {
        $whereClause = '';
        if ($filter == 'week') {
            $whereClause = "WHERE m.Date_Debut >= CURDATE() - INTERVAL DAYOFWEEK(CURDATE())+6 DAY AND m.Date_Debut <= CURDATE() + INTERVAL 7-DAYOFWEEK(CURDATE()) DAY";
        } elseif ($filter == 'month') {
            $whereClause = "WHERE m.Date_Debut >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND m.Date_Debut <= CURDATE()";
        }

        $sql = "SELECT u.CIN, 
                       CONCAT(u.Prenom, ' ', u.Nom) as name, 
                       m.Date_Debut as start_date,
                       DATE_ADD(m.Date_Debut, INTERVAL m.Duree_Conge DAY) as end_date,
                       m.Duree_Conge as duree, 
                       ct.Type_Contrat as kind,
                       CASE 
                           WHEN CURDATE() BETWEEN m.Date_Debut AND DATE_ADD(m.Date_Debut, INTERVAL m.Duree_Conge DAY) THEN 'قيد التنفيد'
                           WHEN CURDATE() < m.Date_Debut THEN 'قبل التنفيد'
                           ELSE 'اكتمل التنفيد'
                       END as status
                FROM users u 
                JOIN conge c ON u.ID_user = c.ID_user
                JOIN mawa3id m ON c.ID_Conge = m.ID_Conge
                JOIN contrat ct ON c.ID_contrat = ct.ID_Contrat
                $whereClause";
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$contracts = GetContrats($dbh, $filter);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment;filename=contracts.csv');

$output = fopen('php://output', 'w');

// Add BOM to fix UTF-8 in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['CIN', 'الإسم الكامل', 'موعد البدء', 'موعد الانتهاء', 'مدة الرخصة', 'نوع الرخصة', 'حالة الرخصة']);

foreach ($contracts as $contract) {
    fputcsv($output, [
        $contract['CIN'],
        $contract['name'],
        $contract['start_date'],
        $contract['end_date'],
        $contract['duree'],
        $contract['kind'],
        $contract['status']
    ]);
}

fclose($output);
?>
