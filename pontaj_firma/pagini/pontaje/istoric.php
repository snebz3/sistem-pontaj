<?php
require_once '../../includes/auth.php';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verifică permisiuni
checkAccess('view_pontaje');

// Preluare parametri filtre din GET
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$angajat_id = $_GET['angajat_id'] ?? '';
$departament_filter = $_GET['departament'] ?? '';
$tip_pontaj = $_GET['tip_pontaj'] ?? '';

// Mapare pentru tip pontaj (dacă folosești 'in'/'out' în interfață)
$tip_mapping = [
    'in' => 'intrare',
    'out' => 'iesire'
];
if (isset($tip_mapping[$tip_pontaj])) {
    $tip_pontaj_real = $tip_mapping[$tip_pontaj];
} else {
    $tip_pontaj_real = $tip_pontaj;
}

// Construire query adaptată la structura ta REALĂ
$query = "SELECT p.*, a.nume as nume_complet, a.email, a.departament 
          FROM pontaje p
          JOIN angajati a ON p.angajat_id = a.id
          WHERE p.data_pontaj BETWEEN ? AND ?";

$params = [$start_date, $end_date . ' 23:59:59'];

if (!empty($angajat_id)) {
    $query .= " AND p.angajat_id = ?";
    $params[] = $angajat_id;
}

if (!empty($departament_filter)) {
    $query .= " AND a.departament = ?";
    $params[] = $departament_filter;
}

if (!empty($tip_pontaj_real)) {
    $query .= " AND p.tip = ?";
    $params[] = $tip_pontaj_real;
}

$query .= " ORDER BY p.data_pontaj DESC LIMIT 1000";

// Execută query
try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $pontaje = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div class='alert alert-danger'><strong>Eroare SQL:</strong> " . $e->getMessage() . 
        "<br><strong>Query:</strong> " . htmlspecialchars($query) . "</div>");
}

// Preluare date pentru dropdown-uri
try {
    // Angajați pentru dropdown
    $angajati = $conn->query("SELECT id, nume FROM angajati ORDER BY nume")->fetchAll();
    
    // Departamente unice din coloana departament
    $departamente = $conn->query("SELECT DISTINCT departament FROM angajati WHERE departament IS NOT NULL AND departament != '' ORDER BY departament")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    echo "<div class='alert alert-warning'>Avertisment: " . $e->getMessage() . "</div>";
    $angajati = [];
    $departamente = [];
}

$page_title = "Istoric Pontaje";
require_once '../../includes/header.php';
?>

<style>
/* ===== CSS SPECIFIC PAGINA ISTORIC PONTAJE ===== */

/* Carduri statistici */
.card-stat {
    border-left: 4px solid #4e73df;
    transition: all 0.3s ease;
    border-radius: 8px;
    overflow: hidden;
}

.card-stat:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.card-stat-primary {
    border-left-color: #4e73df;
}

.card-stat-success {
    border-left-color: #1cc88a;
}

.card-stat-warning {
    border-left-color: #f6c23e;
}

.card-stat-info {
    border-left-color: #36b9cc;
}

/* Tabel personalizat */
.table-pontaje {
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    background: white;
}

.table-pontaje thead {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
}

.table-pontaje thead th {
    border: none;
    padding: 16px 12px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 0.5px;
    border-right: 1px solid rgba(255,255,255,0.1);
}

.table-pontaje thead th:last-child {
    border-right: none;
}

.table-pontaje tbody tr {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.table-pontaje tbody tr:hover {
    background-color: #f8f9fc;
    border-left-color: #4e73df;
    transform: translateX(2px);
}

.table-pontaje tbody td {
    padding: 14px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #e3e6f0;
    border-right: 1px solid #e3e6f0;
}

.table-pontaje tbody td:last-child {
    border-right: none;
}

.table-pontaje tbody tr:last-child td {
    border-bottom: none;
}

/* Badge-uri personalizate */
.badge-intrare {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 12px;
    box-shadow: 0 2px 4px rgba(28, 200, 138, 0.2);
    display: inline-block;
}

.badge-iesire {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
    color: #212529;
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 12px;
    box-shadow: 0 2px 4px rgba(246, 194, 62, 0.2);
    display: inline-block;
}

.badge-departament {
    background-color: #e3e6f0;
    color: #5a5c69;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 500;
    display: inline-block;
}

/* Status badges */
.badge-status-on_time {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 500;
}

.badge-status-late {
    background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 500;
}

.badge-status-early {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
    color: #212529;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 500;
}

.badge-status-overtime {
    background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 500;
}

/* Butoane personalizate */
.btn-filter {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
    color: white;
}

.btn-reset {
    background-color: #f8f9fc;
    color: #5a5c69;
    border: 1px solid #e3e6f0;
    padding: 10px 24px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-reset:hover {
    background-color: #e3e6f0;
    color: #5a5c69;
    border-color: #d1d3e2;
}

/* Card filtre */
.card-filtre {
    border: 1px solid #e3e6f0;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    background: white;
    overflow: hidden;
}

.card-filtre .card-header {
    background: linear-gradient(135deg, #f8f9fc 0%, #e3e6f0 100%);
    border-bottom: 1px solid #e3e6f0;
    padding: 18px 24px;
    border-radius: 12px 12px 0 0 !important;
}

.card-filtre .card-header h6 {
    color: #4e73df;
    font-weight: 600;
    font-size: 16px;
    margin: 0;
}

.card-filtre .card-body {
    padding: 24px;
}

/* Form controls personalizate */
.form-control-custom {
    border: 1px solid #d1d3e2;
    border-radius: 8px;
    padding: 11px 14px;
    font-size: 14px;
    transition: all 0.3s ease;
    background-color: white;
}

.form-control-custom:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    outline: none;
}

.select-custom {
    border: 1px solid #d1d3e2;
    border-radius: 8px;
    padding: 11px 14px;
    font-size: 14px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%235a5c69' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 12px;
    padding-right: 40px;
    appearance: none;
    background-color: white;
}

.select-custom:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    outline: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-stat {
        margin-bottom: 20px;
    }
    
    .table-pontaje {
        font-size: 13px;
    }
    
    .table-pontaje thead th,
    .table-pontaje tbody td {
        padding: 10px 8px;
    }
    
    .btn-filter, .btn-reset {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .card-filtre .card-body {
        padding: 16px;
    }
}

/* Animatie pentru încărcare */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.5s ease forwards;
}

/* Dispozitiv icons */
.device-icon {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background-color: #f8f9fc;
    border-radius: 15px;
    font-size: 12px;
    color: #5a5c69;
    border: 1px solid #e3e6f0;
}

.device-icon i {
    color: #4e73df;
}

/* Butoane acțiuni */
.btn-action {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    margin: 2px;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.btn-action:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.btn-view {
    background: linear-gradient(135deg, rgba(78, 115, 223, 0.1) 0%, rgba(78, 115, 223, 0.05) 100%);
    color: #4e73df;
    border: 1px solid rgba(78, 115, 223, 0.2);
}

.btn-view:hover {
    background: linear-gradient(135deg, rgba(78, 115, 223, 0.2) 0%, rgba(78, 115, 223, 0.1) 100%);
    color: #224abe;
}

.btn-edit {
    background: linear-gradient(135deg, rgba(246, 194, 62, 0.1) 0%, rgba(246, 194, 62, 0.05) 100%);
    color: #f6c23e;
    border: 1px solid rgba(246, 194, 62, 0.2);
}

.btn-edit:hover {
    background: linear-gradient(135deg, rgba(246, 194, 62, 0.2) 0%, rgba(246, 194, 62, 0.1) 100%);
    color: #dda20a;
}

/* No results message */
.no-results {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
    background-color: #f8f9fc;
    border-radius: 10px;
    margin: 20px 0;
}

.no-results i {
    font-size: 56px;
    margin-bottom: 20px;
    color: #e3e6f0;
}

.no-results h4 {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 10px;
}

.no-results p {
    font-size: 14px;
    color: #858796;
}

/* Export buttons */
.btn-export {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-export:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(28, 200, 138, 0.3);
    color: white;
}

.btn-print {
    background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 10px;
}

.btn-print:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
    color: white;
}

/* Page title */
.page-title {
    color: #4e73df;
    font-weight: 600;
    margin-bottom: 5px;
}

.page-title i {
    margin-right: 10px;
}

.page-subtitle {
    color: #858796;
    font-size: 14px;
    margin-bottom: 30px;
}

/* Alert custom */
.alert-custom {
    border-radius: 10px;
    border: none;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

/* Table actions */
.table-actions {
    display: flex;
    gap: 6px;
    justify-content: center;
}

/* Angajat info */
.angajat-info {
    line-height: 1.4;
}

.angajat-nume {
    font-weight: 500;
    color: #4e73df;
}

.angajat-email {
    font-size: 12px;
    color: #858796;
}

/* Timestamp */
.timestamp {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: #5a5c69;
    background-color: #f8f9fc;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
}
</style>

<div class="container-fluid fade-in">
    <!-- Titlu și butoane acțiune -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-history"></i> Istoric Pontaje</h1>
            <p class="page-subtitle">Vizualizează și filtrează toate pontajele din sistem</p>
        </div>
        <div>
            <button class="btn btn-export" onclick="exportExcel()">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- Card Filtre -->
    <div class="card card-filtre mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-filter mr-2"></i>Filtre Avansate</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" id="filterForm">
                <div class="row g-3">
                    <!-- Perioadă -->
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Data început</label>
                        <input type="date" class="form-control form-control-custom" name="start_date" 
                               value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Data sfârșit</label>
                        <input type="date" class="form-control form-control-custom" name="end_date" 
                               value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    
                    <!-- Angajat -->
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Angajat</label>
                        <select class="form-select select-custom" name="angajat_id">
                            <option value="">Toți angajații</option>
                            <?php foreach ($angajati as $angajat): ?>
                                <option value="<?= $angajat['id'] ?>" 
                                    <?= ($angajat_id == $angajat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($angajat['nume']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Departament -->
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Departament</label>
                        <select class="form-select select-custom" name="departament">
                            <option value="">Toate departamentele</option>
                            <?php foreach ($departamente as $dep): ?>
                                <option value="<?= htmlspecialchars($dep) ?>" 
                                    <?= ($departament_filter == $dep) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dep) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Tip Pontaj -->
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Tip Pontaj</label>
                        <select class="form-select select-custom" name="tip_pontaj">
                            <option value="">Toate tipurile</option>
                            <option value="intrare" <?= ($tip_pontaj == 'intrare') ? 'selected' : '' ?>>Intrare</option>
                            <option value="iesire" <?= ($tip_pontaj == 'iesire') ? 'selected' : '' ?>>Ieșire</option>
                        </select>
                    </div>
                </div>
                
                <!-- Butoane acțiune filtre -->
                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-filter">
                        <i class="fas fa-filter mr-2"></i> Aplică Filtre
                    </button>
                    <a href="istoric.php" class="btn btn-reset">
                        <i class="fas fa-times mr-2"></i> Resetează Filtre
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Card Statistici Rapide -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-stat card-stat-primary shadow h-100 py-3">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Pontaje</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format(count($pontaje), 0, ',', '.') ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span>în perioada selectată</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stat card-stat-success shadow h-100 py-3">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Intrări</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format(count(array_filter($pontaje, fn($p) => $p['tip'] == 'intrare')), 0, ',', '.') ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <?php if (count($pontaje) > 0): ?>
                                    <span><?= round(count(array_filter($pontaje, fn($p) => $p['tip'] == 'intrare')) / count($pontaje) * 100, 1) ?>% din total</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stat card-stat-warning shadow h-100 py-3">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Ieșiri</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format(count(array_filter($pontaje, fn($p) => $p['tip'] == 'iesire')), 0, ',', '.') ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <?php if (count($pontaje) > 0): ?>
                                    <span><?= round(count(array_filter($pontaje, fn($p) => $p['tip'] == 'iesire')) / count($pontaje) * 100, 1) ?>% din total</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sign-out-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-stat card-stat-info shadow h-100 py-3">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Angajați activi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $angajatiUnici = [];
                                foreach ($pontaje as $p) {
                                    $angajatiUnici[$p['angajat_id']] = true;
                                }
                                echo number_format(count($angajatiUnici), 0, ',', '.');
                                ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span>cu pontaje în perioadă</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Tabel Pontaje -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list mr-2"></i>Lista Pontaje</h6>
            <div>
                <span class="badge bg-primary rounded-pill px-3 py-2">
                    <i class="fas fa-database mr-1"></i> <?= number_format(count($pontaje), 0, ',', '.') ?> înregistrări
                </span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($pontaje)): ?>
                <div class="no-results">
                    <i class="fas fa-inbox"></i>
                    <h4>Nu există pontaje pentru filtrele selectate</h4>
                    <p>Încearcă să modifici filtrele sau să extinzi perioada de timp</p>
                    <a href="istoric.php" class="btn btn-primary mt-3">
                        <i class="fas fa-redo mr-2"></i>Resetează Filtrele
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-pontaje" id="pontajeTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Data/Ora</th>
                                <th>Angajat</th>
                                <th>Departament</th>
                                <th>Tip</th>
                                <th>Dispozitiv</th>
                                <th>Status</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pontaje as $pontaj): ?>
                                <tr>
                                    <td>
                                        <span class="timestamp">
                                            <?= date('d.m.Y', strtotime($pontaj['data_pontaj'])) ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            <?= date('H:i:s', strtotime($pontaj['data_pontaj'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="angajat-info">
                                            <div class="angajat-nume">
                                                <?= htmlspecialchars($pontaj['nume_complet']) ?>
                                            </div>
                                            <?php if (!empty($pontaj['email'])): ?>
                                                <div class="angajat-email">
                                                    <?= htmlspecialchars($pontaj['email']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($pontaj['departament'])): ?>
                                            <span class="badge-departament">
                                                <i class="fas fa-building mr-1"></i>
                                                <?= htmlspecialchars($pontaj['departament']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pontaj['tip'] == 'intrare'): ?>
                                            <span class="badge-intrare">
                                                <i class="fas fa-sign-in-alt mr-1"></i>
                                                INTRARE
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-iesire">
                                                <i class="fas fa-sign-out-alt mr-1"></i>
                                                IEȘIRE
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $device = 'web';
                                        $deviceIcons = [
                                            'web' => 'fas fa-desktop',
                                            'mobile' => 'fas fa-mobile-alt',
                                            'biometric' => 'fas fa-fingerprint'
                                        ];
                                        ?>
                                        <div class="device-icon">
                                            <i class="<?= $deviceIcons[$device] ?>"></i>
                                            <span>Web</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        // Calculăm status-ul pe baza orei (deoarece nu ai coloană status)
                                        $ora_pontaj = date('H:i', strtotime($pontaj['data_pontaj']));
                                        $status = 'on_time';
                                        
                                        if ($pontaj['tip'] == 'intrare' && $ora_pontaj > '09:00') {
                                            $status = 'late';
                                        } elseif ($pontaj['tip'] == 'iesire' && $ora_pontaj < '17:00') {
                                            $status = 'early';
                                        } elseif ($pontaj['tip'] == 'iesire' && $ora_pontaj > '18:00') {
                                            $status = 'overtime';
                                        }
                                        
                                        $statusLabels = [
                                            'on_time' => 'La timp',
                                            'late' => 'Întârziat',
                                            'early' => 'Ieșire devreme',
                                            'overtime' => 'Overtime'
                                        ];
                                        ?>
                                        <span class="badge-status-<?= $status ?>">
                                            <?php if ($status == 'late'): ?>
                                                <i class="fas fa-clock mr-1"></i>
                                            <?php elseif ($status == 'early'): ?>
                                                <i class="fas fa-running mr-1"></i>
                                            <?php elseif ($status == 'overtime'): ?>
                                                <i class="fas fa-business-time mr-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-check-circle mr-1"></i>
                                            <?php endif; ?>
                                            <?= $statusLabels[$status] ?? $status ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="btn btn-action btn-view" title="Detalii" 
                                                    onclick="showDetails(<?= $pontaj['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (hasPermission('edit_pontaje')): ?>
                                                <button class="btn btn-action btn-edit" title="Editează"
                                                        onclick="editPontaj(<?= $pontaj['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination info -->
                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        <small>Se afișează <?= count($pontaje) ?> pontaje</small>
                    </div>
                    <div>
                        <button class="btn btn-export btn-sm" onclick="exportExcel()">
                            <i class="fas fa-file-excel mr-2"></i>Exportă datele
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<!-- Script-uri specifice -->
<script src="/js/pontaje.js"></script>
<script>
// Funcții JavaScript
function exportExcel() {
    // Păstrăm filtrele curente
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    // Redirecționare către pagina de export
    window.location.href = 'export.php?' + params.toString() + '&format=excel';
}

function showDetails(pontajId) {
    // Modal cu detalii pontaj
    Swal.fire({
        title: 'Detalii Pontaj',
        html: `Se încarcă detaliile pentru pontajul ID: ${pontajId}...`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'OK',
        cancelButtonText: 'Închide'
    });
}

function editPontaj(pontajId) {
    // Editare pontaj
    Swal.fire({
        title: 'Editare Pontaj',
        text: `Funcționalitatea de editare pentru pontajul ID: ${pontajId} va fi disponibilă în curând.`,
        icon: 'warning',
        confirmButtonText: 'OK'
    });
}

// Setare date implicite (ultimele 7 zile) dacă nu sunt setate
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.querySelector('input[name="start_date"]');
    const endDateInput = document.querySelector('input[name="end_date"]');
    
    if (!startDateInput.value) {
        const lastWeek = new Date();
        lastWeek.setDate(lastWeek.getDate() - 7);
        startDateInput.value = lastWeek.toISOString().split('T')[0];
    }
    
    if (!endDateInput.value) {
        endDateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // Adaugă tooltip-uri pentru butoanele de acțiune
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>