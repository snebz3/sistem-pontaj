<?php
session_start();
include 'includes/config.php';

// Verificăm dacă utilizatorul este logat
if (!isset($_SESSION['angajat_id'])) {
    header('Location: index.php');
    exit();
}

// Obținem ultimul pontaj al angajatului
$ultim_pontaj = null;
$sql = "SELECT * FROM pontaje WHERE angajat_id = ? ORDER BY data_pontaj DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([$_SESSION['angajat_id']]);
$ultim_pontaj = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Pontaj</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .welcome {
            font-size: 24px;
            color: #333;
        }
        .pontaj-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .pontaj-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 20px 40px;
            font-size: 18px;
            border-radius: 10px;
            cursor: pointer;
            margin: 10px;
        }
        .pontaj-btn:hover {
            background: #218838;
        }
        .menu {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .menu-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .menu-btn:hover {
            background: #0056b3;
        }
        .logout {
            background: #dc3545;
        }
        .logout:hover {
            background: #c82333;
        }
        .ultim-pontaj {
            margin-top: 20px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="welcome">👋 Bun venit, <?php echo $_SESSION['nume']; ?>!</div>
        
        <?php if ($_SESSION['este_admin']): ?>
            <div style="color: #dc3545; margin-top: 10px;">🔧 Rol: Administrator</div>
        <?php endif; ?>
        
        <div class="menu">
            <a href="pagini/orar/orar.php" class="menu-btn">📅 Vezi Orar</a>
            <a href="pagini/pontaje/istoric_pontaje.php" class="menu-btn">⏱️ Istoric Pontaje</a>
            
            <?php if ($_SESSION['este_admin']): ?>
                <a href="pagini/angajati/gestiune_angajati.php" class="menu-btn">👥 Gestiune Angajați</a>
                <a href="pagini/pontaje/rapoarte_pontaje.php" class="menu-btn">📊 Rapoarte</a>
            <?php endif; ?>
            
            <a href="logout.php" class="menu-btn logout">🚪 Logout</a>
        </div>
    </div>
    
    <div class="pontaj-section">
        <h2>⏱️ Sistem Pontaj</h2>
        
        <?php if ($ultim_pontaj): ?>
            <div class="ultim-pontaj">
                Ultimul pontaj: 
                <strong><?php echo date('d.m.Y H:i', strtotime($ultim_pontaj['data_pontaj'])); ?></strong>
                (<?php echo $ultim_pontaj['tip']; ?>)
            </div>
        <?php else: ?>
            <div class="ultim-pontaj">Nu ai niciun pontaj înregistrat.</div>
        <?php endif; ?>
        
        <form action="pontare.php" method="POST">
            <button type="submit" class="pontaj-btn">🎯 PONTAJ</button>
        </form>
        
        <p style="color: #666; margin-top: 10px;">
            Sistemul detectează automat dacă este intrare sau ieșire
        </p>
    </div>
</body>
</html>