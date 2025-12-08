<?php
include '../../includes/config.php';
include '../../includes/auth.php';
include '../../includes/functions.php';
checkAuth();
checkAdmin();

// Verifică dacă există ID-ul angajatului
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID angajat invalid!");
    exit();
}

$angajat_id = (int)$_GET['id'];

// Obține datele angajatului
$sql = "SELECT a.*, tp.nume_program, tp.ore_pe_zi 
        FROM angajati a
        LEFT JOIN tipuri_program tp ON a.tip_program_id = tp.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$angajat_id]);
$angajat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$angajat) {
    header("Location: index.php?error=Angajatul nu a fost găsit!");
    exit();
}

// Statistici angajat
$stats_sql = "SELECT 
                COUNT(DISTINCT DATE(data_pontaj)) as total_zile_pontate,
                COUNT(*) as total_pontaje,
                MIN(data_pontaj) as prima_pontare,
                MAX(data_pontaj) as ultima_pontare
              FROM pontajes 
              WHERE angajat_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute([$angajat_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Ore lucrate în ultima lună
$luna_trecuta_start = date('Y-m-01', strtotime('-1 month'));
$luna_trecuta_end = date('Y-m-t', strtotime('-1 month'));
$ore_luna_trecuta = calculeazaOreLucrateAngajat($angajat_id, $luna_trecuta_start, $luna_trecuta_end);

// Ore lucrate în luna curentă
$luna_curenta_start = date('Y-m-01');
$luna_curenta_end = date('Y-m-d');
$ore_luna_curenta = calculeazaOreLucrateAngajat($angajat_id, $luna_curenta_start, $luna_curenta_end);

// Ultimele pontări
$pontaje_sql = "SELECT * FROM pontajes 
                WHERE angajat_id = ? 
                ORDER BY data_pontaj DESC 
                LIMIT 10";
$pontaje_stmt = $conn->prepare($pontaje_sql);
$pontaje_stmt->execute([$angajat_id]);
$ultimele_pontaje = $pontaje_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalii Angajat - Sistem Pontaj</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
        .header { background: #343a40; color: white; padding: 15px; margin-bottom: 20px; }
        .header-content { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        
        /* Card profil */
        .profile-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .profile-header { display: flex; align-items: center; margin-bottom: 30px; }
        .avatar { width: 100px; height: 100px; background: #007bff; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin-right: 30px; }
        .profile-info h1 { margin: 0; color: #333; }
        .profile-info .email { color: #666; font-size: 18px; margin: 5px 0 15px 0; }
        
        /* Statistici */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { margin: 0; color: #666; font-size: 14px; }
        .stat-card .number { font-size: 32px; font-weight: bold; margin: 10px 0; }
        .stat-card.primary { background: #007bff; color: white; }
        .stat-card.primary h3, .stat-card.primary .number { color: white; }
        .stat-card.success { background: #28a745; color: white; }
        .stat-card.warning { background: #ffc107; color: #212529; }
        
        /* Detalii și istoric */
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .details-box, .history-box { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .details-box h2, .history-box h2 { margin-top: 0; color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        
        /* Tabel */
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .table th { background-color: #4CAF50; color: white; }
        .table tr:hover { background-color: #f5f5f5; }
        
        /* Badge-uri */
        .badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-admin { background: #dc3545; color: white; }
        .badge-active { background: #28a745; color: white; }
        .badge-department { background: #17a2b8; color: white; }
        
        /* Butoane */
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        
        .actions-bar { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        
        .info-row { display: flex; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .info-label { flex: 1; font-weight: bold; color: #333; }
        .info-value { flex: 2; color: #666; }
        
        .timeline { position: relative; padding-left: 30px; }
        .timeline-item { position: relative; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .timeline-item:last-child { border-bottom: none; }
        .timeline-item:before { content: ''; position: absolute; left: -30px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #007bff; }
        .timeline-date { color: #666; font-size: 14px; }
        .timeline-content { margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="margin: 0;">👤 Detalii Angajat</h1>
            <div>
                <a href="index.php" style="color: white; margin-left: 15px; text-decoration: none;">← Înapoi la listă</a>
                <a href="../dashboard.php" style="color: white; margin-left: 15px; text-decoration: none;">🏠 Dashboard</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Card profil -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar">
                    <?php echo strtoupper(substr($angajat['nume'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($angajat['nume']); ?>
                        <?php if($angajat['este_admin']): ?>
                            <span class="badge badge-admin">ADMINISTRATOR</span>
                        <?php endif; ?>
                    </h1>
                    <div class="email">📧 <?php echo htmlspecialchars($angajat['email']); ?></div>
                    <div>
                        <?php if($angajat['departament']): ?>
                            <span class="badge badge-department"><?php echo htmlspecialchars($angajat['departament']); ?></span>
                        <?php endif; ?>
                        <span style="color: #666; margin-left: 10px;">
                            📅 Membru din <?php echo date('d.m.Y', strtotime($angajat['data_angajare'])); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="actions-bar">
                <a href="editeaza.php?id=<?php echo $angajat_id; ?>" class="btn btn-warning">✏️ Editează date</a>
                <a href="../pontaje/?angajat_id=<?php echo $angajat_id; ?>" class="btn btn-primary">📅 Vezi toate pontajele</a>
                <a href="../rapoarte/?angajat_id=<?php echo $angajat_id; ?>" class="btn" style="background: #28a745; color: white;">📊 Rapoarte</a>
                <a href="sterge.php?id=<?php echo $angajat_id; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Sigur doriți să ștergeți acest angajat?')">🗑️ Șterge</a>
                <a href="index.php" class="btn" style="background: #6c757d; color: white;">← Înapoi la listă</a>
            </div>
        </div>
        
        <!-- Statistici -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <h3>TOTAL ZILE PONTATE</h3>
                <div class="number"><?php echo $stats['total_zile_pontate'] ?? 0; ?></div>
                <div>din <?php echo calculeazaZileLucratoare($angajat['data_angajare'], date('Y-m-d')); ?> zile lucrătoare</div>
            </div>
            
            <div class="stat-card">
                <h3>ORE LUCRATE (LUNA CURENTĂ)</h3>
                <div class="number"><?php echo number_format($ore_luna_curenta, 1); ?>h</div>
                <div><?php echo date('F Y'); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>ORE LUCRATE (LUNA TREcutĂ)</h3>
                <div class="number"><?php echo number_format($ore_luna_trecuta, 1); ?>h</div>
                <div><?php echo date('F Y', strtotime('-1 month')); ?></div>
            </div>
            
            <div class="stat-card warning">
                <h3>PROGRAM DE LUCRU</h3>
                <div class="number"><?php echo htmlspecialchars($angajat['nume_program'] ?? 'Standard'); ?></div>
                <div><?php echo $angajat['ore_pe_zi'] ?? 8; ?> ore/zi</div>
            </div>
        </div>
        
        <!-- Detalii și Istoric -->
        <div class="details-grid">
            <!-- Informații personale -->
            <div class="details-box">
                <h2>📋 Informații personale</h2>
                
                <div class="info-row">
                    <div class="info-label">ID angajat:</div>
                    <div class="info-value">#<?php echo $angajat['id']; ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Nume complet:</div>
                    <div class="info-value"><?php echo htmlspecialchars($angajat['nume']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?php echo htmlspecialchars($angajat['email']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Departament:</div>
                    <div class="info-value">
                        <?php if($angajat['departament']): ?>
                            <span class="badge badge-department"><?php echo htmlspecialchars($angajat['departament']); ?></span>
                        <?php else: ?>
                            <em style="color: #999;">Nespecificat</em>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Data angajării:</div>
                    <div class="info-value"><?php echo date('d.m.Y', strtotime($angajat['data_angajare'])); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Vechie în firmă:</div>
                    <div class="info-value">
                        <?php
                        $data_angajare = new DateTime($angajat['data_angajare']);
                        $today = new DateTime();
                        $interval = $today->diff($data_angajare);
                        echo $interval->y . ' ani, ' . $interval->m . ' luni, ' . $interval->d . ' zile';
                        ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Rol în sistem:</div>
                    <div class="info-value">
                        <?php if($angajat['este_admin']): ?>
                            <span class="badge badge-admin">Administrator</span>
                            <small style="color: #666; margin-left: 10px;">Acces complet la toate funcțiile</small>
                        <?php else: ?>
                            <span class="badge badge-active">Angajat</span>
                            <small style="color: #666; margin-left: 10px;">Acces limitat la sistem</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Program de lucru:</div>
                    <div class="info-value">
                        <strong><?php echo htmlspecialchars($angajat['nume_program'] ?? 'Standard'); ?></strong>
                        (<?php echo $angajat['ore_pe_zi'] ?? 8; ?> ore/zi)
                    </div>
                </div>
            </div>
            
            <!-- Ultimele activități -->
            <div class="history-box">
                <h2>📅 Ultimele pontări</h2>
                
                <?php if (count($ultimele_pontaje) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data/Ora</th>
                            <th>Tip</th>
                            <th>Acțiune</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($ultimele_pontaje as $pontaj): ?>
                        <tr>
                            <td><?php echo date('d.m.Y H:i', strtotime($pontaj['data_pontaj'])); ?></td>
                            <td>
                                <?php if($pontaj['tip'] == 'intrare'): ?>
                                    <span style="color: #28a745; font-weight: bold;">INTRARE</span>
                                <?php else: ?>
                                    <span style="color: #dc3545; font-weight: bold;">IEȘIRE</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($pontaj['tip'] == 'intrare'): ?>
                                    🟢 A intrat la serviciu
                                <?php else: ?>
                                    🔴 A ieșit de la serviciu
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="../pontaje/?angajat_id=<?php echo $angajat_id; ?>" class="btn btn-sm btn-primary">
                        📋 Vezi toate pontările
                    </a>
                </div>
                
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <h3>📭 Nu există pontări înregistrate</h3>
                    <p>Acest angajat nu a efectuat nicio pontare până acum.</p>
                </div>
                <?php endif; ?>
                
                <!-- Timeline statistici -->
                <div style="margin-top: 30px;">
                    <h3>📊 Cronologie activitate</h3>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-date">
                                <?php echo $stats['prima_pontare'] ? date('d.m.Y', strtotime($stats['prima_pontare'])) : 'N/A'; ?>
                            </div>
                            <div class="timeline-content">
                                <strong>Prima pontare</strong> în sistem
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-date">
                                <?php echo $stats['ultima_pontare'] ? date('d.m.Y H:i', strtotime($stats['ultima_pontare'])) : 'N/A'; ?>
                            </div>
                            <div class="timeline-content">
                                <strong>Ultima pontare</strong> înregistrată
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-date">Total</div>
                            <div class="timeline-content">
                                <strong><?php echo $stats['total_pontaje'] ?? 0; ?> pontări</strong> înregistrate
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Acțiuni rapide -->
        <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 20px;">
            <h2 style="margin-top: 0; color: #333;">⚡ Acțiuni rapide</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="editeaza.php?id=<?php echo $angajat_id; ?>" class="btn btn-warning">✏️ Modifică date angajat</a>
                <a href="../pontaje/adauga.php?angajat_id=<?php echo $angajat_id; ?>" class="btn btn-primary">➕ Adaugă pontare manuală</a>
                <a href="../rapoarte/rapoarte_ore.php?angajat_id=<?php echo $angajat_id; ?>" class="btn" style="background: #28a745; color: white;">📊 Generează raport</a>
                <a href="sterge.php?id=<?php echo $angajat_id; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Sigur doriți să ștergeți acest angajat?')">🗑️ Șterge angajat</a>
                <a href="index.php" class="btn" style="background: #6c757d; color: white;">← Înapoi la lista angajaților</a>
            </div>
        </div>
    </div>
</body>
</html>