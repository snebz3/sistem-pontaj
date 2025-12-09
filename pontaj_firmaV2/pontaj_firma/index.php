<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dacă este deja logat, redirect la dashboard
if (isset($_SESSION['angajat_id']) && !empty($_SESSION['angajat_id'])) {
    header('Location: dashboard.php');
    exit();
}

include 'includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $parola = $_POST['parola'];
    
    // Verificăm dacă angajatul există
    $sql = "SELECT * FROM angajati WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $angajat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($angajat && password_verify($parola, $angajat['parola_hash'])) {
        // Login successful
        $_SESSION['angajat_id'] = $angajat['id'];
        $_SESSION['nume'] = $angajat['nume'];
        $_SESSION['este_admin'] = $angajat['este_admin'];
        
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Email sau parolă incorectă!';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pontaj</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>🔐 Login Sistem Pontaj</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="parola">Parola:</label>
                <input type="password" id="parola" name="parola" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div style="margin-top: 20px; text-align: center; font-size: 12px; color: #666;">
            <strong>Utilizatori de test:</strong><br>
            admin@firma.com / password<br>
            mihai@popescu.com / password
        </div>
    </div>
</body>
</html>