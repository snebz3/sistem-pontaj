<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../../includes/config.php';

// Verifică dacă utilizatorul este logat și admin
if (!isset($_SESSION['angajat_id']) || !$_SESSION['este_admin']) {
    die('Acces restrictionat!');
}

// Include biblioteca FPDF
$fpdf_path = __DIR__ . '/biblioteca/fpdf/fpdf.php';
if (file_exists($fpdf_path)) {
    require_once($fpdf_path);
} else {
    die('Biblioteca FPDF nu a fost gasita!');
}

// ==================== FUNCTIE PENTRU ELIMINARE DIACRITICE ====================
function removeDiacritics($text) {
    $diacritice = [
        // Românești
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't',
        'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ț' => 'T',
        'ş' => 's', 'ţ' => 't', 'Ş' => 'S', 'Ţ' => 'T',
        // Alte diacritice
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
        'ç' => 'c', 'Ç' => 'C',
        'ñ' => 'n', 'Ñ' => 'N',
        'ß' => 'ss'
    ];
    
    return strtr($text, $diacritice);
}

// ==================== FUNCȚII DE CALCUL ÎNTÂRZIERI ====================
function verificaIntarziereSimplu($angajat_id, $data_pontaj, $ora_pontaj, $conn) {
    // Căutăm în tabelul orar_angajati cu coloana corectă data_start
    $sql = "SELECT * FROM orar_angajati WHERE angajat_id = ? AND data_start = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$angajat_id, $data_pontaj]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$program) {
        return ['intarziere' => false, 'minute' => 0, 'ora_program' => '08:00', 'toleranta' => 15];
    }
    
    $ora_inceput = '08:00';
    $toleranta = 15;
    
    // Dacă avem tura_id, căutăm în tabelul TURE
    if (isset($program['tura_id']) && $program['tura_id']) {
        $sql_tura = "SELECT t.*, tp.ore_pe_zi 
                     FROM ture t 
                     LEFT JOIN tipuri_program tp ON t.tip_program_id = tp.id 
                     WHERE t.id = ?";
        $stmt_tura = $conn->prepare($sql_tura);
        $stmt_tura->execute([$program['tura_id']]);
        $tura = $stmt_tura->fetch(PDO::FETCH_ASSOC);
        
        if ($tura) {
            if (isset($tura['ora_start']) && !empty($tura['ora_start'])) {
                $ora_inceput = $tura['ora_start'];
            }
            
            if (isset($tura['ore_pe_zi'])) {
                $toleranta = ($tura['ore_pe_zi'] >= 12) ? 10 : 15;
            }
        }
    }
    
    // Calcul
    try {
        $ora_limita = strtotime($ora_inceput . " +{$toleranta} minutes");
        $ora_pontaj_timestamp = strtotime($ora_pontaj);
        
        if ($ora_pontaj_timestamp > $ora_limita) {
            $minute_intarziere = round(($ora_pontaj_timestamp - $ora_limita) / 60);
            return [
                'intarziere' => true, 
                'minute' => $minute_intarziere,
                'ora_program' => $ora_inceput,
                'toleranta' => $toleranta
            ];
        }
    } catch (Exception $e) {
        // Dacă apare eroare la calcul
    }
    
    return ['intarziere' => false, 'minute' => 0, 'ora_program' => $ora_inceput, 'toleranta' => $toleranta];
}

// ==================== CLASA PDF CU DIACRITICE ELIMINATE ====================
class RaportPDF extends FPDF {
    private $titluRaport;
    private $perioadaRaport;
    
    function setTitlu($titlu, $perioada = '') {
        $this->titluRaport = removeDiacritics($titlu);
        $this->perioadaRaport = removeDiacritics($perioada);
    }
    
    // Antet
    function Header() {
        // Titlu
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, removeDiacritics('Sistem Pontaj - ') . $this->titluRaport, 0, 1, 'C');
        
        // Perioada
        if (!empty($this->perioadaRaport)) {
            $this->SetFont('Arial', 'I', 12);
            $this->Cell(0, 10, $this->perioadaRaport, 0, 1, 'C');
        }
        
        // Linie separatoare
        $this->SetLineWidth(0.5);
        $this->Line(10, 30, 200, 30);
        $this->Ln(10);
    }
    
    // Footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, removeDiacritics('Pagina ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, removeDiacritics('Generat la: ') . date('d.m.Y H:i:s'), 0, 0, 'C');
    }
    
    // Suprascrie metoda Cell pentru a elimina diacritice
    function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '') {
        parent::Cell($w, $h, removeDiacritics($txt), $border, $ln, $align, $fill, $link);
    }
    
    // Suprascrie metoda MultiCell pentru a elimina diacritice
    function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false) {
        parent::MultiCell($w, $h, removeDiacritics($txt), $border, $align, $fill);
    }
}

// ==================== FUNCȚII PENTRU RAPOARTE PDF ====================

/**
 * Generează raport PDF pentru întârzieri
 */
function genereazaRaportIntarzieriPDF($conn, $data_start, $data_end, $angajat_id = null, $sortare = 'data_desc') {
    // Determină ordinea sortării
    $order_by = '';
    switch ($sortare) {
        case 'data_asc': $order_by = 'p.data_pontaj ASC'; break;
        case 'minute_desc': $order_by = 'minute_intarziere DESC'; break;
        case 'minute_asc': $order_by = 'minute_intarziere ASC'; break;
        case 'nume_asc': $order_by = 'a.nume ASC'; break;
        default: $order_by = 'p.data_pontaj DESC'; break;
    }
    
    // Interogare pentru întârzieri
    $sql = "SELECT p.data_pontaj, p.angajat_id, a.nume, 
                   TIME(p.data_pontaj) as ora_pontaj
            FROM pontaje p
            JOIN angajati a ON p.angajat_id = a.id
            WHERE DATE(p.data_pontaj) BETWEEN ? AND ?
            AND p.tip = 'intrare'";
    
    $params = [$data_start, $data_end];
    
    if ($angajat_id) {
        $sql .= " AND p.angajat_id = ?";
        $params[] = $angajat_id;
    }
    
    $sql .= " ORDER BY " . $order_by;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $pontaje = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesează pontările și calculează întârzierile
    $intarzieri = [];
    $total_minute = 0;
    
    foreach ($pontaje as $pontaj) {
        $intarziere = verificaIntarziereSimplu(
            $pontaj['angajat_id'],
            date('Y-m-d', strtotime($pontaj['data_pontaj'])),
            date('H:i:s', strtotime($pontaj['data_pontaj'])),
            $conn
        );
        
        if ($intarziere['intarziere']) {
            $intarzieri[] = [
                'data' => $pontaj['data_pontaj'],
                'nume' => $pontaj['nume'],
                'ora_pontaj' => date('H:i', strtotime($pontaj['data_pontaj'])),
                'ora_program' => $intarziere['ora_program'],
                'toleranta' => $intarziere['toleranta'],
                'minute_intarziere' => $intarziere['minute'],
                'tip_program' => ($intarziere['toleranta'] == 10) ? '12h' : '8h'
            ];
            $total_minute += $intarziere['minute'];
        }
    }
    
    // Crează PDF
    $pdf = new RaportPDF('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    
    $titlu = 'Raport Intarzieri';
    $perioada = date('d.m.Y', strtotime($data_start)) . ' - ' . date('d.m.Y', strtotime($data_end));
    
    if ($angajat_id) {
        $titlu .= ' - Angajat Specific';
        $sql_nume = "SELECT nume FROM angajati WHERE id = ?";
        $stmt_nume = $conn->prepare($sql_nume);
        $stmt_nume->execute([$angajat_id]);
        $angajat = $stmt_nume->fetch(PDO::FETCH_ASSOC);
        if ($angajat) {
            $perioada .= ' | ' . $angajat['nume'];
        }
    }
    
    $pdf->setTitlu($titlu, $perioada);
    $pdf->AddPage();
    
    // Statistici rapide
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, removeDiacritics('Statistici generale:'), 0, 1);
    $pdf->SetFont('Arial', '', 11);
    
    $pdf->Cell(60, 8, removeDiacritics('Total intarzieri: ') . count($intarzieri), 0, 0);
    $pdf->Cell(60, 8, removeDiacritics('Total minute: ') . $total_minute, 0, 0);
    $pdf->Cell(60, 8, removeDiacritics('Medie minute/intarziere: ') . (count($intarzieri) > 0 ? round($total_minute / count($intarzieri), 1) : 0), 0, 1);
    
    $pdf->Ln(5);
    
    // Tabel cu întârzieri
    if (empty($intarzieri)) {
        $pdf->SetFont('Arial', 'I', 12);
        $pdf->Cell(0, 10, removeDiacritics('Nu exista intarzieri inregistrate in aceasta perioada.'), 0, 1, 'C');
    } else {
        // Antet tabel
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(200, 200, 200);
        
        $pdf->Cell(25, 10, 'Data', 1, 0, 'C', true);
        $pdf->Cell(40, 10, removeDiacritics('Angajat'), 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Program', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Pontaj', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'Tip', 1, 0, 'C', true);
        $pdf->Cell(25, 10, removeDiacritics('Toleranta'), 1, 0, 'C', true);
        $pdf->Cell(25, 10, removeDiacritics('Intarziere'), 1, 1, 'C', true);
        
        $pdf->SetFont('Arial', '', 10);
        $fill = false;
        
        foreach ($intarzieri as $intarziere) {
            $pdf->SetFillColor($fill ? 240 : 255, 240, 255);
            
            $pdf->Cell(25, 8, date('d.m.Y', strtotime($intarziere['data'])), 1, 0, 'C', $fill);
            $pdf->Cell(40, 8, removeDiacritics($intarziere['nume']), 1, 0, 'L', $fill);
            $pdf->Cell(25, 8, $intarziere['ora_program'], 1, 0, 'C', $fill);
            $pdf->Cell(25, 8, $intarziere['ora_pontaj'], 1, 0, 'C', $fill);
            $pdf->Cell(25, 8, $intarziere['tip_program'], 1, 0, 'C', $fill);
            $pdf->Cell(25, 8, $intarziere['toleranta'] . ' min', 1, 0, 'C', $fill);
            
            // Colorați în funcție de gravitate
            if ($intarziere['minute_intarziere'] > 30) {
                $pdf->SetTextColor(255, 0, 0);
            } elseif ($intarziere['minute_intarziere'] > 15) {
                $pdf->SetTextColor(255, 140, 0);
            }
            
            $pdf->Cell(25, 8, $intarziere['minute_intarziere'] . ' min', 1, 1, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);
            
            $fill = !$fill;
        }
        
        // Rezumat final
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 10, removeDiacritics('Rezumat final:'), 0, 1);
        $pdf->SetFont('Arial', '', 10);
        
        // Grupează după angajat pentru statistici detaliate
        $statistici_angajati = [];
        foreach ($intarzieri as $intarziere) {
            $nume = $intarziere['nume'];
            if (!isset($statistici_angajati[$nume])) {
                $statistici_angajati[$nume] = ['count' => 0, 'total_minute' => 0];
            }
            $statistici_angajati[$nume]['count']++;
            $statistici_angajati[$nume]['total_minute'] += $intarziere['minute_intarziere'];
        }
        
        $pdf->Cell(0, 8, removeDiacritics('Statistici pe angajat:'), 0, 1);
        $pdf->SetFont('Arial', '', 9);
        
        foreach ($statistici_angajati as $nume => $stats) {
            $pdf->Cell(50, 6, removeDiacritics($nume), 0, 0);
            $pdf->Cell(30, 6, $stats['count'] . ' ' . removeDiacritics('intarzieri'), 0, 0);
            $pdf->Cell(40, 6, $stats['total_minute'] . ' ' . removeDiacritics('minute totale'), 0, 0);
            $pdf->Cell(30, 6, round($stats['total_minute'] / $stats['count'], 1) . ' ' . removeDiacritics('min/intarziere'), 0, 1);
        }
    }
    
    // Nume fișier
    $filename = 'raport_intarzieri_' . date('Y-m-d') . '.pdf';
    $pdf->Output('I', $filename);
}

/**
 * Generează raport PDF pentru pontaje
 */
function genereazaRaportPontajePDF($conn, $data_start, $data_end, $angajat_id = null, $tip_pontaj = 'toate') {
    // Construiește interogarea
    $sql = "SELECT p.*, a.nume FROM pontaje p 
            JOIN angajati a ON p.angajat_id = a.id 
            WHERE DATE(p.data_pontaj) BETWEEN ? AND ?";
    
    $params = [$data_start, $data_end];
    
    if ($angajat_id) {
        $sql .= " AND p.angajat_id = ?";
        $params[] = $angajat_id;
    }
    
    if ($tip_pontaj != 'toate') {
        $sql .= " AND p.tip = ?";
        $params[] = $tip_pontaj;
    }
    
    $sql .= " ORDER BY p.data_pontaj DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $pontaje = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crează PDF
    $pdf = new RaportPDF('L', 'mm', 'A4');
    $pdf->AliasNbPages();
    
    $titlu = 'Raport Pontaje';
    $perioada = date('d.m.Y', strtotime($data_start)) . ' - ' . date('d.m.Y', strtotime($data_end));
    
    if ($angajat_id) {
        $titlu .= ' - Angajat Specific';
    }
    
    if ($tip_pontaj != 'toate') {
        $titlu .= ' (' . ucfirst($tip_pontaj) . ')';
    }
    
    $pdf->setTitlu($titlu, $perioada);
    $pdf->AddPage();
    
    // Statistici
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, removeDiacritics('Statistici:'), 0, 1);
    $pdf->SetFont('Arial', '', 11);
    
    $total_pontaje = count($pontaje);
    $intrari = array_filter($pontaje, fn($p) => $p['tip'] == 'intrare');
    $iesiri = array_filter($pontaje, fn($p) => $p['tip'] == 'iesire');
    
    $pdf->Cell(0, 8, removeDiacritics('Total pontaje: ') . $total_pontaje, 0, 1);
    $pdf->Cell(0, 8, removeDiacritics('Intrari: ') . count($intrari), 0, 1);
    $pdf->Cell(0, 8, removeDiacritics('Iesiri: ') . count($iesiri), 0, 1);
    
    $pdf->Ln(5);
    
    // Tabel
    if (empty($pontaje)) {
        $pdf->SetFont('Arial', 'I', 12);
        $pdf->Cell(0, 10, removeDiacritics('Nu exista pontaje in aceasta perioada.'), 0, 1, 'C');
    } else {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        
        // Antet tabel (orizontal pentru mai mult spațiu)
        $pdf->Cell(15, 10, '#', 1, 0, 'C', true);
        $pdf->Cell(40, 10, removeDiacritics('Angajat'), 1, 0, 'C', true);
        $pdf->Cell(40, 10, removeDiacritics('Data si ora'), 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Tip', 1, 0, 'C', true);
        $pdf->Cell(40, 10, removeDiacritics('Ziua saptamanii'), 1, 0, 'C', true);
        $pdf->Cell(40, 10, removeDiacritics('Observatii'), 1, 1, 'C', true);
        
        $pdf->SetFont('Arial', '', 9);
        $fill = false;
        $counter = 1;
        
        foreach ($pontaje as $pontaj) {
            $pdf->SetFillColor($fill ? 240 : 255, 240, 255);
            
            $pdf->Cell(15, 8, $counter++, 1, 0, 'C', $fill);
            $pdf->Cell(40, 8, removeDiacritics($pontaj['nume']), 1, 0, 'L', $fill);
            $pdf->Cell(40, 8, date('d.m.Y H:i', strtotime($pontaj['data_pontaj'])), 1, 0, 'C', $fill);
            
            // Culoare diferită pentru tip
            if ($pontaj['tip'] == 'intrare') {
                $pdf->SetTextColor(0, 128, 0);
            } else {
                $pdf->SetTextColor(255, 140, 0);
            }
            
            $pdf->Cell(30, 8, ucfirst($pontaj['tip']), 1, 0, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);
            
            // Ziua săptămânii în română
            $zile = [
                'Monday' => 'Luni', 'Tuesday' => 'Marti', 'Wednesday' => 'Miercuri',
                'Thursday' => 'Joi', 'Friday' => 'Vineri', 'Saturday' => 'Sambata',
                'Sunday' => 'Duminica'
            ];
            $ziua_eng = date('l', strtotime($pontaj['data_pontaj']));
            $ziua_ro = $zile[$ziua_eng] ?? $ziua_eng;
            
            $pdf->Cell(40, 8, removeDiacritics($ziua_ro), 1, 0, 'C', $fill);
            
            // Verifică dacă e întârziere pentru intrări
            $observatii = '';
            if ($pontaj['tip'] == 'intrare') {
                $intarziere = verificaIntarziereSimplu(
                    $pontaj['angajat_id'],
                    date('Y-m-d', strtotime($pontaj['data_pontaj'])),
                    date('H:i:s', strtotime($pontaj['data_pontaj'])),
                    $conn
                );
                
                if ($intarziere['intarziere']) {
                    $observatii = removeDiacritics('Intarziat ') . $intarziere['minute'] . ' min';
                } else {
                    $observatii = removeDiacritics('La timp');
                }
            } else {
                $observatii = '-';
            }
            
            $pdf->Cell(40, 8, $observatii, 1, 1, 'C', $fill);
            
            $fill = !$fill;
        }
    }
    
    $filename = 'raport_pontaje_' . date('Y-m-d') . '.pdf';
    $pdf->Output('I', $filename);
}

/**
 * Generează raport PDF pentru ore lucrate
 */
function genereazaRaportOrePDF($conn, $luna, $an) {
    // Determină intervalul lunii
    $data_start = date('Y-m-01', strtotime("$an-$luna-01"));
    $data_end = date('Y-m-t', strtotime("$an-$luna-01"));
    
    // Interogare pentru ore lucrate
    $sql = "SELECT a.id, a.nume, 
                   COUNT(DISTINCT DATE(p.data_pontaj)) as zile_lucrate,
                   SEC_TO_TIME(SUM(
                       CASE 
                           WHEN p.tip = 'iesire' THEN 
                               TIMESTAMPDIFF(SECOND, 
                                   (SELECT MAX(data_pontaj) FROM pontaje p2 
                                    WHERE p2.angajat_id = p.angajat_id 
                                    AND DATE(p2.data_pontaj) = DATE(p.data_pontaj) 
                                    AND p2.tip = 'intrare' 
                                    AND p2.data_pontaj < p.data_pontaj),
                                   p.data_pontaj)
                           ELSE 0
                       END
                   )) as ore_lucrate
            FROM angajati a
            LEFT JOIN pontaje p ON a.id = p.angajat_id 
                AND DATE(p.data_pontaj) BETWEEN ? AND ?
            WHERE a.este_admin = 0
            GROUP BY a.id, a.nume
            ORDER BY a.nume";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$data_start, $data_end]);
    $angajati = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crează PDF
    $pdf = new RaportPDF('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    
    // Numele lunilor în română
    $luni_ro = [
        1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
        5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
        9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
    ];
    
    $nume_luna_ro = $luni_ro[$luna] ?? date('F', strtotime("$an-$luna-01"));
    $titlu = removeDiacritics("Raport Ore Lucrate - $nume_luna_ro $an");
    $perioada = removeDiacritics("Luna: $nume_luna_ro $an | Generat la: ") . date('d.m.Y H:i:s');
    
    $pdf->setTitlu($titlu, $perioada);
    $pdf->AddPage();
    
    // Informații generale
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, removeDiacritics('Rezumat lunar:'), 0, 1);
    $pdf->SetFont('Arial', '', 11);
    
    $total_angajati = count($angajati);
    $total_zile = array_sum(array_column($angajati, 'zile_lucrate'));
    
    $pdf->Cell(0, 8, removeDiacritics('Numar angajati: ') . $total_angajati, 0, 1);
    $pdf->Cell(0, 8, removeDiacritics('Total zile lucrate: ') . $total_zile, 0, 1);
    $pdf->Cell(0, 8, removeDiacritics('Perioada raport: ') . date('d.m.Y', strtotime($data_start)) . ' - ' . date('d.m.Y', strtotime($data_end)), 0, 1);
    
    $pdf->Ln(5);
    
    // Tabel cu orele
    if (empty($angajati)) {
        $pdf->SetFont('Arial', 'I', 12);
        $pdf->Cell(0, 10, removeDiacritics('Nu exista date pentru aceasta luna.'), 0, 1, 'C');
    } else {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        
        // Antet tabel
        $pdf->Cell(15, 10, '#', 1, 0, 'C', true);
        $pdf->Cell(60, 10, removeDiacritics('Angajat'), 1, 0, 'C', true);
        $pdf->Cell(40, 10, removeDiacritics('Zile lucrate'), 1, 0, 'C', true);
        $pdf->Cell(40, 10, removeDiacritics('Ore totale'), 1, 0, 'C', true);
        $pdf->Cell(35, 10, removeDiacritics('Ore/zi (medie)'), 1, 1, 'C', true);
        
        $pdf->SetFont('Arial', '', 9);
        $fill = false;
        $counter = 1;
        
        foreach ($angajati as $angajat) {
            $pdf->SetFillColor($fill ? 240 : 255, 240, 255);
            
            $pdf->Cell(15, 8, $counter++, 1, 0, 'C', $fill);
            $pdf->Cell(60, 8, removeDiacritics($angajat['nume']), 1, 0, 'L', $fill);
            $pdf->Cell(40, 8, $angajat['zile_lucrate'], 1, 0, 'C', $fill);
            
            // Formatează orele
            $ore_lucrate = '00:00';
            if ($angajat['ore_lucrate']) {
                $ore_lucrate = substr($angajat['ore_lucrate'], 0, 5);
            }
            
            $pdf->Cell(40, 8, $ore_lucrate, 1, 0, 'C', $fill);
            
            // Calculează media
            $media = '00:00';
            if ($angajat['zile_lucrate'] > 0 && $angajat['ore_lucrate']) {
                $total_secunde = strtotime($angajat['ore_lucrate']) - strtotime('TODAY');
                $media_secunde = $total_secunde / $angajat['zile_lucrate'];
                $media = gmdate('H:i', $media_secunde);
            }
            
            $pdf->Cell(35, 8, $media, 1, 1, 'C', $fill);
            
            $fill = !$fill;
        }
        
        // Rezumat final
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 10, removeDiacritics('Analiza statistica:'), 0, 1);
        $pdf->SetFont('Arial', '', 10);
        
        // Calculează statistici
        $ore_totale = 0;
        $zile_max = 0;
        $angajat_productiv = '';
        
        foreach ($angajati as $angajat) {
            if ($angajat['zile_lucrate'] > $zile_max) {
                $zile_max = $angajat['zile_lucrate'];
                $angajat_productiv = $angajat['nume'];
            }
            
            if ($angajat['ore_lucrate']) {
                $secunde = strtotime($angajat['ore_lucrate']) - strtotime('TODAY');
                $ore_totale += $secunde / 3600;
            }
        }
        
        $pdf->Cell(0, 8, removeDiacritics('Total ore lucrate companie: ') . round($ore_totale, 1) . ' ore', 0, 1);
        $pdf->Cell(0, 8, removeDiacritics('Angajat cu cele mai multe zile: ') . removeDiacritics($angajat_productiv) . ' (' . $zile_max . ' zile)', 0, 1);
        $pdf->Cell(0, 8, removeDiacritics('Medie ore/angajat: ') . ($total_angajati > 0 ? round($ore_totale / $total_angajati, 1) : 0) . ' ore', 0, 1);
    }
    
    $filename = 'raport_ore_' . $luna . '_' . $an . '.pdf';
    $pdf->Output('I', $filename);
}

// ==================== PROCESARE CERERE ====================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tip_raport = $_POST['tip_raport'] ?? '';
    $perioada = $_POST['perioada'] ?? '';
    
    // Determină intervalul de timp
    switch ($perioada) {
        case 'azi':
            $data_start = date('Y-m-d');
            $data_end = date('Y-m-d');
            break;
        case 'saptamana':
            $data_start = date('Y-m-d', strtotime('monday this week'));
            $data_end = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'luna':
            $data_start = date('Y-m-01');
            $data_end = date('Y-m-t');
            break;
        case 'personalizat':
            $data_start = $_POST['data_start'] ?? date('Y-m-d');
            $data_end = $_POST['data_end'] ?? date('Y-m-d');
            break;
        default:
            $data_start = date('Y-m-d');
            $data_end = date('Y-m-d');
    }
    
    // Verifică datele
    if (strtotime($data_start) > strtotime($data_end)) {
        $temp = $data_start;
        $data_start = $data_end;
        $data_end = $temp;
    }
    
    // Generează raportul corespunzător
    switch ($tip_raport) {
        case 'intarzieri':
            $angajat_id = !empty($_POST['angajat_id']) ? (int)$_POST['angajat_id'] : null;
            $sortare = $_POST['sortare'] ?? 'data_desc';
            genereazaRaportIntarzieriPDF($conn, $data_start, $data_end, $angajat_id, $sortare);
            break;
            
        case 'pontaje':
            $angajat_id = !empty($_POST['angajat_id']) ? (int)$_POST['angajat_id'] : null;
            $tip_pontaj = $_POST['tip_pontaj'] ?? 'toate';
            genereazaRaportPontajePDF($conn, $data_start, $data_end, $angajat_id, $tip_pontaj);
            break;
            
        case 'ore':
            $luna = (int)$_POST['luna'];
            $an = (int)$_POST['an'];
            genereazaRaportOrePDF($conn, $luna, $an);
            break;
            
        default:
            die('Tip raport invalid!');
    }
} else {
    die('Cerere invalida!');
}
?>