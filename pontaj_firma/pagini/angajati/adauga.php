<?php
include '../../includes/config.php';
include '../../includes/auth.php';
checkAuth();
checkAdmin();

$errors = [];
$success = false;

// Procesare formular (poate fi în același fișier sau separat)
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
        // Verifică dacă email-ul există deja
        $check_email = $conn->prepare("SELECT id FROM angajati WHERE email = ?");
        $check_email->execute([$email]);
        if ($check_email->fetch()) {
            $errors[] = "Adresa de email este deja utilizată.";
        }
    }
    
    if (empty($parola)) {
        $errors[] = "Parola este obligatorie.";
    } elseif (strlen($parola) < 6) {
        $errors[] = "Parola trebuie să aibă cel puțin 6 caractere.";
    } elseif ($parola !== $confirma_parola) {
        $errors[] = "Parolele nu coincid.";
    }
    
    if (empty($data_angajare)) {
        $data_angajare = date('Y-m-d');
    }
    
    // Dacă nu sunt erori, inserează în baza de date
    if (empty($errors)) {
        try {
            $parola_hash = password_hash($parola, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO angajati (nume, email, parola_hash, departament, data_angajare, este_admin, tip_program_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $nume, 
                $email, 
                $parola_hash, 
                $departament, 
                $data_angajare, 
                $este_admin, 
                $tip_program_id
            ]);
            
            $id_nou = $conn->lastInsertId();
            $success = true;
            
            // Redirect la lista cu mesaj de succes
            header("Location: index.php?success=Angajatul " . urlencode($nume) . " a fost adăugat cu succes!");
            exit();
            
        } catch (Exception $e) {
            $errors[] = "Eroare la adăugarea angajatului: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaugă Angajat - Sistem Pontaj</title>
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
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        
        .checkbox-group { display: flex; align-items: center; }
        .checkbox-group input { width: auto; margin-right: 10px; }
        
        .password-strength { margin-top: 5px; font-size: 14px; }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .form-help { font-size: 13px; color: #666; margin-top: 5px; }
    </style>
    <script>
        function checkPasswordStrength(password) {
            const strengthText = document.getElementById('password-strength');
            if (!password) {
                strengthText.textContent = '';
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
            
            if (!password || !confirm) {
                message.textContent = '';
                return true;
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
            <h1 style="margin: 0;">➕ Adaugă Angajat Nou</h1>
            <div>
                <a href="index.php" style="color: white; margin-left: 15px; text-decoration: none;">← Înapoi la lista angajaților</a>
                <a href="../../dashboard.php" style="color: white; margin-left: 15px; text-decoration: none;">🏠 Dashboard</a>
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
                ✅ Angajatul a fost adăugat cu succes!
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <h2 class="form-title">📝 Date angajat nou</h2>
            
            <form method="POST" action="" onsubmit="return validatePasswords()">
                <!-- Rând 1: Nume și Email -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="nume" class="required">Nume complet:</label>
                        <input type="text" id="nume" name="nume" 
                               value="<?php echo isset($_POST['nume']) ? htmlspecialchars($_POST['nume']) : ''; ?>"
                               required placeholder="Ex: Popescu Ion">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="required">Email:</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required placeholder="Ex: ion.popescu@firma.com">
                    </div>
                </div>
                
                <!-- Rând 2: Parole -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="parola" class="required">Parolă:</label>
                        <input type="password" id="parola" name="parola" 
                               onkeyup="checkPasswordStrength(this.value)"
                               required placeholder="Minim 6 caractere">
                        <div id="password-strength" class="password-strength"></div>
                        <div class="form-help">Minim 6 caractere. Recomandat: majuscule, cifre, caractere speciale</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirma_parola" class="required">Confirmă parola:</label>
                        <input type="password" id="confirma_parola" name="confirma_parola" 
                               onkeyup="validatePasswords()"
                               required placeholder="Introduceți din nou parola">
                        <div id="password-match" class="password-strength"></div>
                    </div>
                </div>
                
                <!-- Rând 3: Departament și Data angajare -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="departament">Departament:</label>
                        <select id="departament" name="departament">
                            <option value="">-- Selectează departament --</option>
                            <option value="IT" <?php echo (isset($_POST['departament']) && $_POST['departament'] == 'IT') ? 'selected' : ''; ?>>IT</option>
                            <option value="Vanzari" <?php echo (isset($_POST['departament']) && $_POST['departament'] == 'Vanzari') ? 'selected' : ''; ?>>Vânzări</option>
                            <option value="Marketing" <?php echo (isset($_POST['departament']) && $_POST['departament'] == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                            <option value="Resurse Umane" <?php echo (isset($_POST['departament']) && $_POST['departament'] == 'Resurse Umane') ? 'selected' : ''; ?>>Resurse Umane</option>
                            <option value="Contabilitate" <?php echo (isset($_POST['departament']) && $_POST['departament'] == 'Contabilitate') ? 'selected' : ''; ?>>Contabilitate</option>
                            <option value="Management" <?php echo (isset($_POST['departament']) && $_POST['departament'] == 'Management') ? 'selected' : ''; ?>>Management</option>
                            <option value="Productie" <?php echo (isset($_POST['departament']) && $_POST['departament'] == 'Productie') ? 'selected' : ''; ?>>Producție</option>
                            <option value="Suport" <?php echo (isset($_POST['departament']) && $_POST['departament'] == 'Suport') ? 'selected' : ''; ?>>Suport</option>
                        </select>
                        <div class="form-help">Sau introduceți manual în caseta de mai jos</div>
                        <input type="text" name="departament_custom" placeholder="Alt departament..." 
                               style="margin-top: 5px;"
                               value="<?php echo (isset($_POST['departament_custom']) && !empty($_POST['departament_custom'])) ? htmlspecialchars($_POST['departament_custom']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_angajare">Data angajării:</label>
                        <input type="date" id="data_angajare" name="data_angajare" 
                               value="<?php echo isset($_POST['data_angajare']) ? htmlspecialchars($_POST['data_angajare']) : date('Y-m-d'); ?>">
                        <div class="form-help">Lăsați gol pentru data de astăzi</div>
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
                                $selected = (isset($_POST['tip_program_id']) && $_POST['tip_program_id'] == $tip['id']) ? 'selected' : '';
                                echo "<option value='{$tip['id']}' $selected>{$tip['nume_program']} ({$tip['ore_pe_zi']} ore/zi)</option>";
                            }
                            ?>
                        </select>
                        <div class="form-help">Programul de lucru standard</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="este_admin">Rol în sistem:</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="este_admin" name="este_admin" value="1"
                                   <?php echo (isset($_POST['este_admin']) && $_POST['este_admin'] == 1) ? 'checked' : ''; ?>>
                            <label for="este_admin" style="font-weight: normal;">Acest angajat este administrator</label>
                        </div>
                        <div class="form-help">Administratorii pot accesa toate funcțiile sistemului</div>
                    </div>
                </div>
                
                <!-- Note opționale -->
                <div class="form-group">
                    <label for="note">Note (opțional):</label>
                    <textarea id="note" name="note" rows="3" placeholder="Observații sau informații suplimentare..."><?php echo isset($_POST['note']) ? htmlspecialchars($_POST['note']) : ''; ?></textarea>
                </div>
                
                <!-- Butoane -->
                <div style="display: flex; gap: 15px; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                    <button type="submit" class="btn btn-primary">💾 Salvează Angajat</button>
                    <a href="index.php" class="btn btn-secondary">❌ Anulează</a>
                    <button type="reset" class="btn" style="background: #6c757d; color: white;">🔄 Resetează formular</button>
                </div>
            </form>
        </div>
        
        <!-- Instrucțiuni -->
        <div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px; border-left: 4px solid #007bff;">
            <h3 style="margin-top: 0;">ℹ️ Instrucțiuni pentru adăugare angajat:</h3>
            <ul style="margin-bottom: 0;">
                <li><strong>Email-ul trebuie să fie unic</strong> pentru fiecare angajat</li>
                <li><strong>Parola</strong> va fi criptată în baza de date</li>
                <li>Angajatul primește <strong>acces imediat</strong> la sistem după adăugare</li>
                <li>Pentru <strong>administratori</strong> – acordați acest rol doar personalului autorizat</li>
                <li>Datele pot fi <strong>modificate ulterior</strong> din pagina de editare</li>
            </ul>
        </div>
    </div>
</body>
</html>