<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../../includes/config.php';

// Verifică dacă utilizatorul este logat și admin
if (!isset($_SESSION['angajat_id']) || !$_SESSION['este_admin']) {
    header('Location: ../../index.php');
    exit();
}

// Înlocuiește include-urile cu căile corecte
$header_path = __DIR__ . '/../../header.php';
$footer_path = __DIR__ . '/../../footer.php';

if (!file_exists($header_path)) {
    // Dacă nu găsește header-ul, afișează HTML simplu
    $page_title = "Export Rapoarte PDF";
    ?>
    <!DOCTYPE html>
    <html lang="ro">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $page_title; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f4f4f4;
                padding: 20px;
            }
            .navbar-custom {
                background: #343a40;
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .card {
                border-radius: 10px;
                border: none;
                margin-bottom: 20px;
                transition: transform 0.3s;
            }
            .card:hover {
                transform: translateY(-5px);
            }
            .card-header {
                border-radius: 10px 10px 0 0 !important;
                font-weight: bold;
            }
            .btn {
                border-radius: 5px;
                padding: 8px 20px;
                font-weight: 600;
                transition: all 0.3s;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            .btn-back {
                background: #6c757d;
                color: white;
                border: none;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .btn-back:hover {
                background: #5a6268;
                color: white;
            }
            .btn-danger { background: #dc3545; }
            .btn-primary { background: #007bff; }
            .btn-success { background: #28a745; }
            .btn-danger:hover { background: #c82333; }
            .btn-primary:hover { background: #0056b3; }
            .btn-success:hover { background: #218838; }
            .fixed-top-btn {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
            }
            @media (max-width: 768px) {
                .fixed-top-btn {
                    position: relative;
                    top: auto;
                    right: auto;
                    margin-bottom: 15px;
                }
                body {
                    padding: 15px;
                }
            }
            .form-select, .form-control {
                border-radius: 5px;
                border: 1px solid #ced4da;
                padding: 10px;
            }
            .alert-info {
                border-radius: 10px;
                border-left: 4px solid #17a2b8;
            }
            .note-box {
                background: #f8f9fa;
                border-left: 4px solid #6c757d;
                padding: 15px;
                margin: 20px 0;
                border-radius: 5px;
                font-size: 14px;
                color: #495057;
            }
        </style>
    </head>
    <body>
        <!-- Buton fixat pentru înapoi -->
        <div class="fixed-top-btn d-none d-md-block">
            <a href="../../dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
        
        <!-- Navbar cu titlu -->
        <div class="navbar-custom">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="fas fa-file-pdf"></i> Export Rapoarte PDF
                </h2>
                <div>
                    <span style="margin-right: 15px;">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nume'] ?? 'Admin'); ?>
                    </span>
                    <span class="badge bg-danger">Admin</span>
                </div>
            </div>
        </div>
    <?php
} else {
    $page_title = "Export Rapoarte PDF";
    include $header_path;
    ?>
    <!-- Buton fixat pentru înapoi (doar dacă nu ai header fix) -->
    <div class="fixed-top-btn d-none d-md-block">
        <a href="../../dashboard.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>
    <?php
}
?>

<div class="container">
    <!-- Buton înapoi pentru mobile -->
    <div class="d-block d-md-none mb-4">
        <a href="../../dashboard.php" class="btn btn-back w-100">
            <i class="fas fa-arrow-left"></i> Înapoi la Dashboard
        </a>
    </div>

    <!-- Titlu principal -->
    <div class="text-center mb-4">
        <h1 class="display-5 fw-bold text-primary">
            <i class="fas fa-file-pdf me-2"></i>Export Rapoarte PDF
        </h1>
        <p class="lead text-muted">Generează rapoarte profesionale în format PDF pentru întârzieri, pontaje și ore lucrate</p>
    </div>

    <!-- Card-uri cu formulare -->
    <div class="row mt-4">
        <div class="col-md-6">
            <!-- Card pentru Raport Întârzieri -->
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Raport Întârzieri</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Generează raport cu toate întârzierile calculate dinamic după programul fiecărui angajat.</p>
                    
                    <form action="genereaza_pdf.php" method="POST" target="_blank">
                        <input type="hidden" name="tip_raport" value="intarzieri">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Perioada raport:</label>
                            <select name="perioada" class="form-select" required>
                                <option value="">-- Selectează perioada --</option>
                                <option value="azi">Astăzi</option>
                                <option value="saptamana">Săptămâna aceasta</option>
                                <option value="luna">Luna aceasta</option>
                                <option value="personalizat">Perioadă personalizată</option>
                            </select>
                        </div>
                        
                        <div class="row mb-3" id="perioada-personalizata" style="display: none;">
                            <div class="col-md-6">
                                <label class="form-label">Data început:</label>
                                <input type="date" name="data_start" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data sfârșit:</label>
                                <input type="date" name="data_end" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Filtru angajat:</label>
                            <select name="angajat_id" class="form-select">
                                <option value="0">Toți angajații</option>
                                <?php
                                $sql = "SELECT id, nume FROM angajati WHERE este_admin = 0 ORDER BY nume";
                                $stmt = $conn->prepare($sql);
                                $stmt->execute();
                                $angajati = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($angajati as $angajat) {
                                    echo '<option value="' . $angajat['id'] . '">' . htmlspecialchars($angajat['nume']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Format sortare:</label>
                            <select name="sortare" class="form-select">
                                <option value="data_desc">Dată (cele mai recente)</option>
                                <option value="data_asc">Dată (cele mai vechi)</option>
                                <option value="minute_desc">Minute întârziere (cele mai multe)</option>
                                <option value="minute_asc">Minute întârziere (cele mai puține)</option>
                                <option value="nume_asc">Nume angajat (A-Z)</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-download me-2"></i> Generează PDF Întârzieri
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i> 
                        Sistemul calculează întârzierile dinamic după programul specific fiecărui angajat.
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Card pentru Raport Pontaje -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-fingerprint"></i> Raport Pontaje</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Generează raport cu toate pontajele și verifică automat dacă au fost cu întârziere.</p>
                    
                    <form action="genereaza_pdf.php" method="POST" target="_blank">
                        <input type="hidden" name="tip_raport" value="pontaje">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Perioada raport:</label>
                            <select name="perioada" class="form-select" required>
                                <option value="">-- Selectează perioada --</option>
                                <option value="azi">Astăzi</option>
                                <option value="saptamana">Săptămâna aceasta</option>
                                <option value="luna">Luna aceasta</option>
                                <option value="personalizat">Perioadă personalizată</option>
                            </select>
                        </div>
                        
                        <div class="row mb-3" id="perioada-personalizata-pontaje" style="display: none;">
                            <div class="col-md-6">
                                <label class="form-label">Data început:</label>
                                <input type="date" name="data_start" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data sfârșit:</label>
                                <input type="date" name="data_end" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Filtru angajat:</label>
                            <select name="angajat_id" class="form-select">
                                <option value="0">Toți angajații</option>
                                <?php
                                foreach ($angajati as $angajat) {
                                    echo '<option value="' . $angajat['id'] . '">' . htmlspecialchars($angajat['nume']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tip pontaje:</label>
                            <select name="tip_pontaj" class="form-select">
                                <option value="toate">Toate pontajele</option>
                                <option value="intrare">Doar intrări</option>
                                <option value="iesire">Doar ieșiri</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-download me-2"></i> Generează PDF Pontaje
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i> 
                        Pentru intrări, raportul indică automat dacă au fost cu întârziere.
                    </small>
                </div>
            </div>
            
            <!-- Card pentru Raport Ore Lucrate -->
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Raport Ore Lucrate</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Generează raport cu orele lucrate, ore suplimentare și statistici lunare.</p>
                    
                    <form action="genereaza_pdf.php" method="POST" target="_blank">
                        <input type="hidden" name="tip_raport" value="ore">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Luna:</label>
                            <select name="luna" class="form-select" required>
                                <?php
                                for ($i = 1; $i <= 12; $i++) {
                                    $selected = ($i == date('n')) ? 'selected' : '';
                                    echo '<option value="' . $i . '" ' . $selected . '>' . date('F', mktime(0, 0, 0, $i, 1)) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">An:</label>
                            <select name="an" class="form-select" required>
                                <?php
                                $current_year = date('Y');
                                for ($i = $current_year - 2; $i <= $current_year + 1; $i++) {
                                    $selected = ($i == $current_year) ? 'selected' : '';
                                    echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-download me-2"></i> Generează PDF Ore
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i> 
                        Calculează automat orele lucrate și orele suplimentare pentru fiecare angajat.
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Informații despre rapoarte -->
    <div class="alert alert-info mt-4">
        <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i> Informații importante despre rapoarte:</h5>
        <ul class="mb-0">
            <li><strong>Raport Întârzieri:</strong> Listă cu toate întârzierile calculate dinamic după programul fiecărui angajat (toleranță: 15min pentru 8h, 10min pentru 12h)</li>
            <li><strong>Raport Pontaje:</strong> Istoric complet al pontajelor cu verificare automată a întârzierilor și detalii complete</li>
            <li><strong>Raport Ore:</strong> Total ore lucrate, ore suplimentare, medii și statistici detaliate pe angajat</li>
            <li>Toate rapoartele includ antet profesional, data generării, numărul paginii și semnătura digitală</li>
            <li>Rapoartele se deschid direct în browser pentru vizualizare și pot fi salvate sau printate</li>
        </ul>
    </div>
    
    <!-- Notă tehnică -->
    <div class="note-box">
        <h6><i class="fas fa-lightbulb me-2"></i> Cum funcționează sistemul:</h6>
        <p class="mb-1">1. Selectează tipul de raport și filtrele dorite</p>
        <p class="mb-1">2. Apasă butonul "Generează PDF" corespunzător</p>
        <p class="mb-1">3. Raportul se va deschide într-o filă nouă în format PDF</p>
        <p class="mb-0">4. Din browser poți să salvezi sau să prinți raportul</p>
    </div>
    
    <!-- Butoane de navigație la sfârșit -->
    <div class="row mt-4">
        <div class="col-md-6 mb-3">
            <a href="../../dashboard.php" class="btn btn-back w-100">
                <i class="fas fa-arrow-left me-2"></i> Înapoi la Dashboard
            </a>
        </div>
        <div class="col-md-6 mb-3">
            <a href="../../logout.php" class="btn btn-outline-danger w-100">
                <i class="fas fa-sign-out-alt me-2"></i> Deconectare
            </a>
        </div>
    </div>
    
    <!-- Footer informativ -->
    <div class="text-center mt-5 pt-3 border-top">
        <p class="text-muted">
            <small>
                <i class="fas fa-copyright me-1"></i> Sistem Pontaj <?php echo date('Y'); ?> | 
                <i class="fas fa-sync-alt me-1 ms-3"></i> Ultima actualizare: <?php echo date('d.m.Y H:i:s'); ?>
            </small>
        </p>
    </div>
</div>

<script>
// Afișează/cută câmpurile pentru perioadă personalizată
document.querySelector('select[name="perioada"]').addEventListener('change', function() {
    const perioadaPersonalizata = document.getElementById('perioada-personalizata');
    perioadaPersonalizata.style.display = (this.value === 'personalizat') ? 'block' : 'none';
});

document.querySelectorAll('select[name="perioada"]').forEach(select => {
    select.addEventListener('change', function() {
        const containerId = this.closest('form').querySelector('[id*="perioada-personalizata"]').id;
        const container = document.getElementById(containerId);
        container.style.display = (this.value === 'personalizat') ? 'block' : 'none';
    });
});

// Setează datele default pentru câmpurile de dată
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"]').forEach(input => {
        if (!input.value) {
            input.value = today;
        }
    });
    
    // Adaugă efecte hover pentru card-uri
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 8px 16px rgba(0,0,0,0.15)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
        });
    });
});

// Confirmare înainte de logout
document.querySelector('a[href*="logout.php"]')?.addEventListener('click', function(e) {
    if (!confirm('Sigur doriți să vă deconectați?')) {
        e.preventDefault();
    }
});
</script>

<?php
if (isset($footer_path) && file_exists($footer_path)) {
    include $footer_path;
} else {
    echo '</body></html>';
}
?>