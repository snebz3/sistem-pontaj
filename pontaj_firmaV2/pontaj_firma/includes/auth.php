<?php
/**
 * Verifică dacă utilizatorul este autentificat
 */


function checkAuth() {
    if (!isset($_SESSION['angajat_id']) || empty($_SESSION['angajat_id'])) {
        header("Location: ../../index.php");
        exit();
    }
}

/**
 * Verifică dacă utilizatorul este administrator
 */
function checkAdmin() {
    if (!isset($_SESSION['este_admin']) || $_SESSION['este_admin'] != 1) {
        header("Location: ../../dashboard.php");
        exit();
    }
}

/**
 * Obține ID-ul utilizatorului curent
 */
function getCurrentUserId() {
    return $_SESSION['angajat_id'] ?? null;
}

/**
 * Obține numele utilizatorului curent
 */
function getCurrentUserName() {
    return $_SESSION['nume'] ?? 'Utilizator';
}

/**
 * Verifică dacă utilizatorul are acces la un anumit angajat
 */
function hasAccessToEmployee($employee_id) {
    if (isset($_SESSION['este_admin']) && $_SESSION['este_admin'] == 1) {
        return true;
    }
    
    return (isset($_SESSION['angajat_id']) && $_SESSION['angajat_id'] == $employee_id);
}

/**
 * Verifică dacă utilizatorul are o anumită permisiune
 */
function hasPermission($permission) {
    // Logica simplă - poți adapta după nevoie
    $user_role = $_SESSION['user_role'] ?? 'angajat';
    
    $permissions = [
        'admin' => ['view_pontaje', 'edit_pontaje', 'export_pontaje', 'delete_pontaje', 'view_all'],
        'manager' => ['view_pontaje', 'export_pontaje', 'view_all'],
        'angajat' => ['view_pontaje']
    ];
    
    // Adminii au toate permisiunile
    if (isset($_SESSION['este_admin']) && $_SESSION['este_admin'] == 1) {
        return true;
    }
    
    return in_array($permission, $permissions[$user_role] ?? []);
}

/**
 * Verifică accesul la o resursă
 */
function checkAccess($permission) {
    // 1. Verifică autentificare
    if (!isset($_SESSION['angajat_id'])) {
        // Dacă suntem într-un subfolder, ajustăm path-ul
        $current_dir = dirname($_SERVER['PHP_SELF']);
        $levels_up = substr_count($current_dir, '/') - 1;
        $back_path = str_repeat('../', max($levels_up, 0));
        
        header("Location: " . $back_path . "index.php");
        exit;
    }
    
    // 2. Verifică permisiunea
    if (!hasPermission($permission)) {
        // Redirecționează către dashboard cu path relativ
        $current_dir = dirname($_SERVER['PHP_SELF']);
        $levels_up = substr_count($current_dir, '/') - 1;
        $back_path = str_repeat('../', max($levels_up, 0));
        
        header("Location: " . $back_path . "pagini/dashboard.php?error=no_access");
        exit;
    }
}

// Funcție pentru debugging
function debugSession() {
    echo '<div style="background:#f0f0f0;padding:10px;margin:10px;border:1px solid #ccc;">';
    echo '<strong>Debug Session:</strong><br>';
    echo 'angajat_id: ' . ($_SESSION['angajat_id'] ?? 'NOT SET') . '<br>';
    echo 'este_admin: ' . ($_SESSION['este_admin'] ?? 'NOT SET') . '<br>';
    echo 'user_role: ' . ($_SESSION['user_role'] ?? 'NOT SET') . '<br>';
    echo 'nume: ' . ($_SESSION['nume'] ?? 'NOT SET') . '<br>';
    echo '</div>';
}

?>