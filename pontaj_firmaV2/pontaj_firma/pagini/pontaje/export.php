<?php
require_once '../../includes/auth.php';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

checkAccess('export_pontaje');

// Preluare parametri
$format = $_REQUEST['format'] ?? 'excel';
$start_date = $_REQUEST['start_date'] ?? date('Y-m-01');
$end_date = $_REQUEST['end_date'] ?? date('Y-m-d');
$angajat_id = $_REQUEST['angajat_id'] ?? '';
$departament = $_REQUEST['departament'] ?? '';

// Construire query
$query = "SELECT p.*, a.nume, a.prenume, a.departament 
          FROM pontaje p
          JOIN angajati a ON p.angajat_id = a.id
          WHERE p.data_pontaj BETWEEN ? AND ?";

$params = [$start_date, $end_date . ' 23:59:59'];

if (!empty($angajat_id)) {
    $query .= " AND p.angajat_id = ?";
    $params[] = $angajat_id;
}

if (!empty($departament)) {
    $query .= " AND a.departament = ?";
    $params[] = $departament;
}

$query .= " ORDER BY p.data_pontaj DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$pontaje = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export în funcție de format
switch (strtolower($format)) {
    case 'excel':
        exportExcel($pontaje, $start_date, $end_date);
        break;
    case 'csv':
        exportCSV($pontaje, $start_date, $end_date);
        break;
    case 'pdf':
        exportPDF($pontaje, $start_date, $end_date);
        break;
    case 'json':
        exportJSON($pontaje);
        break;
    default:
        exportExcel($pontaje, $start_date, $end_date);
}

function exportExcel($data, $start_date, $end_date) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="pontaje_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    $startFormatted = date('d.m.Y', strtotime($start_date));
    $endFormatted = date('d.m.Y', strtotime($end_date));
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1" cellpadding="5">';
    
    // Titlu
    echo '<tr><th colspan="8" style="background:#4e73df;color:white;font-size:16px;padding:10px;">';
    echo 'Raport Pontaje - Perioada: ' . $startFormatted . ' - ' . $endFormatted;
    echo '</th></tr>';
    
    // Antet tabel
    echo '<tr style="background:#f2f2f2;font-weight:bold;">';
    echo '<th>Data/Ora</th>';
    echo '<th>Angajat</th>';
    echo '<th>Departament</th>';
    echo '<th>Tip Pontaj</th>';
    echo '<th>Dispozitiv</th>';
    echo '<th>Status</th>';
    echo '<th>IP Adresă</th>';
    echo '<th>Observații</th>';
    echo '</tr>';
    
    // Date
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td>' . date('d.m.Y H:i', strtotime($row['data_pontaj'])) . '</td>';
        echo '<td>' . htmlspecialchars($row['nume'] . ' ' . $row['prenume']) . '</td>';
        echo '<td>' . htmlspecialchars($row['departament'] ?? 'N/A') . '</td>';
        echo '<td>' . ($row['tip_pontaj'] == 'in' ? 'INTRARE' : 'IEȘIRE') . '</td>';
        echo '<td>' . htmlspecialchars($row['dispozitiv'] ?? 'web') . '</td>';
        echo '<td>' . getStatusLabel($row['status'] ?? 'on_time') . '</td>';
        echo '<td>' . htmlspecialchars($row['ip_address'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($row['observatii'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    // Total
    if (!empty($data)) {
        echo '<tr style="background:#f8f9fa;font-weight:bold;">';
        echo '<td colspan="8" style="text-align:right;">';
        echo 'Total înregistrări: ' . count($data);
        echo '</td></tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}

function exportCSV($data, $start_date, $end_date) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="pontaje_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // Adaugă BOM pentru UTF-8
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Header
    fputcsv($output, ['RAPORT PONTAJE']);
    fputcsv($output, ['Perioada:', date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date))]);
    fputcsv($output, ['Data export:', date('d.m.Y H:i')]);
    fputcsv($output, []); // Linie goală
    
    // Antet tabel
    fputcsv($output, ['Data/Ora', 'Angajat', 'Departament', 'Tip', 'Dispozitiv', 'Status', 'IP', 'Observații']);
    
    // Date
    foreach ($data as $row) {
        fputcsv($output, [
            date('d.m.Y H:i', strtotime($row['data_pontaj'])),
            $row['nume'] . ' ' . $row['prenume'],
            $row['departament'] ?? 'N/A',
            $row['tip_pontaj'] == 'in' ? 'INTRARE' : 'IEȘIRE',
            $row['dispozitiv'] ?? 'web',
            getStatusLabel($row['status'] ?? 'on_time'),
            $row['ip_address'] ?? 'N/A',
            $row['observatii'] ?? ''
        ]);
    }
    
    // Total
    if (!empty($data)) {
        fputcsv($output, []);
        fputcsv($output, ['Total înregistrări:', count($data)]);
    }
    
    fclose($output);
    exit;
}

function exportJSON($data) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment;filename="pontaje_' . date('Y-m-d') . '.json"');
    
    $exportData = [
        'metadata' => [
            'export_date' => date('Y-m-d H:i:s'),
            'total_records' => count($data)
        ],
        'pontaje' => $data
    ];
    
    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function exportPDF($data, $start_date, $end_date) {
    // Pentru PDF, folosește o bibliotecă ca TCPDF sau FPDF
    // Aici redirecționăm către Excel temporar
    exportExcel($data, $start_date, $end_date);
}

function getStatusLabel($status) {
    $statusLabels = [
        'on_time' => 'La timp',
        'late' => 'Întârziat',
        'early' => 'Ieșire devreme',
        'overtime' => 'Overtime'
    ];
    
    return $statusLabels[$status] ?? $status;
}
?>