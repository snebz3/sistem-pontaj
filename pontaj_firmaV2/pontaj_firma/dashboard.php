<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/config.php';

// Verificăm dacă utilizatorul este logat
if (!isset($_SESSION['angajat_id'])) {
    header('Location: index.php');
    exit();
}

// ==================== VERSIUNE FINALĂ CU STRUCTURA CORECTĂ ====================
function verificaIntarziereSimplu($angajat_id, $data_pontaj, $ora_pontaj, $conn) {
    // 1. Căutăm în tabelul orar_angajati cu coloana corectă data_start
    $sql = "SELECT * FROM orar_angajati WHERE angajat_id = ? AND data_start = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$angajat_id, $data_pontaj]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$program) {
        // Dacă nu are program, folosim ora implicită 08:00
        return [
            'intarziere' => false, 
            'minute' => 0,
            'ora_program' => '08:00',
            'toleranta' => 15,
            'motiv' => 'Nu are program - folosită ora implicită 08:00'
        ];
    }
    
    // 2. Găsim ora de început - folosim ora_start din tabelul ture
    $ora_inceput = '08:00'; // implicit
    $toleranta = 15; // implicit pentru 8h
    
    // Dacă avem tura_id, căutăm în tabelul TURE (nu tura!)
    if (isset($program['tura_id']) && $program['tura_id']) {
        $sql_tura = "SELECT t.*, tp.ore_pe_zi 
                     FROM ture t 
                     LEFT JOIN tipuri_program tp ON t.tip_program_id = tp.id 
                     WHERE t.id = ?";
        $stmt_tura = $conn->prepare($sql_tura);
        $stmt_tura->execute([$program['tura_id']]);
        $tura = $stmt_tura->fetch(PDO::FETCH_ASSOC);
        
        if ($tura) {
            // Obținem ora de început din tura
            if (isset($tura['ora_start']) && !empty($tura['ora_start'])) {
                $ora_inceput = $tura['ora_start'];
            }
            
            // Determinăm toleranța în funcție de ore_pe_zi
            if (isset($tura['ore_pe_zi'])) {
                if ($tura['ore_pe_zi'] >= 12) {
                    $toleranta = 10; // 10 minute pentru program de 12h
                } else {
                    $toleranta = 15; // 15 minute pentru program de 8h
                }
            }
        }
    }
    
    // 3. Calcul
    try {
        $ora_limită = strtotime($ora_inceput . " +{$toleranta} minutes");
        $ora_pontaj_timestamp = strtotime($ora_pontaj);
        
        if ($ora_pontaj_timestamp > $ora_limită) {
            $minute_intarziere = round(($ora_pontaj_timestamp - $ora_limită) / 60);
            return [
                'intarziere' => true, 
                'minute' => $minute_intarziere,
                'ora_program' => $ora_inceput,
                'toleranta' => $toleranta,
                'motiv' => "Pontaj la {$ora_pontaj}, program la {$ora_inceput}"
            ];
        }
    } catch (Exception $e) {
        // Dacă apare eroare la calcul
    }
    
    return [
        'intarziere' => false, 
        'minute' => 0,
        'ora_program' => $ora_inceput,
        'toleranta' => $toleranta,
        'motiv' => 'Pontaj la timp'
    ];
}

// ==================== STATISTICI SIMPLIFICATE ====================
$today = date('Y-m-d');
$is_admin = $_SESSION['este_admin'];
$angajat_id = $_SESSION['angajat_id'];
$stats = [];

// 1. Total pontări azi
if ($is_admin) {
    $sql = "SELECT COUNT(*) as total FROM pontaje WHERE DATE(data_pontaj) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$today]);
} else {
    $sql = "SELECT COUNT(*) as total FROM pontaje WHERE DATE(data_pontaj) = ? AND angajat_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$today, $angajat_id]);
}
$stats['pontari_azi'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 2. Angajați care au pontat azi (doar pentru admin)
if ($is_admin) {
    $sql = "SELECT COUNT(DISTINCT angajat_id) as total FROM pontaje WHERE DATE(data_pontaj) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$today]);
    $stats['angajati_azi'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// 3. ÎNTÂRZIERI - VERSIUNE SIMPLIFICATĂ
$stats['intarzieri_azi'] = 0;
$lista_intarzieri_detaliat = [];

// Pentru admin: verifică toate pontările
if ($is_admin) {
    $sql = "SELECT p.*, a.nume 
            FROM pontaje p 
            JOIN angajati a ON p.angajat_id = a.id 
            WHERE DATE(p.data_pontaj) = ? AND p.tip = 'intrare'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$today]);
    $pontaje = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($pontaje as $pontaj) {
        $intarziere = verificaIntarziereSimplu(
            $pontaj['angajat_id'],
            $today,
            date('H:i:s', strtotime($pontaj['data_pontaj'])),
            $conn
        );
        
        if ($intarziere['intarziere']) {
            $stats['intarzieri_azi']++;
            
            // Determină tipul programului pentru afișare
            $tip_program = ($intarziere['toleranta'] == 10) ? '12h' : '8h';
            
            $lista_intarzieri_detaliat[] = [
                'nume' => $pontaj['nume'],
                'data_pontaj' => $pontaj['data_pontaj'],
                'ora_inceput' => $intarziere['ora_program'],
                'tip_program' => $tip_program,
                'minute_intarziere' => $intarziere['minute']
            ];
        }
    }
} else {
    // Pentru angajat: doar pontările lui
    $sql = "SELECT * FROM pontaje 
            WHERE DATE(data_pontaj) = ? 
            AND angajat_id = ? 
            AND tip = 'intrare'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$today, $angajat_id]);
    $pontaje = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($pontaje as $pontaj) {
        $intarziere = verificaIntarziereSimplu(
            $angajat_id,
            $today,
            date('H:i:s', strtotime($pontaj['data_pontaj'])),
            $conn
        );
        
        if ($intarziere['intarziere']) {
            $stats['intarzieri_azi']++;
            
            $tip_program = ($intarziere['toleranta'] == 10) ? '12h' : '8h';
            
            $lista_intarzieri_detaliat[] = [
                'data_pontaj' => $pontaj['data_pontaj'],
                'ora_inceput' => $intarziere['ora_program'],
                'tip_program' => $tip_program,
                'minute_intarziere' => $intarziere['minute']
            ];
        }
    }
}

// 4. Pontări săptămâna aceasta
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('sunday this week'));

if ($is_admin) {
    $sql = "SELECT COUNT(*) as total FROM pontaje WHERE DATE(data_pontaj) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$startOfWeek, $endOfWeek]);
} else {
    $sql = "SELECT COUNT(*) as total FROM pontaje 
            WHERE DATE(data_pontaj) BETWEEN ? AND ? 
            AND angajat_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$startOfWeek, $endOfWeek, $angajat_id]);
}
$stats['pontari_saptamana'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 5. Ultimele 5 pontări
if ($is_admin) {
    $sql = "SELECT p.*, a.nume 
            FROM pontaje p 
            JOIN angajati a ON p.angajat_id = a.id 
            ORDER BY p.data_pontaj DESC 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $ultimele_pontari = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sql = "SELECT * FROM pontaje 
            WHERE angajat_id = ? 
            ORDER BY data_pontaj DESC 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$angajat_id]);
    $ultimele_pontari = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Adaugă verificare întârziere la ultimele pontări
foreach ($ultimele_pontari as &$pontaj) {
    if ($pontaj['tip'] == 'intrare') {
        $angajat_id_pontaj = $is_admin ? $pontaj['angajat_id'] : $angajat_id;
        $data_pontaj = date('Y-m-d', strtotime($pontaj['data_pontaj']));
        
        $intarziere = verificaIntarziereSimplu(
            $angajat_id_pontaj,
            $data_pontaj,
            date('H:i:s', strtotime($pontaj['data_pontaj'])),
            $conn
        );
        $pontaj['intarziere'] = $intarziere['intarziere'];
        $pontaj['minute_intarziere'] = $intarziere['minute'];
        $pontaj['ora_program'] = $intarziere['ora_program'];
    } else {
        $pontaj['intarziere'] = false;
        $pontaj['minute_intarziere'] = 0;
    }
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
        /* PĂSTREAZĂ TOATE STILURILE DIN FIȘIERUL ORIGINAL */
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
            flex-wrap: wrap;
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
        
        /* STATISTICI */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #007bff;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #333;
            font-size: 16px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 15px 0;
            color: #2c3e50;
        }
        
        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
            display: block;
        }
        
        .card-primary { border-left-color: #007bff; }
        .card-success { border-left-color: #28a745; }
        .card-warning { border-left-color: #ffc107; }
        .card-danger { border-left-color: #dc3545; }
        .card-info { border-left-color: #17a2b8; }
        
        /* ÎNTÂRZIERI */
        .intarzieri-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .intarzieri-section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #f4f4f4;
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .intarziere-count {
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .intarzieri-list {
            margin-top: 20px;
        }
        
        .intarziere-item {
            padding: 15px;
            border-bottom: 1px solid #f4f4f4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .intarziere-item:last-child {
            border-bottom: none;
        }
        
        .intarziere-info {
            flex-grow: 1;
        }
        
        .intarziere-nume {
            font-weight: bold;
            font-size: 16px;
        }
        
        .intarziere-detalii {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .intarziere-timp {
            background: #ffc107;
            color: #856404;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .intarziere-major {
            background: #dc3545;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .section-title {
            font-size: 22px;
            color: #333;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
            font-weight: 600;
        }
        
        .info-box {
            background: #e8f4ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="welcome">👋 Bun venit, <?php echo htmlspecialchars($_SESSION['nume']); ?>!</div>
        
        <?php if ($is_admin): ?>
            <div style="color: #dc3545; margin-top: 10px; font-weight: bold;">🔧 Rol: Administrator</div>
        <?php else: ?>
            <div style="color: #28a745; margin-top: 10px;">👤 Rol: Angajat</div>
        <?php endif; ?>
        
        <div class="menu">
            <a href="pagini/orar/orar.php" class="menu-btn">📅 Vezi Orar</a>
            <a href="pagini/pontaje/istoric.php" class="menu-btn">⏱️ Istoric Pontaje</a>
            
            <?php if ($is_admin): ?>
                <a href="pagini/angajati/index.php" class="menu-btn">👥 Gestiune Angajați</a>
                <a href="pagini/statistici/export_pdf.php" class="menu-btn">📊 Export PDF</a>
            <?php endif; ?>
            
            <a href="logout.php" class="menu-btn logout">🚪 Logout</a>
        </div>
    </div>
    
    <div class="pontaj-section">
        <h2>⏱️ Sistem Pontaj</h2>
        
        <?php if ($ultim_pontaj): 
            if ($ultim_pontaj['tip'] == 'intrare') {
                $intarziere = verificaIntarziereSimplu(
                    $_SESSION['angajat_id'],
                    date('Y-m-d', strtotime($ultim_pontaj['data_pontaj'])),
                    date('H:i:s', strtotime($ultim_pontaj['data_pontaj'])),
                    $conn
                );
            }
        ?>
            <div class="ultim-pontaj">
                📍 <strong>Ultimul pontaj:</strong> 
                <?php echo date('d.m.Y H:i', strtotime($ultim_pontaj['data_pontaj'])); ?>
                <span style="margin-left: 10px; font-weight: bold; color: <?php echo $ultim_pontaj['tip'] == 'intrare' ? '#28a745' : '#ffc107'; ?>">
                    (<?php echo $ultim_pontaj['tip'] == 'intrare' ? 'INTRARE' : 'IEȘIRE'; ?>)
                </span>
                
                <?php if ($ultim_pontaj['tip'] == 'intrare' && isset($intarziere) && $intarziere['intarziere']): ?>
                    <div style="margin-top: 10px; color: #dc3545; font-weight: bold;">
                        ⚠️ Ai întârziat <?php echo $intarziere['minute']; ?> minute
                        (Program: <?php echo $intarziere['ora_program']; ?>, Toleranță: <?php echo $intarziere['toleranta']; ?> min)
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="ultim-pontaj">ℹ️ Nu ai niciun pontaj înregistrat.</div>
        <?php endif; ?>
        
        <form action="pontare.php" method="POST">
            <button type="submit" class="pontaj-btn">🎯 PONTAJ</button>
        </form>
        
        <p style="color: #666; margin-top: 10px;">
            ⚡ Sistemul detectează automat dacă este intrare sau ieșire
        </p>
    </div>
    
    <!-- ==================== SECȚIUNEA STATISTICI ==================== -->
    <h2 class="section-title">📊 Statistici Pontaje</h2>
    <!--
    <div class="info-box">
        <p><strong>ℹ️ Sistem nou de calcul întârzieri:</strong></p>
        <p>• Întârzierile se calculează dinamic după programul fiecărui angajat</p>
        <p>• Toleranță: 15 minute pentru program 8h, 10 minute pentru program 12h</p>
        <p>• Comparație: ora pontaj vs ora program + toleranță</p>
        <p>• Programul se obține din tabelul orar_angajati → ture → tipuri_program</p>
    </div> -->
    
    <div class="stats-grid">
        <div class="stat-card card-primary">
            <div class="stat-icon">📅</div>
            <h3>Pontări Astăzi</h3>
            <div class="stat-value"><?php echo $stats['pontari_azi']; ?></div>
            <p style="color: #666; font-size: 14px;"><?php echo date('d.m.Y'); ?></p>
        </div>
        
        <?php if ($is_admin): ?>
        <div class="stat-card card-success">
            <div class="stat-icon">👥</div>
            <h3>Angajați Activi Astăzi</h3>
            <div class="stat-value"><?php echo $stats['angajati_azi']; ?></div>
            <p style="color: #666; font-size: 14px;">Au pontat cel puțin o dată</p>
        </div>
        <?php endif; ?>
        
        <div class="stat-card card-warning">
            <div class="stat-icon">📊</div>
            <h3>Pontări Săptămâna</h3>
            <div class="stat-value"><?php echo $stats['pontari_saptamana']; ?></div>
            <p style="color: #666; font-size: 14px;">
                <?php echo date('d.m', strtotime($startOfWeek)) . ' - ' . date('d.m.Y', strtotime($endOfWeek)); ?>
            </p>
        </div>
        
        <div class="stat-card card-danger">
            <div class="stat-icon">⚠️</div>
            <h3>Întârzieri Astăzi</h3>
            <div class="stat-value"><?php echo $stats['intarzieri_azi']; ?></div>
            <p style="color: #666; font-size: 14px;">Calcul bazat pe program</p>
        </div>
    </div>
    
    <!-- ==================== SECȚIUNEA ÎNTÂRZIERI ==================== -->
    <!--
    <div class="intarzieri-section">
        <h2>📋 Detalii întârzieri astăzi 
            <span class="intarziere-count"><?php echo count($lista_intarzieri_detaliat); ?></span>
        </h2>
        
        <div class="intarzieri-list">
            <?php if (count($lista_intarzieri_detaliat) > 0): ?>
                <?php foreach ($lista_intarzieri_detaliat as $intarziere): ?>
                    <div class="intarziere-item">
                        <div class="intarziere-info">
                            <div class="intarziere-nume">
                                <?php if ($is_admin): ?>
                                    <?php echo htmlspecialchars($intarziere['nume'] ?? 'Angajat'); ?>
                                <?php else: ?>
                                    Pontajul tău
                                <?php endif; ?>
                            </div>
                            <div class="intarziere-detalii">
                                Program: <strong><?php echo $intarziere['ora_inceput']; ?></strong> 
                                | Pontaj: <strong><?php echo date('H:i', strtotime($intarziere['data_pontaj'])); ?></strong>
                                <?php if (isset($intarziere['tip_program'])): ?>
                                    | Tip: <strong><?php echo htmlspecialchars($intarziere['tip_program']); ?></strong>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="intarziere-timp <?php echo (isset($intarziere['minute_intarziere']) && $intarziere['minute_intarziere'] > 30) ? 'intarziere-major' : ''; ?>">
                            <?php echo $intarziere['minute_intarziere']; ?> minute
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">✅</div>
                    <h3>Nicio întârziere astăzi!</h3>
                    <p>Toți angajații sunt la timp conform programelor lor.</p>
                </div>
            <?php endif; ?>
        </div>
    </div> -->
    
    <!-- ==================== ULTIMELE PONTĂRI ==================== -->
    <div class="recent-activity" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2 style="margin-top: 0; color: #333; border-bottom: 2px solid #f4f4f4; padding-bottom: 10px;">🕐 Ultimele Pontări</h2>
        <ul style="list-style: none; padding: 0;">
            <?php if (count($ultimele_pontari) > 0): ?>
                <?php foreach ($ultimele_pontari as $pontaj): ?>
                    <li style="padding: 12px 0; border-bottom: 1px solid #f4f4f4; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>
                                <?php 
                                if ($is_admin) {
                                    echo htmlspecialchars($pontaj['nume'] ?? 'Angajat');
                                } else {
                                    echo 'Pontajul tău';
                                }
                                ?>
                            </strong>
                            <br>
                            <span style="color: #666; font-size: 14px;">
                                <?php echo date('d.m.Y H:i', strtotime($pontaj['data_pontaj'])); ?>
                                <?php if ($pontaj['tip'] == 'intrare' && isset($pontaj['intarziere']) && $pontaj['intarziere']): ?>
                                    <span style="color: #dc3545; font-weight: bold;">
                                        (Întârziat <?php echo $pontaj['minute_intarziere']; ?> min)
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <span style="padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; background: <?php echo $pontaj['tip'] == 'intrare' ? '#d4edda' : '#fff3cd'; ?>; color: <?php echo $pontaj['tip'] == 'intrare' ? '#155724' : '#856404'; ?>;">
                            <?php echo $pontaj['tip'] == 'intrare' ? 'INTRARE' : 'IEȘIRE'; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li style="padding: 12px 0;">
                    <div>📭 Nu există pontări recente</div>
                </li>
            <?php endif; ?>
        </ul>
        
        <?php if (count($ultimele_pontari) > 0): ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="pagini/pontaje/istoric.php" class="menu-btn" style="background: #6c757d; text-decoration: none; display: inline-block; padding: 8px 16px; border-radius: 5px; color: white;">
                👉 Vezi toate pontările
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- FOOTER -->
    <div style="text-align: center; margin-top: 40px; color: #666; font-size: 14px; padding: 20px; background: white; border-radius: 10px;">
        <p><strong>Sistem Pontaj © <?php echo date('Y'); ?></strong> - Toate drepturile rezervate</p>
        <p>Ultima actualizare: <?php echo date('d.m.Y H:i:s'); ?></p>
        <p id="live-time" style="color: #007bff; font-weight: bold; margin-top: 10px;"></p>
    </div>
    
    <script>
    function updateLiveTime() {
        const now = new Date();
        const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                          now.getMinutes().toString().padStart(2, '0') + ':' + 
                          now.getSeconds().toString().padStart(2, '0');
        document.getElementById('live-time').textContent = 'Ora curentă: ' + timeString;
    }
    setInterval(updateLiveTime, 1000);
    updateLiveTime();
    </script>
</body>
</html>