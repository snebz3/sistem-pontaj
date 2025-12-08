<?php
/**
 * Funcții pentru sistemul de pontaj
 */

/**
 * Calculează orele lucrate pentru un angajat într-o perioadă
 */
function calculeazaOreLucrateAngajat($angajat_id, $data_start, $data_end) {
    global $conn;
    
    if (!$conn) {
        require_once __DIR__ . '/config.php';
    }
    
    // Query care grupează perechile intrare/ieșire pe zile
    $sql = "SELECT 
                DATE(p1.data_pontaj) as ziua,
                p1.data_pontaj as intrare,
                MIN(p2.data_pontaj) as iesire,
                TIMESTAMPDIFF(SECOND, p1.data_pontaj, MIN(p2.data_pontaj)) as secunde
            FROM pontaje p1
            LEFT JOIN pontaje p2 ON p1.angajat_id = p2.angajat_id 
                AND DATE(p1.data_pontaj) = DATE(p2.data_pontaj)
                AND p1.tip = 'intrare'
                AND p2.tip = 'iesire'
                AND p1.data_pontaj < p2.data_pontaj
            WHERE p1.angajat_id = ?
                AND p1.tip = 'intrare'
                AND DATE(p1.data_pontaj) BETWEEN ? AND ?
            GROUP BY DATE(p1.data_pontaj), p1.data_pontaj
            HAVING iesire IS NOT NULL
            ORDER BY ziua";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$angajat_id, $data_start, $data_end]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_ore = 0;
        
        foreach ($results as $row) {
            if ($row['secunde'] > 0) {
                $ore = $row['secunde'] / 3600; // convertește secunde în ore
                $total_ore += $ore;
            }
        }
        
        return round($total_ore, 2);
        
    } catch (Exception $e) {
        error_log("Eroare calcul ore angajat {$angajat_id}: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calculează numărul de zile pontate (cu intrare) pentru un angajat
 */
function calculeazaZilePontate($angajat_id, $data_start, $data_end) {
    global $conn;
    
    $sql = "SELECT COUNT(DISTINCT DATE(data_pontaj)) as zile_pontate
            FROM pontaje 
            WHERE angajat_id = ?
                AND tip = 'intrare'
                AND DATE(data_pontaj) BETWEEN ? AND ?";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$angajat_id, $data_start, $data_end]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['zile_pontate'] ?? 0;
    } catch (Exception $e) {
        error_log("Eroare calcul zile pontate: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calculează orele planificate pentru un angajat (din tipul programului)
 */
function calculeazaOrePlanificate($angajat_id, $data_start, $data_end) {
    global $conn;
    
    $sql = "SELECT 
                a.tip_program_id,
                tp.ore_pe_zi,
                COUNT(DISTINCT DATE(p.data_pontaj)) as zile_pontate
            FROM angajati a
            LEFT JOIN tipuri_program tp ON a.tip_program_id = tp.id
            LEFT JOIN pontaje p ON a.id = p.angajat_id 
                AND p.tip = 'intrare'
                AND DATE(p.data_pontaj) BETWEEN ? AND ?
            WHERE a.id = ?
            GROUP BY a.id";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$data_start, $data_end, $angajat_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['zile_pontate'] > 0 && $result['ore_pe_zi']) {
            return $result['zile_pontate'] * $result['ore_pe_zi'];
        }
        
        // Dacă nu are pontări, folosește zilele lucrătoare
        $zile_lucratoare = calculeazaZileLucratoare($data_start, $data_end);
        return $zile_lucratoare * ($result['ore_pe_zi'] ?? 8);
        
    } catch (Exception $e) {
        error_log("Eroare calcul ore planificate: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calculează zilele lucrătoare dintr-o perioadă (exclusiv weekend)
 */
function calculeazaZileLucratoare($data_start, $data_end) {
    $start = new DateTime($data_start);
    $end = new DateTime($data_end);
    $end->modify('+1 day'); // Include ziua de sfârșit
    
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);
    
    $zile_lucratoare = 0;
    foreach ($period as $date) {
        $dayOfWeek = $date->format('N'); // 1 (Luni) - 7 (Duminică)
        if ($dayOfWeek <= 5) { // Luni-Vineri
            $zile_lucratoare++;
        }
    }
    
    return $zile_lucratoare;
}

/**
 * Verifică dacă un angajat are pontaj complet pentru o zi (intrare + ieșire)
 */
function arePontajComplet($angajat_id, $data) {
    global $conn;
    
    $sql = "SELECT 
                SUM(CASE WHEN tip = 'intrare' THEN 1 ELSE 0 END) as intrari,
                SUM(CASE WHEN tip = 'iesire' THEN 1 ELSE 0 END) as iesiri
            FROM pontaje 
            WHERE angajat_id = ?
                AND DATE(data_pontaj) = ?";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([$angajat_id, $data]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['intrari'] ?? 0) > 0 && ($result['iesiri'] ?? 0) > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Obține angajați fără duplicate (pentru rapoarte)
 */
function getAngajatiFaraDuplicate($filtru_angajat_id = '', $filtru_departament = '') {
    global $conn;
    
    $sql = "SELECT 
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
    
    if ($filtru_angajat_id) {
        $sql .= " AND a.id = ?";
        $params[] = $filtru_angajat_id;
    }
    
    if ($filtru_departament) {
        $sql .= " AND a.departament = ?";
        $params[] = $filtru_departament;
    }
    
    $sql .= " ORDER BY a.id";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rezultate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Elimină duplicatele pe bază de email (cel mai probabil cauza)
    $angajati_unici = [];
    foreach($rezultate as $row) {
        $email = strtolower(trim($row['email']));
        if (!isset($angajati_unici[$email])) {
            $angajati_unici[$email] = $row;
        }
    }
    
    return array_values($angajati_unici);
}
?>