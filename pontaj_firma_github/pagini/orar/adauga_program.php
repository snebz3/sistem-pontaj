<?php
session_start();
include '../../includes/config.php';
include '../../includes/auth.php';
checkAuth();
checkAdmin(); // Doar adminii pot adăuga programe
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaugă Program - Sistem Pontaj</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f4f4;
        }
        .header {
            background: #343a40;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #218838;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="margin: 0;">🏢 Adaugă Program</h1>
            <div>
                <a href="../../dashboard.php" style="color: white; margin-left: 15px; text-decoration: none;">🏠 Dashboard</a>
                <a href="../orar.php" style="color: white; margin-left: 15px; text-decoration: none;">📅 Orar</a>
                <a href="../../logout.php" style="color: white; margin-left: 15px; text-decoration: none;">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2>➕ Adaugă Program Angajat</h2>
        
        <?php
        // Procesează formularul
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $angajat_id = $_POST['angajat_id'];
            $data_start = $_POST['data_start'];
            $tura_id = $_POST['tura_id'];
            
            // Obține detalii tura pentru a calcula orele efective
            $tura_query = $conn->prepare("SELECT ora_start, ora_end, trece_peste_zi FROM ture WHERE id = ?");
            $tura_query->execute([$tura_id]);
            $tura = $tura_query->fetch(PDO::FETCH_ASSOC);
            
            // Calculează orele efective
            $ora_start_efectiva = $data_start . ' ' . $tura['ora_start'];
            
            if ($tura['trece_peste_zi']) {
                // Dacă tura trece peste noapte, adaugă o zi la data_end
                $data_end = date('Y-m-d', strtotime($data_start . ' +1 day'));
                $ora_end_efectiva = $data_end . ' ' . $tura['ora_end'];
            } else {
                $ora_end_efectiva = $data_start . ' ' . $tura['ora_end'];
            }
            
            // Inserează în baza de date
            $insert_query = $conn->prepare("INSERT INTO orar_angajati (angajat_id, data_start, tura_id, ora_start_efectiva, ora_end_efectiva) VALUES (?, ?, ?, ?, ?)");
            $insert_query->execute([$angajat_id, $data_start, $tura_id, $ora_start_efectiva, $ora_end_efectiva]);
            
            echo '<div class="success">✅ Program adăugat cu succes!</div>';
        }
        ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="angajat_id">Selectează Angajat:</label>
                <select id="angajat_id" name="angajat_id" required>
                    <option value="">-- Alege angajat --</option>
                    <?php
                    $angajati_query = $conn->query("SELECT id, nume FROM angajati ORDER BY nume");
                    $angajati = $angajati_query->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach($angajati as $angajat) {
                        echo "<option value='{$angajat['id']}'>{$angajat['nume']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="data_start">Data:</label>
                <input type="date" id="data_start" name="data_start" required>
            </div>
            
            <div class="form-group">
                <label for="tura_id">Selectează Tura:</label>
                <select id="tura_id" name="tura_id" required>
                    <option value="">-- Alege tura --</option>
                    <?php
                    $ture_query = $conn->query("SELECT t.id, t.nume_tura, t.ora_start, t.ora_end, p.nume_program 
                                               FROM ture t 
                                               JOIN tipuri_program p ON t.tip_program_id = p.id 
                                               ORDER BY p.nume_program, t.nume_tura");
                    $ture = $ture_query->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach($ture as $tura) {
                        $ora_start = date('H:i', strtotime($tura['ora_start']));
                        $ora_end = date('H:i', strtotime($tura['ora_end']));
                        echo "<option value='{$tura['id']}'>{$tura['nume_program']} - {$tura['nume_tura']} ({$ora_start}-{$ora_end})</option>";
                    }
                    ?>
                </select>
            </div>
            
            <button type="submit">➕ Adaugă Program</button>
        </form>
    </div>
</body>
</html>