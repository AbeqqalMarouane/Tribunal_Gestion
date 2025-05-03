<?php
include_once('dbConn.php');

function getCongePerMonth($dbh, $year) {
    $sql = "SELECT MONTH(m.Date_Debut) as month, COUNT(*) as count 
            FROM mawa3id m
            WHERE YEAR(m.Date_Debut) = :year 
            GROUP BY month";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getEmployeeCongeStats($dbh) {
    // Total employees
    $sql = "SELECT COUNT(*) as total FROM users";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $totalEmployees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Employees who took leave
    $sql = "SELECT COUNT(DISTINCT c.ID_user) as count FROM conge c";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $employeesWithConge = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $employeesWithoutConge = $totalEmployees - $employeesWithConge;

    return [
        'total' => $totalEmployees,
        'withConge' => $employeesWithConge,
        'withoutConge' => $employeesWithoutConge,
    ];
}

function getCongeLeftStats($dbh) {
    // Employees who used all their leave
    $sql = "SELECT COUNT(*) as count FROM conge c JOIN users u ON c.ID_user = u.ID_user WHERE c.Reste = 0";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $employeesWithNoCongeLeft = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Employees with leave left
    $sql = "SELECT COUNT(*) as count FROM conge c JOIN users u ON c.ID_user = u.ID_user WHERE c.Reste > 0";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $employeesWithCongeLeft = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    return [
        'withNoCongeLeft' => $employeesWithNoCongeLeft,
        'withCongeLeft' => $employeesWithCongeLeft,
    ];
}

$currentYear = date('Y');
$previousYear = $currentYear - 1;

$congePerMonthCurrentYear = getCongePerMonth($dbh, $currentYear);
$congePerMonthPreviousYear = getCongePerMonth($dbh, $previousYear);
$employeeCongeStats = getEmployeeCongeStats($dbh);
$congeLeftStats = getCongeLeftStats($dbh);

$data = [
    'congePerMonthCurrentYear' => $congePerMonthCurrentYear,
    'congePerMonthPreviousYear' => $congePerMonthPreviousYear,
    'employeeCongeStats' => $employeeCongeStats,
    'congeLeftStats' => $congeLeftStats,
];

// Debugging output to see if the data is correct
error_log(print_r($data, true));

echo json_encode($data);
?>
