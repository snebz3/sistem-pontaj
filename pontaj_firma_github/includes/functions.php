<?php
function calculeazaOreDinOrar($angajat_id) {
    global $conn;
    
    $total_ore = 0;
    $sql = "SELECT ora_start, ora_end FROM orar_angajati WHERE angajat_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $angajat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $start = new DateTime($row['ora_start']);
        $end = new DateTime($row['ora_end']);
        $diff = $start->diff($end);
        $ore = $diff->h + ($diff->i / 60);
        $total_ore += $ore;
    }
    
    return round($total_ore, 2);
}
?>