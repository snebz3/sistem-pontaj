<?php
include '../../includes/config.php';
include '../../includes/auth.php';
checkAuth();
checkAdmin();

$errors = [];
$success = false;

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

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validare date
    $nume = trim($_POST['nume'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $parola = $_POST['parola'] ?? '';
    $confirma_parola = $_POST['confirma_parola'] ?? '';
    $departament = trim($_POST['departament'] ?? '');
    $data_angajare = $_POST['data_angajare'] ?? '';
    $este_admin = isset($_POST['este_admin']) ? 1 : 0;
    $tip_program_id = $_POST['tip_program_id'] ?? 1;
    
    // Validări
    if (empty($nume)) {
        $errors[] = "Numele este obligatoriu.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresa de email este invalidă.";
    } else {
        // Verifică dacă email-ul există deja pentru alt angajat
        $check_email = $conn->prepare("SELECT id FROM angajati WHERE email = ? AND id != ?");
        $check_email->execute([$email, $angajat_id]);
        if ($check_email->fetch()) {
            $errors[] = "Adresa de email este deja utilizată de alt angajat.";
        }
    }
    
    // Validare parolă doar dacă a fost completată
    if (!empty($parola)) {
        if (strlen($parola) < 6) {
            $errors[] = "Parola trebuie să aibă cel puțin 6 caractere.";
        } elseif ($parola !== $confirma_parola) {
            $errors[] = "Parolele nu coincid.";
        }
    }
    
    if (empty($data_angajare)) {
        $data_angajare = $angajat['data_angajare'] ?? date('Y-m-d');
    }
    
    // Dacă nu sunt erori, actualizează în baza de date
    if (empty($errors)) {
        try {
            if (!empty($parola)) {
                // Actualizează cu parolă nouă
                $parola_hash = password_hash($parola, PASSWORD_DEFAULT);
                $sql = "UPDATE angajati SET 
                        nume = ?, 
                        email = ?, 
                        parola_hash = ?, 
                        departament = ?, 
                        data_angajare = ?, 
                        este_admin = ?, 
                        tip_program_id = ?
                        WHERE id = ?";
                
                $params = [$nume, $email, $parola_hash, $departament, $data_angajare, $este_admin, $tip_program_id, $angajat_id];
            } else {
                // Actualizează fără a schimba parola
                $sql = "UPDATE angajati SET 
                        nume = ?, 
                        email = ?, 
                        departament = ?, 
                        data_angajare = ?, 
                        este_admin = ?, 
                        tip_program_id = ?
                        WHERE id = ?";
                
                $params = [$nume, $email, $departament, $data_angajare, $este_admin, $tip_program_id, $angajat_id];
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $success = true;
            
            // Actualizează datele afișate
            $angajat['nume'] = $nume;
            $angajat['email'] = $email;
            $angajat['departament'] = $departament;
            $angajat['data_angajare'] = $data_angajare;
            $angajat['este_admin'] = $este_admin;
            $angajat['tip_program_id'] = $tip_program_id;
            
        } catch (Exception $e) {
            $errors[] = "Eroare la actualizarea angajatului: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editează Angajat - Sistem Pontaj</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
        .header { background: #343a40; color: white; padding: 15px; margin-bottom: 20px; }
        .header-content { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
        .container { max-width: 800px; margin: 0 auto; padding: 0 20px; }
        
        .form-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-title { margin-top: 0; margin-bottom: 25px; color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        .required:after { content: " *"; color: #dc3545; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        input:focus, select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 2px rgba(0,123,255,.25); }
        
        .btn { padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        
        .checkbox-group { display: flex; align-items: center; }
        .checkbox-group input { width: auto; margin-right: 10px; }
        
        .password-strength { margin-top: 5px; font-size: 14px; }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .form-help { font-size: 13px; color: #666; margin-top: 5px; }
        .info-box { background: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #007bff; }
    </style>
    <script>
        function checkPasswordStrength(password) {
            const strengthText = document.getElementById('password-strength');
            if (!password) {
                strengthText.textContent = 'Lăsați gol pentru a păstra parola existentă';
                strengthText.className = 'password-strength';
                return;
            }
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const texts = ['Foarte slabă', 'Slabă', 'Medie', 'Bună', 'Foarte bună'];
            const colors = ['strength-weak', 'strength-weak', 'strength-medium', 'strength-strong', 'strength-strong'];
            
            strengthText.textContent = 'Rezistență: ' + texts[strength];
            strengthText.className = 'password-strength ' + colors[strength];
        }
        
        function validatePasswords() {
            const password = document.getElementById('parola').value;
            const confirm = document.getElementById('confirma_parola').value;
            const message = document.getElementById('password-match');
            
            if (!password && !confirm) {
                message.textContent = 'Lăsați gol pentru a păstra parola existentă';
                message.style.color = '#666';
                return true;
            }
            
            if (!password || !confirm) {
                message.textContent = 'Completați ambele câmpuri pentru a schimba parola';
                message.style.color = '#dc3545';
                return false;
            }
            
            if (password === confirm) {
                message.textContent = '✓ Parolele coincid';
                message.style.color = '#28a745';
                return true;
            } else {
                message.textContent = '✗ Parolele nu coincid';
                message.style.color = '#dc3545';
                return false;
            }
        }
    </script>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="margin: 0;">✏️ Editează Angajat</h1>
            <div>
                <a href="detalii.php?id=<?php echo $angajat_id; ?>" style="color: white; margin-left: 15px; text-decoration: none;">👁️ Vezi detalii</a>
                <a href="index.php" style="color: white; margin-left: 15px; text-decoration: none;">← Înapoi la listă</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Eroare:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <?php foreach($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ Datele angajatului au fost actualizate cu succes!
            </div>
        <?php endif; ?>
        
        <!-- Informații angajat -->
        <div class="info-box">
            <h3 style="margin-top: 0;">📋 Informații angajat</h3>
            <p><strong>ID:</strong> <?php echo $angajat['id']; ?> | 
               <strong>Data înregistrării:</strong> <?php echo date('d.m.Y', strtotime($angajat['data_angajare'])); ?> |
               <strong>Ultima actualizare:</strong> <?php echo date('d.m.Y H:i'); ?></p>
            <?php if($angajat['este_admin']): ?>
                <div style="background: #dc3545; color: white; padding: 5px 10px; border-radius: 3px; display: inline-block;">
                    ⚠️ Acest angajat are drepturi de administrator
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-container">
            <h2 class="form-title">Editează datele lui <span style="color: #007bff;"><?php echo htmlspecialchars($angajat['nume']); ?></span></h2>
            
            <form method="POST" action="" onsubmit="return validatePasswords()">
                <!-- Rând 1: Nume și Email -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="nume" class="required">Nume complet:</label>
                        <input type="text" id="nume" name="nume" 
                               value="<?php echo htmlspecialchars($angajat['nume']); ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="required">Email:</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($angajat['email']); ?>"
                               required>
                    </div>
                </div>
                
                <!-- Rând 2: Parole (opțional) -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="parola">Schimbă parola:</label>
                        <input type="password" id="parola" name="parola" 
                               onkeyup="checkPasswordStrength(this.value)"
                               placeholder="Lăsați gol pentru a păstra parola actuală">
                        <div id="password-strength" class="password-strength">Lăsați gol pentru a păstra parola existentă</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirma_parola">Confirmă noua parolă:</label>
                        <input type="password" id="confirma_parola" name="confirma_parola" 
                               onkeyup="validatePasswords()"
                               placeholder="Introduceți din nou noua parolă">
                        <div id="password-match" class="password-strength"></div>
                    </div>
                </div>
                
                <!-- Rând 3: Departament și Data angajare -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="departament">Departament:</label>
                        <input type="text" id="departament" name="departament" 
                               value="<?php echo htmlspecialchars($angajat['departament'] ?? ''); ?>"
                               placeholder="Ex: IT, Vânzări, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_angajare">Data angajării:</label>
                        <input type="date" id="data_angajare" name="data_angajare" 
                               value="<?php echo $angajat['data_angajare']; ?>">
                    </div>
                </div>
                
                <!-- Rând 4: Tip program și Rol -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="tip_program_id">Tip program:</label>
                        <select id="tip_program_id" name="tip_program_id">
                            <?php
                            $tipuri_query = $conn->query("SELECT * FROM tipuri_program ORDER BY id");
                            $tipuri = $tipuri_query->fetchAll(PDO::FETCH_ASSOC);
                            foreach($tipuri as $tip) {
                                $selected = ($angajat['tip_program_id'] == $tip['id']) ? 'selected' : '';
                                echo "<option value='{$tip['id']}' $selected>{$tip['nume_program']} ({$tip['ore_pe_zi']} ore/zi)</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="este_admin">Rol în sistem:</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="este_admin" name="este_admin" value="1"
                                   <?php echo ($angajat['este_admin'] == 1) ? 'checked' : ''; ?>>
                            <label for="este_admin" style="font-weight: normal;">Acest angajat este administrator</label>
                        </div>
                    </div>
                </div>
                
                <!-- Butoane -->
                <div style="display: flex; gap: 15px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                    <button type="submit" class="btn btn-primary">💾 Salvează modificări</button>
                    <a href="detalii.php?id=<?php echo $angajat_id; ?>" class="btn btn-secondary">👁️ Vezi detalii</a>
                    <a href="index.php" class="btn" style="background: #6c757d; color: white;">❌ Anulează</a>
                </div>
            </form>
        </div>
        
        <!-- Acțiuni rapide -->
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px; border: 1px solid #ddd;">
            <h3 style="margin-top: 0;">⚡ Acțiuni rapide</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="sterge.php?id=<?php echo $angajat_id; ?>" 
                   class="btn btn-warning"
                   onclick="return confirm('⚠️ ATENȚIE! Doriți să ștergeți angajatul <?php echo addslashes($angajat['nume']); ?>? Această acțiune va șterge TOATE datele asociate (pontaje, cereri, etc.) și este ireversibilă!')">
                   🗑️ Șterge acest angajat
                </a>
                <a href="../pontaje/?angajat_id=<?php echo $angajat_id; ?>" class="btn" style="background: #17a2b8; color: white;">📅 Vezi pontajele</a>
                <a href="../rapoarte/?angajat_id=<?php echo $angajat_id; ?>" class="btn" style="background: #28a745; color: white;">📊 Vezi rapoarte</a>
                <a href="index.php" class="btn" style="background: #6c757d; color: white;">← Înapoi la lista angajaților</a>
            </div>
        </div>
    </div>
</body>
</html>