<?php
session_start();
include 'includes/config.php';

if (!isset($_SESSION['angajat_id'])) {
    header('Location: index.php');
    exit();
}

// Determinăm tipul de pontaj (intrare/ieșire)
$sql = "SELECT tip FROM pontaje WHERE angajat_id = ? ORDER BY data_pontaj DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([$_SESSION['angajat_id']]);
$ultim_pontaj = $stmt->fetch(PDO::FETCH_ASSOC);

$tip_pontaj = 'intrare'; // implicit
if ($ultim_pontaj && $ultim_pontaj['tip'] == 'intrare') {
    $tip_pontaj = 'iesire';
}

// Inserăm noul pontaj
$sql = "INSERT INTO pontaje (angajat_id, tip) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->execute([$_SESSION['angajat_id'], $tip_pontaj]);

header('Location: dashboard.php?message=pontaj_success');
exit();
?>