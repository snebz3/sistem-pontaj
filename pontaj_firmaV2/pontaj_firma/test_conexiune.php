<?php
include 'includes/config.php';

echo "✅ Conexiunea la baza de date funcționează!<br>";

// Testează dacă putem citi din baza de date
$sql = "SELECT COUNT(*) as total FROM angajati";
$result = $conn->query($sql);
$row = $result->fetch(PDO::FETCH_ASSOC);

echo "✅ Număr angajați în baza de date: " . $row['total'] . "<br>";

// Afișează lista angajaților
$sql = "SELECT nume, email, departament FROM angajati";
$result = $conn->query($sql);

echo "<h3>Lista angajaților:</h3>";
foreach($result as $row) {
    echo "👤 " . $row['nume'] . " - " . $row['email'] . " - " . $row['departament'] . "<br>";
}
?>