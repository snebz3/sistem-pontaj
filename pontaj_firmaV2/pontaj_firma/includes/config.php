<?php
// Configurare baza de date
$host = 'localhost';
$dbname = 'pontaj_firma';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Eroare conexiune baza de date: " . $e->getMessage());
}

// Setări sesiune - verifică dacă sesiunea nu este deja pornită
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>