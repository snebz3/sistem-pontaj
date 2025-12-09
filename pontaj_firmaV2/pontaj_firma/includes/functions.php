<?php
/**
 * Conectare la baza de date
 */
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'pontaj';
    $username = 'root';
    $password = '';
    
    try {
        $conn = new mysqli($host, $username, $password, $dbname);
        $conn->set_charset("utf8");
        return $conn;
    } catch (Exception $e) {
        die("Eroare conexiune: " . $e->getMessage());
    }
}

/**
 * Verifică dacă utilizatorul este autentificat
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifică dacă utilizatorul este admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

/**
 * Redirect către login dacă nu este autentificat
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Redirect către index dacă nu este admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Formatează data pentru afișare
 */
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

/**
 * Formatează ora pentru afișare
 */
function formatTime($time) {
    return date('H:i', strtotime($time));
}

/**
 * Calculează diferența de ore între două timpuri
 */
function calculateHours($start, $end) {
    if (empty($start) || empty($end)) return '0:00';
    
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    
    if ($end_time < $start_time) {
        $end_time += 24 * 3600; // Adaugă o zi dacă e peste miezul nopții
    }
    
    $diff = $end_time - $start_time;
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    return sprintf('%d:%02d', $hours, $minutes);
}

/**
 * Verifică dacă un angajat a întârziat la pontaj
 * @param int $angajat_id ID-ul angajatului
 * @param string $data_pontaj Data pontajului (YYYY-MM-DD)
 * @param string $ora_pontaj Ora pontajului (HH:MM:SS)
 * @return array ['intarziere' => bool, 'minute' => int, 'ora_program' => string, 'toleranta' => int, 'motiv' => string]
 */
function verificaIntarziere($angajat_id, $data_pontaj, $ora_pontaj) {
    $conn = getDBConnection();
    
    // 1. Caută programul angajatului pentru acea zi
    $sql = "SELECT ora_inceput, tip_program FROM orar_angajati 
            WHERE angajat_id = ? AND data = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $angajat_id, $data_pontaj);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'intarziere' => false, 
            'minute' => 0, 
            'ora_program' => '00:00',
            'toleranta' => 0,
            'motiv' => 'Nu are program setat pentru această zi'
        ];
    }
    
    $row = $result->fetch_assoc();
    $ora_inceput = $row['ora_inceput'];
    $tip_program = $row['tip_program'];
    
    // 2. Setează toleranța în funcție de tipul programului
    if (strpos($tip_program, '12') !== false) {
        $toleranta = 10; // 10 minute pentru program de 12h
    } else {
        $toleranta = 15; // 15 minute pentru program de 8h
    }
    
    // 3. Calculează
    $ora_limită = strtotime($ora_inceput . " +{$toleranta} minutes");
    $ora_pontaj_timestamp = strtotime($ora_pontaj);
    
    if ($ora_pontaj_timestamp > $ora_limită) {
        $minute_intarziere = round(($ora_pontaj_timestamp - $ora_limită) / 60);
        return [
            'intarziere' => true, 
            'minute' => $minute_intarziere,
            'ora_program' => $ora_inceput,
            'toleranta' => $toleranta,
            'motiv' => "Pontaj la {$ora_pontaj}, program la {$ora_inceput}"
        ];
    }
    
    return [
        'intarziere' => false, 
        'minute' => 0,
        'ora_program' => $ora_inceput,
        'toleranta' => $toleranta,
        'motiv' => 'Pontaj la timp'
    ];
}

/**
 * Obține numărul de întârzieri din ziua curentă
 * @return int Numărul de angajați întârziați astăzi
 */
function getNumarIntarzieriAzi() {
    $conn = getDBConnection();
    $data_azi = date('Y-m-d');
    
    $sql = "SELECT COUNT(DISTINCT p.angajat_id) as numar_intarzieri
            FROM pontaje p
            JOIN angajati a ON p.angajat_id = a.id
            JOIN orar_angajati oa ON a.id = oa.angajat_id 
                AND DATE(p.data_pontaj) = oa.data
            WHERE DATE(p.data_pontaj) = ?
            AND p.tip = 'intrare'
            AND (
                (oa.tip_program LIKE '%12%' AND TIME(p.ora_intrare) > TIME(ADDTIME(oa.ora_inceput, '00:10:00')))
                OR
                (oa.tip_program NOT LIKE '%12%' AND TIME(p.ora_intrare) > TIME(ADDTIME(oa.ora_inceput, '00:15:00')))
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $data_azi);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['numar_intarzieri'] ?? 0;
}

/**
 * Obține lista detaliată a întârzierilor din ziua curentă
 * @return mysqli_result Lista angajaților întârziați
 */
function getListaIntarzieriAzi() {
    $conn = getDBConnection();
    $data_azi = date('Y-m-d');
    
    $sql = "SELECT a.id, a.nume, a.prenume, p.ora_intrare, oa.ora_inceput, oa.tip_program,
            CASE 
                WHEN oa.tip_program LIKE '%12%' THEN 
                    TIMESTAMPDIFF(MINUTE, oa.ora_inceput, p.ora_intrare) - 10
                ELSE 
                    TIMESTAMPDIFF(MINUTE, oa.ora_inceput, p.ora_intrare) - 15
            END as minute_intarziere
            FROM pontaje p
            JOIN angajati a ON p.angajat_id = a.id
            JOIN orar_angajati oa ON a.id = oa.angajat_id 
                AND DATE(p.data_pontaj) = oa.data
            WHERE DATE(p.data_pontaj) = ?
            AND p.tip = 'intrare'
            HAVING minute_intarziere > 0
            ORDER BY minute_intarziere DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $data_azi);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Obține statistici generale pentru dashboard
 */
function getStatisticiGenerale() {
    $conn = getDBConnection();
    $stats = [];
    
    // Total angajați activi
    $sql = "SELECT COUNT(*) as total FROM angajati WHERE activ = 1";
    $result = $conn->query($sql);
    $stats['angajati_activi'] = $result->fetch_assoc()['total'];
    
    // Total pontaje azi
    $sql = "SELECT COUNT(*) as total FROM pontaje WHERE DATE(data_pontaj) = CURDATE()";
    $result = $conn->query($sql);
    $stats['pontaje_azi'] = $result->fetch_assoc()['total'];
    
    // Total pontaje în sistem
    $sql = "SELECT COUNT(*) as total FROM pontaje";
    $result = $conn->query($sql);
    $stats['total_pontaje'] = $result->fetch_assoc()['total'];
    
    // Concedii active
    $sql = "SELECT COUNT(*) as total FROM concedii WHERE data_sfarsit >= CURDATE() AND status = 'aprobat'";
    $result = $conn->query($sql);
    $stats['concedii_active'] = $result->fetch_assoc()['total'];
    
    return $stats;
}

/**
 * Obține activități recente
 */
function getActivitatiRecente($limit = 5) {
    $conn = getDBConnection();
    
    $sql = "SELECT a.nume, a.prenume, p.data_pontaj, p.tip 
            FROM pontaje p 
            JOIN angajati a ON p.angajat_id = a.id 
            ORDER BY p.data_pontaj DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result();
}
?>