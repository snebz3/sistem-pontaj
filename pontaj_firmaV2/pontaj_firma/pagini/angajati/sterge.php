<?php
include '../../includes/config.php';
include '../../includes/auth.php';
checkAuth();
checkAdmin();

// Verifică dacă există ID-ul angajatului
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=ID angajat invalid!");
    exit();
}

$angajat_id = (int)$_GET['id'];

// Obține datele angajatului
$sql = "SELECT * FROM angajati WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$angajat_id]);
$angajat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$angajat) {
    header("Location: index.php?error=Angajatul nu a fost găsit!");
    exit();
}

// Procesare ștergere
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'DA') {
        try {
            // Începe tranzacție pentru a asigura integritatea datelor
            $conn->beginTransaction();
            
            // 1. Șterge pontajele angajatului
            $sql1 = "DELETE FROM pontaje WHERE angajat_id = ?";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->execute([$angajat_id]);
            
            // 2. Șterge orarul angajatului
            $sql2 = "DELETE FROM orar_angajati WHERE angajat_id = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->execute([$angajat_id]);
            
            // 3. Șterge cererile de concediu
            $sql3 = "DELETE FROM cereri_concediu WHERE angajat_id = ?";
            $stmt3 = $conn->prepare($sql3);
            $stmt3->execute([$angajat_id]);
            
            // 4. Șterge statisticile
            $sql4 = "DELETE FROM statistici_ore WHERE angajat_id = ?";
            $stmt4 = $conn->prepare($sql4);
            $stmt4->execute([$angajat_id]);
            
            // 5. Șterge angajatul
            $sql5 = "DELETE FROM angajati WHERE id = ?";
            $stmt5 = $conn->prepare($sql5);
            $stmt5->execute([$angajat_id]);
            
            $conn->commit();
            
            header("Location: index.php?success=Angajatul " . urlencode($angajat['nume']) . " a fost șters cu succes!");
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Eroare la ștergerea angajatului: " . $e->getMessage();
        }
    } else {
        // User a anulat
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Șterge Angajat - Sistem Pontaj</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
        .header { background: #dc3545; color: white; padding: 15px; margin-bottom: 20px; }
        .header-content { display: flex; justify-content: space-between; align-items: center; max-width: 800px; margin: 0 auto; }
        .container { max-width: 800px; margin: 0 auto; padding: 0 20px; }
        
        .warning-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border: 3px solid #dc3545; }
        .warning-title { color: #dc3545; margin-top: 0; font-size: 24px; }
        
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        .info-box { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #007bff; }
        .danger-box { background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc3545; }
        
        .confirmation-box { background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: center; }
        .confirmation-box input { margin: 0 10px; padding: 10px; font-size: 18px; text-align: center; width: 60px; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-item { background: #e9ecef; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; color: #dc3545; }
        .stat-label { font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="margin: 0;">🗑️ Șterge Angajat</h1>
            <div>
                <a href="index.php" style="color: white; margin-left: 15px; text-decoration: none;">← Înapoi la listă</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="danger-box">
                <strong>❌ Eroare:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="warning-container">
            <h2 class="warning-title">⚠️ ATENȚIE: OPERAȚIUNE PERICULOASĂ!</h2>
            
            <div class="danger-box">
                <h3 style="margin-top: 0; color: #dc3545;">Vreți să ștergeți angajatul:</h3>
                <div style="text-align: center; padding: 20px; background: white; border-radius: 5px; margin: 15px 0;">
                    <div style="font-size: 24px; font-weight: bold; color: #333;"><?php echo htmlspecialchars($angajat['nume']); ?></div>
                    <div style="color: #666;"><?php echo htmlspecialchars($angajat['email']); ?></div>
                    <div style="margin-top: 10px;">
                        <span style="background: <?php echo $angajat['este_admin'] ? '#dc3545' : '#28a745'; ?>; 
                              color: white; padding: 5px 10px; border-radius: 3px;">
                            <?php echo $angajat['este_admin'] ? 'ADMINISTRATOR' : 'ANGAJAT'; ?>
                        </span>
                        <span style="margin-left: 10px; background: #6c757d; color: white; padding: 5px 10px; border-radius: 3px;">
                            <?php echo htmlspecialchars($angajat['departament'] ?? 'Nespecificat'); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Statistici ștergere -->
            <div class="stats">
                <?php
                // Număr pontări
                $pontaje_sql = "SELECT COUNT(*) as total FROM pontaje WHERE angajat_id = ?";
                $pontaje_stmt = $conn->prepare($pontaje_sql);
                $pontaje_stmt->execute([$angajat_id]);
                $pontaje_count = $pontaje_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Număr cereri concediu
                $cereri_sql = "SELECT COUNT(*) as total FROM cereri_concediu WHERE angajat_id = ?";
                $cereri_stmt = $conn->prepare($cereri_sql);
                $cereri_stmt->execute([$angajat_id]);
                $cereri_count = $cereri_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Număr înregistrări orar
                $orar_sql = "SELECT COUNT(*) as total FROM orar_angajati WHERE angajat_id = ?";
                $orar_stmt = $conn->prepare($orar_sql);
                $orar_stmt->execute([$angajat_id]);
                $orar_count = $orar_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                ?>
                
                <div class="stat-item">
                    <div class="stat-number"><?php echo $pontaje_count; ?></div>
                    <div class="stat-label">PONTAJE</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $cereri_count; ?></div>
                    <div class="stat-label">CERERI CONCEDIU</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $orar_count; ?></div>
                    <div class="stat-label">ÎNREGISTRĂRI ORAR</div>
                </div>
            </div>
            
            <div class="danger-box">
                <h4 style="margin-top: 0; color: #dc3545;">⚠️ Această acțiune va șterge:</h4>
                <ul>
                    <li><strong>Toate pontajele</strong> înregistrate de acest angajat</li>
                    <li><strong>Toate cererile de concediu</strong> ale angajatului</li>
                    <li><strong>Orarul și programul</strong> de lucru alocat</li>
                    <li><strong>Statisticile și rapoartele</strong> asociate</li>
                    <li><strong>Contul și toate datele</strong> personale ale angajatului</li>
                </ul>
                <p><strong>⚠️ OPERAȚIUNE IRREVERSIBILĂ!</strong> Datele șterse nu pot fi recuperate.</p>
            </div>
            
            <!-- Confirmare finală -->
            <div class="confirmation-box">
                <h3 style="margin-top: 0; color: #856404;">CONFIRMARE FINALĂ</h3>
                <p>Pentru a confirma ștergerea, introduceți <strong>DA</strong> în caseta de mai jos:</p>
                
                <form method="POST" action="" onsubmit="return validateConfirmation()">
                    <input type="text" id="confirm" name="confirm" 
                           placeholder="DA" 
                           style="font-weight: bold; letter-spacing: 2px;"
                           autocomplete="off">
                    <br><br>
                    
                    <button type="submit" class="btn btn-danger" id="delete-btn" disabled>
                        🗑️ ȘTERGE DEFINITIV ACEST ANGAJAT
                    </button>
                    <a href="index.php" class="btn btn-secondary">❌ ANULEAZĂ</a>
                </form>
            </div>
            
            <!-- Informații backup -->
            <div class="info-box">
                <h4 style="margin-top: 0;">💡 Recomandări înainte de ștergere:</h4>
                <ul style="margin-bottom: 0;">
                    <li>Exportați rapoartele finale ale angajatului</li>
                    <li>Verificați dacă angajatul are cereri de concediu în așteptare</li>
                    <li>Notați informații importante pentru arhivă</li>
                    <li>În loc de ștergere, puteți marca angajatul ca "inactiv"</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        function validateConfirmation() {
            const input = document.getElementById('confirm');
            const btn = document.getElementById('delete-btn');
            
            if (input.value.toUpperCase() === 'DA') {
                const finalConfirm = confirm('⛔ ATENȚIE FINALĂ!\n\nSunteți SIGUR că doriți să ștergeți definitiv angajatul:\n\n<?php echo addslashes($angajat['nume']); ?>?\n\nAceastă acțiune NU poate fi anulată!');
                return finalConfirm;
            }
            return false;
        }
        
        // Activează butonul doar când se scrie "DA"
        document.getElementById('confirm').addEventListener('input', function() {
            const btn = document.getElementById('delete-btn');
            btn.disabled = this.value.toUpperCase() !== 'DA';
        });
    </script>
</body>
</html>