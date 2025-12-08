<?php
require_once '../../includes/auth.php';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

checkAccess('view_rapoarte');

$page_title = "Rapoarte Pontaje";
require_once '../../includes/header.php';

// Parametri raport
$an = $_GET['an'] ?? date('Y');
$luna = $_GET['luna'] ?? date('m');
$departament = $_GET['departament'] ?? '';
?>

<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-chart-bar"></i> Rapoarte Pontaje</h1>
    
    <!-- Selector Raport -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Selectare Raport</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label>An</label>
                    <select name="an" class="form-control">
                        <?php for($i = date('Y'); $i >= date('Y')-5; $i--): ?>
                            <option value="<?= $i ?>" <?= $an == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label>Lună</label>
                    <select name="luna" class="form-control">
                        <?php 
                        $luni = [
                            '01' => 'Ianuarie', '02' => 'Februarie', '03' => 'Martie',
                            '04' => 'Aprilie', '05' => 'Mai', '06' => 'Iunie',
                            '07' => 'Iulie', '08' => 'August', '09' => 'Septembrie',
                            '10' => 'Octombrie', '11' => 'Noiembrie', '12' => 'Decembrie'
                        ];
                        foreach($luni as $key => $nume): ?>
                            <option value="<?= $key ?>" <?= $luna == $key ? 'selected' : '' ?>>
                                <?= $nume ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label>Departament</label>
                    <select name="departament" class="form-control">
                        <option value="">Toate departamentele</option>
                        <?php 
                        $depQuery = $conn->query("SELECT DISTINCT departament FROM angajati WHERE departament IS NOT NULL ORDER BY departament");
                        while($dep = $depQuery->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= htmlspecialchars($dep['departament']) ?>" 
                                <?= $departament == $dep['departament'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dep['departament']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-chart-line"></i> Generează Raport
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row">
        <!-- Raport 1: Pontaje pe zile -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pontaje pe Zile - <?= $luni[$luna] ?> <?= $an ?></h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="pontajePeZileChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Raport 2: Distribuție departamente -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Distribuție pe Departamente</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie">
                        <canvas id="departamenteChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Raport 3: Top angajați cu cele mai multe pontări -->
        <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top 10 Angajați - Pontări în <?= $luni[$luna] ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Poziție</th>
                                    <th>Angajat</th>
                                    <th>Departament</th>
                                    <th>Total Pontări</th>
                                    <th>Intrări</th>
                                    <th>Ieșiri</th>
                                    <th>Întârzieri</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Query pentru top angajați
                                $topQuery = "
                                    SELECT 
                                        a.id,
                                        a.nume,
                                        a.prenume,
                                        a.departament,
                                        COUNT(p.id) as total_pontaje,
                                        SUM(CASE WHEN p.tip_pontaj = 'in' THEN 1 ELSE 0 END) as intrari,
                                        SUM(CASE WHEN p.tip_pontaj = 'out' THEN 1 ELSE 0 END) as iesiri,
                                        SUM(CASE WHEN p.status = 'late' THEN 1 ELSE 0 END) as intarzieri
                                    FROM pontaje p
                                    JOIN angajati a ON p.angajat_id = a.id
                                    WHERE YEAR(p.data_pontaj) = ? 
                                    AND MONTH(p.data_pontaj) = ?
                                    " . ($departament ? " AND a.departament = ?" : "") . "
                                    GROUP BY a.id
                                    ORDER BY total_pontaje DESC
                                    LIMIT 10
                                ";
                                
                                $params = [$an, $luna];
                                if($departament) {
                                    $params[] = $departament;
                                }
                                
                                $stmt = $conn->prepare($topQuery);
                                $stmt->execute($params);
                                $topAngajati = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                $position = 1;
                                foreach($topAngajati as $angajat):
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?= $position <= 3 ? ($position == 1 ? 'gold' : ($position == 2 ? 'silver' : 'bronze')) : 'secondary' ?>">
                                            <?= $position ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($angajat['nume'] . ' ' . $angajat['prenume']) ?></td>
                                    <td><?= htmlspecialchars($angajat['departament']) ?></td>
                                    <td><strong><?= $angajat['total_pontaje'] ?></strong></td>
                                    <td><?= $angajat['intrari'] ?></td>
                                    <td><?= $angajat['iesiri'] ?></td>
                                    <td>
                                        <?php if($angajat['intarzieri'] > 0): ?>
                                            <span class="badge bg-danger"><?= $angajat['intarzieri'] ?> întârzieri</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Nicio întârziere</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php $position++; endforeach; ?>
                                
                                <?php if(empty($topAngajati)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Nu există date pentru perioada selectată.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Raport 4: Statistici generale -->
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistici Generale</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        // Statistici generale
                        $statsQuery = "
                            SELECT 
                                COUNT(DISTINCT p.angajat_id) as angajati_activi,
                                COUNT(p.id) as total_pontaje,
                                ROUND(AVG(CASE WHEN p.status = 'late' THEN 1 ELSE 0 END) * 100, 2) as procent_intarzieri,
                                COUNT(DISTINCT DAY(p.data_pontaj)) as zile_cu_pontaje,
                                MIN(p.data_pontaj) as prima_pontare,
                                MAX(p.data_pontaj) as ultima_pontare
                            FROM pontaje p
                            JOIN angajati a ON p.angajat_id = a.id
                            WHERE YEAR(p.data_pontaj) = ? 
                            AND MONTH(p.data_pontaj) = ?
                            " . ($departament ? " AND a.departament = ?" : "");
                        
                        $stmt = $conn->prepare($statsQuery);
                        $stmt->execute($params);
                        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        
                        <div class="col-md-2 text-center">
                            <div class="card border-left-primary shadow py-2">
                                <div class="card-body">
                                    <div class="text-primary">Angajați Activi</div>
                                    <div class="h3"><?= $stats['angajati_activi'] ?? 0 ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2 text-center">
                            <div class="card border-left-success shadow py-2">
                                <div class="card-body">
                                    <div class="text-success">Total Pontări</div>
                                    <div class="h3"><?= $stats['total_pontaje'] ?? 0 ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2 text-center">
                            <div class="card border-left-warning shadow py-2">
                                <div class="card-body">
                                    <div class="text-warning">Întârzieri</div>
                                    <div class="h3"><?= $stats['procent_intarzieri'] ?? 0 ?>%</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 text-center">
                            <div class="card border-left-info shadow py-2">
                                <div class="card-body">
                                    <div class="text-info">Zile cu Pontări</div>
                                    <div class="h3"><?= $stats['zile_cu_pontaje'] ?? 0 ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 text-center">
                            <div class="card border-left-secondary shadow py-2">
                                <div class="card-body">
                                    <div class="text-secondary">Perioadă Acoperită</div>
                                    <div class="h6">
                                        <?php if($stats['prima_pontare']): ?>
                                            <?= date('d.m.Y', strtotime($stats['prima_pontare'])) ?> - 
                                            <?= date('d.m.Y', strtotime($stats['ultima_pontare'])) ?>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date pentru grafice (acestea vor fi populate din PHP prin AJAX într-o implementare reală)
    // Pentru demo, folosim date statice
    
    // Grafic 1: Pontaje pe zile
    const ctx1 = document.getElementById('pontajePeZileChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: ['Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică'],
            datasets: [{
                label: 'Intrări',
                data: [45, 42, 40, 43, 44, 12, 5],
                backgroundColor: 'rgba(54, 162, 235, 0.7)'
            }, {
                label: 'Ieșiri',
                data: [44, 41, 39, 42, 43, 11, 5],
                backgroundColor: 'rgba(255, 206, 86, 0.7)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
    
    // Grafic 2: Distribuție departamente
    const ctx2 = document.getElementById('departamenteChart').getContext('2d');
    new Chart(ctx2, {
        type: 'pie',
        data: {
            labels: ['IT', 'HR', 'Vânzări', 'Contabilitate', 'Management'],
            datasets: [{
                data: [35, 20, 25, 15, 5],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>