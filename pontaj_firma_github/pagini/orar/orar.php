<?php
include '../../includes/config.php';
include '../../includes/auth.php';
checkAuth();

// Determina săptămâna curentă sau cea viitoare
$saptamana = isset($_GET['saptamana']) ? $_GET['saptamana'] : 'curenta';
if ($saptamana == 'viitoare') {
    $startOfWeek = new DateTime('monday next week');
} else {
    $startOfWeek = new DateTime('monday this week');
}

// Procesează asignarea turei
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asigneaza_tura'])) {
    $angajat_id = $_POST['angajat_id'];
    $data_start = $_POST['data_start'];
    $tura_id = $_POST['tura_id'];
    
    if ($tura_id == '0') {
        // Șterge programul dacă este selectat "-"
        $delete_query = $conn->prepare("DELETE FROM orar_angajati WHERE angajat_id = ? AND data_start = ?");
        $delete_query->execute([$angajat_id, $data_start]);
    } else {
        // Obține detalii tura
        $tura_query = $conn->prepare("SELECT ora_start, ora_end, trece_peste_zi FROM ture WHERE id = ?");
        $tura_query->execute([$tura_id]);
        $tura = $tura_query->fetch(PDO::FETCH_ASSOC);
        
        // Calculează orele efective
        $ora_start_efectiva = $data_start . ' ' . $tura['ora_start'];
        
        if ($tura['trece_peste_zi']) {
            $data_end = date('Y-m-d', strtotime($data_start . ' +1 day'));
            $ora_end_efectiva = $data_end . ' ' . $tura['ora_end'];
        } else {
            $ora_end_efectiva = $data_start . ' ' . $tura['ora_end'];
        }
        
        // Verifică dacă există deja program și actualizează, altfel inserează
        $check_query = $conn->prepare("SELECT id FROM orar_angajati WHERE angajat_id = ? AND data_start = ?");
        $check_query->execute([$angajat_id, $data_start]);
        
        if ($check_query->fetch()) {
            $update_query = $conn->prepare("UPDATE orar_angajati SET tura_id = ?, ora_start_efectiva = ?, ora_end_efectiva = ? WHERE angajat_id = ? AND data_start = ?");
            $update_query->execute([$tura_id, $ora_start_efectiva, $ora_end_efectiva, $angajat_id, $data_start]);
        } else {
            $insert_query = $conn->prepare("INSERT INTO orar_angajati (angajat_id, data_start, tura_id, ora_start_efectiva, ora_end_efectiva) VALUES (?, ?, ?, ?, ?)");
            $insert_query->execute([$angajat_id, $data_start, $tura_id, $ora_start_efectiva, $ora_end_efectiva]);
        }
    }
    
    // Redirect pentru a evita reincarcarea formularului
    header("Location: orar.php?saptamana=" . $saptamana);
    exit();
}

// Obține toate turele pentru dropdown
$ture_query = $conn->query("SELECT t.id, t.nume_tura, t.ora_start, t.ora_end, p.nume_program 
                           FROM ture t 
                           JOIN tipuri_program p ON t.tip_program_id = p.id 
                           ORDER BY p.nume_program, t.nume_tura");
$ture = $ture_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orar Angajați - Sistem Pontaj</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        .container-orar {
            display: flex;
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .orar-saptamanal {
            flex: 3;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .total-ore {
            flex: 1;
            min-width: 300px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tabel-orar {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .tabel-orar th, .tabel-orar td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .tabel-orar th {
            background-color: #f2f2f2;
            position: sticky;
            top: 0;
        }
        .dropdown-tura {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
            cursor: pointer;
        }
        .dropdown-tura:hover {
            background: #f8f9fa;
        }
        .saptamana-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        .btn-saptamana {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-saptamana:hover {
            background: #0056b3;
        }
        .btn-saptamana.active {
            background: #28a745;
        }
        .card-angajat {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 10px;
            margin: 10px 0;
        }
        .progress-fill {
            background: #28a745;
            height: 100%;
            border-radius: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .value {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="margin: 0;">🏢 Orar Angajați</h1>
            <div>
                <span>Bun venit, <?php echo $_SESSION['nume']; ?>!</span>
                <?php if ($_SESSION['este_admin']): ?>
                    <span style="margin-left: 10px; background: #dc3545; padding: 2px 8px; border-radius: 10px; font-size: 12px;">ADMIN</span>
                <?php endif; ?>
                <a href="../../dashboard.php" style="color: white; margin-left: 15px; text-decoration: none;">🏠 Dashboard</a>
                <a href="../../logout.php" style="color: white; margin-left: 15px; text-decoration: none;">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container-orar">
        <!-- Partea Stângă - Orarul cu Dropdown-uri -->
        <div class="orar-saptamanal">
            <div class="saptamana-nav">
                <a href="?saptamana=curenta" class="btn-saptamana <?php echo $saptamana == 'curenta' ? 'active' : ''; ?>">
                    📅 Săptămâna Curentă
                </a>
                <a href="?saptamana=viitoare" class="btn-saptamana <?php echo $saptamana == 'viitoare' ? 'active' : ''; ?>">
                    🔮 Săptămâna Viitoare
                </a>
            </div>
            
            <h2>📅 Orarul - <?php echo $saptamana == 'viitoare' ? 'Săptămâna Viitoare' : 'Săptămâna Curentă'; ?></h2>
            
            <?php
            // Obținem toți angajații
            $angajati_query = $conn->query("SELECT id, nume FROM angajati ORDER BY nume");
            $angajati = $angajati_query->fetchAll(PDO::FETCH_ASSOC);
            
            // Generăm zilele săptămânii
            $zile_saptamana = [];
            for ($i = 0; $i < 7; $i++) {
                $zi = clone $startOfWeek;
                $zi->modify("+$i days");
                $zile_saptamana[] = $zi;
            }
            ?>
            
            <table class="tabel-orar">
                <thead>
                    <tr>
                        <th>Angajat</th>
                        <?php foreach($zile_saptamana as $zi): ?>
                            <th><?php echo $zi->format('d.m') . '<br>' . ['Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică'][$zi->format('N')-1]; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($angajati as $angajat): ?>
                    <tr>
                        <td><strong><?php echo $angajat['nume']; ?></strong></td>
                        <?php foreach($zile_saptamana as $zi): ?>
                        <td>
                            <?php
                            $data_cautare = $zi->format('Y-m-d');
                            
                            // Caută programul existent
                            $program_query = $conn->prepare("SELECT tura_id FROM orar_angajati WHERE angajat_id = ? AND data_start = ?");
                            $program_query->execute([$angajat['id'], $data_cautare]);
                            $program_existent = $program_query->fetch(PDO::FETCH_ASSOC);
                            $tura_curenta = $program_existent ? $program_existent['tura_id'] : 0;
                            
                            if ($_SESSION['este_admin']): ?>
                                <!-- Admin vede dropdown -->
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="angajat_id" value="<?php echo $angajat['id']; ?>">
                                    <input type="hidden" name="data_start" value="<?php echo $data_cautare; ?>">
                                    <select name="tura_id" class="dropdown-tura" onchange="this.form.submit()">
                                        <option value="0">-</option>
                                        <?php foreach($ture as $tura): 
                                            $ora_start = date('H:i', strtotime($tura['ora_start']));
                                            $ora_end = date('H:i', strtotime($tura['ora_end']));
                                            $selected = $tura_curenta == $tura['id'] ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $tura['id']; ?>" <?php echo $selected; ?>>
                                                <?php echo $tura['nume_tura'] . ' (' . $ora_start . '-' . $ora_end . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="asigneaza_tura" value="1">
                                </form>
                            <?php else: ?>
                                <!-- Angajatul normal vede doar afișare -->
                                <?php
                                if ($tura_curenta > 0) {
                                    $tura_info_query = $conn->prepare("SELECT nume_tura, ora_start, ora_end FROM ture WHERE id = ?");
                                    $tura_info_query->execute([$tura_curenta]);
                                    $tura_info = $tura_info_query->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($tura_info) {
                                        $ora_start = date('H:i', strtotime($tura_info['ora_start']));
                                        $ora_end = date('H:i', strtotime($tura_info['ora_end']));
                                        echo $tura_info['nume_tura'] . "<br>(" . $ora_start . "-" . $ora_end . ")";
                                    }
                                } else {
                                    echo "<span style='color: #999;'>-</span>";
                                }
                                ?>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Partea Dreaptă - Statistici (păstrăm versiunea simplă) -->
        <div class="total-ore">
            <h2>📊 Statistici Luna Curentă</h2>
            
            <?php 
            $luna_curenta = date('n');
            $an_curent = date('Y');
            
            foreach($angajati as $angajat): 
                $zile_lucrate_query = $conn->prepare("
                    SELECT COUNT(DISTINCT DATE(data_pontaj)) as zile_lucrate 
                    FROM pontaje 
                    WHERE angajat_id = ? 
                        AND MONTH(data_pontaj) = ? 
                        AND YEAR(data_pontaj) = ?
                        AND tip = 'intrare'
                ");
                $zile_lucrate_query->execute([$angajat['id'], $luna_curenta, $an_curent]);
                $zile_lucrate = $zile_lucrate_query->fetch(PDO::FETCH_ASSOC)['zile_lucrate'] ?? 0;
                
                $ore_lucrate = $zile_lucrate * 8;
                $ore_maxime = 160;
                $procentaj = $ore_maxime > 0 ? min(100, ($ore_lucrate / $ore_maxime) * 100) : 0;
            ?>
            <div class="card-angajat">
                <h4><?php echo $angajat['nume']; ?></h4>
                
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $procentaj; ?>%"></div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="label">Zile lucrate:</span>
                        <span class="value"><?php echo $zile_lucrate; ?> zile</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Ore lucrate:</span>
                        <span class="value"><?php echo $ore_lucrate; ?>h</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Ore rămase:</span>
                        <span class="value"><?php echo max(0, $ore_maxime - $ore_lucrate); ?>h</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Procentaj:</span>
                        <span class="value"><?php echo round($procentaj, 1); ?>%</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>