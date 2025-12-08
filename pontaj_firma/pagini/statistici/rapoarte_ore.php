<?php
include '../../includes/config.php';
include '../../includes/auth.php';
include '../../includes/functions.php';
checkAuth();
checkAdmin();

// Funcție locală pentru a verifica dacă un angajat a fost deja procesat
function angajatDejaProcesat($angajat_id, &$angajati_procesati) {
    if (in_array($angajat_id, $angajati_procesati)) {
        return true;
    }
    $angajati_procesati[] = $angajat_id;
    return false;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapoarte Ore - Sistem Pontaj</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
        .header { background: #343a40; color: white; padding: 15px; margin-bottom: 20px; }
        .header-content { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .filtre-rapoarte { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        select, input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; margin-right: 10px; }
        .btn:hover { background: #0056b3; }
        .btn-export { background: #28a745; }
        .btn-export:hover { background: #218838; }
        .rezultate-rapoarte { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; }
        .tabel-rapoarte { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .tabel-rapoarte th, .tabel-rapoarte td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        .tabel-rapoarte th { background-color: #4CAF50; color: white; position: sticky; top: 0; }
        .tabel-rapoarte tr:hover { background-color: #f5f5f5; }
        .positive { color: #28a745; font-weight: bold; }
        .negative { color: #dc3545; font-weight: bold; }
        .total-row { background-color: #e9ecef; font-weight: bold; }
        .chart-container { background: white; padding: 20px; border-radius: 10px; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); height: 400px; }
        .empty-message { text-align: center; padding: 40px; color: #666; font-size: 18px; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .note { font-size: 12px; color: #666; margin-top: 5px; font-style: italic; }
        .duplicate-warning { background-color: #ffcc00; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 5px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="margin: 0;">📊 Rapoarte Ore Lucrate</h1>
            <div>
                <span>Bun venit, <?php echo htmlspecialchars($_SESSION['nume']); ?>!</span>
                <span style="margin-left: 10px; background: #dc3545; padding: 2px 8px; border-radius: 10px; font-size: 12px;">ADMIN</span>
                <a href="../../dashboard.php" style="color: white; margin-left: 15px; text-decoration: none;">🏠 Dashboard</a>
                <a href="../../logout.php" style="color: white; margin-left: 15px; text-decoration: none;">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php
        // Validare date
        if (isset($_GET['data_start']) && isset($_GET['data_end'])) {
            if ($_GET['data_start'] > $_GET['data_end']) {
                echo '<div class="alert alert-error">⚠️ Data de început nu poate fi mai mare decât data de sfârșit!</div>';
            }
        }
        ?>
        
        <!-- Formular filtre -->
        <div class="filtre-rapoarte">
            <h2>🔍 Filtre Raport</h2>
            <form method="GET" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="angajat_id">Angajat:</label>
                        <select id="angajat_id" name="angajat_id">
                            <option value="">-- Toți angajații --</option>
                            <?php
                            $angajati_query = $conn->query("SELECT id, nume FROM angajati GROUP BY id ORDER BY nume");
                            $angajati = $angajati_query->fetchAll(PDO::FETCH_ASSOC);
                            foreach($angajati as $angajat) {
                                $selected = (isset($_GET['angajat_id']) && $_GET['angajat_id'] == $angajat['id']) ? 'selected' : '';
                                echo "<option value='{$angajat['id']}' $selected>{$angajat['nume']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="departament">Departament:</label>
                        <select id="departament" name="departament">
                            <option value="">-- Toate departamentele --</option>
                            <?php
                            $dept_query = $conn->query("SELECT DISTINCT departament FROM angajati WHERE departament IS NOT NULL AND departament != '' ORDER BY departament");
                            $departamente = $dept_query->fetchAll(PDO::FETCH_ASSOC);
                            foreach($departamente as $dept) {
                                $selected = (isset($_GET['departament']) && $_GET['departament'] == $dept['departament']) ? 'selected' : '';
                                echo "<option value='{$dept['departament']}' $selected>{$dept['departament']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_start">Perioadă de la:</label>
                        <input type="date" id="data_start" name="data_start" 
                               value="<?php echo isset($_GET['data_start']) ? htmlspecialchars($_GET['data_start']) : date('Y-m-01'); ?>" 
                               max="<?php echo date('Y-m-d'); ?>" required>
                        <div class="note">Prima zi a lunii curente</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_end">Până la:</label>
                        <input type="date" id="data_end" name="data_end" 
                               value="<?php echo isset($_GET['data_end']) ? htmlspecialchars($_GET['data_end']) : date('Y-m-d'); ?>" 
                               max="<?php echo date('Y-m-d'); ?>" required>
                        <div class="note">Astăzi</div>
                    </div>
                </div>
                
                <button type="submit" class="btn">🔍 Generează Raport</button>
                <button type="button" class="btn btn-export" onclick="exportRaport()">📥 Export Excel</button>
                <button type="button" class="btn" onclick="resetFiltre()">🔄 Resetare Filtre</button>
            </form>
        </div>

        <?php
        // Generare raport dacă există filtre
        if (isset($_GET['data_start']) && isset($_GET['data_end']) && $_GET['data_start'] <= $_GET['data_end']) {
            
            $angajat_id = $_GET['angajat_id'] ?? '';
            $departament = $_GET['departament'] ?? '';
            $data_start = $_GET['data_start'] ?? date('Y-m-01');
            $data_end = $_GET['data_end'] ?? date('Y-m-d');
            
            // Query SIMPLU și SIGUR pentru a obține angajații
            $sql = "SELECT DISTINCT
                    a.id,
                    a.nume,
                    a.email,
                    a.departament,
                    a.tip_program_id,
                    tp.nume_program,
                    tp.ore_pe_zi
                FROM angajati a
                LEFT JOIN tipuri_program tp ON a.tip_program_id = tp.id
                WHERE 1=1";
            
            $params = [];
            
            if ($angajat_id) {
                $sql .= " AND a.id = ?";
                $params[] = $angajat_id;
            }
            
            if ($departament) {
                $sql .= " AND a.departament = ?";
                $params[] = $departament;
            }
            
            $sql .= " ORDER BY a.id";
            
            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $rezultate_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Verificare STRICTĂ pentru duplicate
                $angajati_procesati = [];
                $rezultate = [];
                $duplicate_ids = [];
                
                foreach($rezultate_raw as $row) {
                    if (in_array($row['id'], $angajati_procesati)) {
                        $duplicate_ids[] = $row['id'];
                        continue;
                    }
                    
                    $angajati_procesati[] = $row['id'];
                    $rezultate[] = $row;
                }
                
                // Mesaj de avertizare dacă s-au găsit duplicate
                if (!empty($duplicate_ids)) {
                    $unique_duplicates = array_unique($duplicate_ids);
                    echo '<div class="alert alert-warning">⚠️ <strong>Atenție!</strong> Au fost eliminate ' . 
                         count($duplicate_ids) . ' duplicate pentru angajații cu ID: ' . 
                         implode(', ', $unique_duplicates) . '. Verifică query-urile din funcțiile de calcul.</div>';
                }
                
                if (count($rezultate) > 0):
                    // Calcule totale
                    $total_zile_pontate = 0;
                    $total_ore_lucrate = 0;
                    $total_ore_planificate = 0;
                    $total_ore_suplimentare = 0;
                    $chart_labels = [];
                    $chart_ore = [];
                    $chart_ore_plan = [];
                    $index_afisare = 1;
                    
        ?>
        
        <!-- Rezultate raport -->
        <div class="rezultate-rapoarte">
            <h2>📋 Rezultate Raport (<?php echo date('d.m.Y', strtotime($data_start)); ?> - <?php echo date('d.m.Y', strtotime($data_end)); ?>)</h2>
            
            <div class="alert alert-info">
                <strong>📊 Statistici:</strong> 
                <?php 
                $zile_lucratoare = calculeazaZileLucratoare($data_start, $data_end);
                echo "Angajați unici: <strong>" . count($rezultate) . "</strong> | ";
                echo "Zile lucrătoare: <strong>{$zile_lucratoare}</strong>";
                if (!empty($duplicate_ids)) {
                    echo " | <span class='duplicate-warning'>Duplicate eliminate: " . count($duplicate_ids) . "</span>";
                }
                ?>
            </div>
            
            <table class="tabel-rapoarte">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Angajat (ID)</th>
                        <th>Departament</th>
                        <th>Program</th>
                        <th>Zile Pontate</th>
                        <th>Ore Lucrate</th>
                        <th>Ore Planificate</th>
                        <th>Diferență</th>
                        <th>Ore Suplimentare</th>
                        <th>Procentaj</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $angajati_afisati = [];
                    foreach($rezultate as $row): 
                        // Verifică dublu că nu am afișat deja acest angajat
                        if (in_array($row['id'], $angajati_afisati)) {
                            continue;
                        }
                        $angajati_afisati[] = $row['id'];
                        
                        // Calcule pentru fiecare angajat
                        $zile_pontate = calculeazaZilePontate($row['id'], $data_start, $data_end);
                        $ore_lucrate = calculeazaOreLucrateAngajat($row['id'], $data_start, $data_end);
                        $ore_planificate = calculeazaOrePlanificate($row['id'], $data_start, $data_end);
                        $diferenta = $ore_lucrate - $ore_planificate;
                        $ore_suplimentare = max(0, $diferenta);
                        $procentaj = $ore_planificate > 0 ? ($ore_lucrate / $ore_planificate) * 100 : 0;
                        
                        // Actualizează totale
                        $total_zile_pontate += $zile_pontate;
                        $total_ore_lucrate += $ore_lucrate;
                        $total_ore_planificate += $ore_planificate;
                        $total_ore_suplimentare += $ore_suplimentare;
                        
                        // Date pentru grafic
                        $chart_labels[] = $row['nume'] . ' (ID:' . $row['id'] . ')';
                        $chart_ore[] = round($ore_lucrate, 1);
                        $chart_ore_plan[] = round($ore_planificate, 1);
                    ?>
                    <tr>
                        <td><?php echo $index_afisare++; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['nume']); ?></strong><br>
                            <small style="color: #666; font-size: 11px;">ID: <?php echo $row['id']; ?> | Email: <?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['departament'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['nume_program'] ?? '8 ore'); ?><br>
                            <small style="color: #666; font-size: 11px;"><?php echo $row['ore_pe_zi'] ?? '8'; ?>h/zi</small>
                        </td>
                        <td><?php echo $zile_pontate; ?></td>
                        <td><strong><?php echo number_format($ore_lucrate, 1); ?>h</strong></td>
                        <td><?php echo number_format($ore_planificate, 1); ?>h</td>
                        <td class="<?php echo $diferenta >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $diferenta >= 0 ? '+' : ''; ?><?php echo number_format($diferenta, 1); ?>h
                        </td>
                        <td class="positive"><?php echo number_format($ore_suplimentare, 1); ?>h</td>
                        <td><strong><?php echo number_format($procentaj, 1); ?>%</strong></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- Rand total -->
                    <tr class="total-row">
                        <td colspan="4"><strong>TOTAL</strong></td>
                        <td><strong><?php echo $total_zile_pontate; ?></strong></td>
                        <td><strong><?php echo number_format($total_ore_lucrate, 1); ?>h</strong></td>
                        <td><strong><?php echo number_format($total_ore_planificate, 1); ?>h</strong></td>
                        <td><strong class="<?php echo ($total_ore_lucrate - $total_ore_planificate) >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($total_ore_lucrate - $total_ore_planificate) >= 0 ? '+' : ''; ?>
                            <?php echo number_format($total_ore_lucrate - $total_ore_planificate, 1); ?>h
                        </strong></td>
                        <td><strong class="positive"><?php echo number_format($total_ore_suplimentare, 1); ?>h</strong></td>
                        <td><strong>
                            <?php 
                            if ($total_ore_planificate > 0) {
                                $procentaj_total = ($total_ore_lucrate / $total_ore_planificate) * 100;
                                echo number_format($procentaj_total, 1);
                            } else {
                                echo '0';
                            }
                            ?>%
                        </strong></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if (count($rezultate) > 1): ?>
            <!-- Grafic -->
            <div class="chart-container">
                <h3>📈 Distribuție Ore pe Angajat</h3>
                <canvas id="oreChart"></canvas>
            </div>
            
            <script>
            const ctx = document.getElementById('oreChart').getContext('2d');
            const oreChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [{
                        label: 'Ore Lucrate',
                        data: <?php echo json_encode($chart_ore); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Ore' }
                        },
                        x: {
                            title: { display: true, text: 'Angajați' },
                            ticks: { maxRotation: 45, minRotation: 45 }
                        }
                    },
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' ore';
                                }
                            }
                        }
                    }
                }
            });
            </script>
            <?php endif; ?>
            
        </div>
        
        <?php else: ?>
        <div class="empty-message">
            <h3>📭 Nu s-au găsit angajați pentru filtrele selectate</h3>
            <p>Încearcă să schimbi filtrele sau să selectezi o altă perioadă.</p>
        </div>
        <?php endif;
                
            } catch (Exception $e) {
                echo '<div class="alert alert-error">❌ Eroare la generarea raportului: ' . htmlspecialchars($e->getMessage()) . '</div>';
                error_log("Eroare raport: " . $e->getMessage());
            }
        } ?>
    </div>

    <script>
    function exportRaport() {
        // Crează un tabel pentru export
        const table = document.querySelector('.tabel-rapoarte');
        if (!table) {
            alert('Nu există date de exportat!');
            return;
        }
        
        const rows = table.querySelectorAll('tr');
        let csv = [];
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('th, td');
            const rowData = Array.from(cells).map(cell => {
                let text = cell.innerText;
                // Elimină liniile mici cu ID și email
                text = text.replace(/ID:\s*\d+\s*\|.*/g, '').trim();
                text = text.replace(/\n/g, ' ').trim();
                return `"${text}"`;
            });
            csv.push(rowData.join(','));
        });
        
        // Descarcă fișierul CSV
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', 'raport_ore_' + new Date().toISOString().slice(0,10) + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        alert('Raportul a fost exportat cu succes!');
    }
    
    function resetFiltre() {
        window.location.href = 'rapoarte_ore.php';
    }
    
    // Setează data maximă pentru inputurile de dată
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('data_start').max = today;
        document.getElementById('data_end').max = today;
        
        // Validare în timp real
        document.getElementById('data_start').addEventListener('change', function() {
            const endDate = document.getElementById('data_end');
            if (this.value > endDate.value) {
                alert('Data de început nu poate fi după data de sfârșit!');
                this.value = endDate.value;
            }
        });
        
        document.getElementById('data_end').addEventListener('change', function() {
            const startDate = document.getElementById('data_start');
            if (this.value < startDate.value) {
                alert('Data de sfârșit nu poate fi înaintea datei de început!');
                this.value = startDate.value;
            }
        });
    });
    </script>
</body>
</html>