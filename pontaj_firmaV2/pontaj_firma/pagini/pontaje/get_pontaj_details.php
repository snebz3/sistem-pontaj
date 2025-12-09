<?php
require_once '../../includes/auth.php';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

checkAccess('view_pontaje');

$pontaj_id = $_GET['id'] ?? 0;

$query = "SELECT p.*, a.nume, a.prenume, a.email, d.nume as departament_nume
          FROM pontaje p
          JOIN angajati a ON p.angajat_id = a.id
          LEFT JOIN departamente d ON a.departament_id = d.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->execute([$pontaj_id]);
$pontaj = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pontaj) {
    echo '<div class="alert alert-danger">Pontajul nu a fost găsit.</div>';
    exit;
}
?>

<div class="pontaj-details">
    <h6>Informații Pontaj</h6>
    <table class="table table-sm">
        <tr>
            <th>ID:</th>
            <td><?= $pontaj['id'] ?></td>
        </tr>
        <tr>
            <th>Angajat:</th>
            <td><?= htmlspecialchars($pontaj['nume'] . ' ' . $pontaj['prenume']) ?></td>
        </tr>
        <tr>
            <th>Email:</th>
            <td><?= htmlspecialchars($pontaj['email']) ?></td>
        </tr>
        <tr>
            <th>Departament:</th>
            <td><?= htmlspecialchars($pontaj['departament_nume'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <th>Data și Ora:</th>
            <td>
                <strong><?= date('d.m.Y', strtotime($pontaj['data_pontaj'])) ?></strong>
                <br>
                <?= date('H:i:s', strtotime($pontaj['data_pontaj'])) ?>
            </td>
        </tr>
        <tr>
            <th>Tip Pontaj:</th>
            <td>
                <?php if ($pontaj['tip'] == 'intrare'): ?>
                    <span class="badge bg-success">INTRARE</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">IEȘIRE</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Înregistrat la:</th>
            <td>
                <?php 
                $created = $pontaj['data_pontaj']; // Folosim aceeași dată
                echo date('d.m.Y H:i:s', strtotime($created));
                ?>
            </td>
        </tr>
    </table>
    
    <div class="mt-3">
        <small class="text-muted">
            <i class="fas fa-info-circle"></i> 
            Această înregistrare a fost creată automat la momentul pontării.
        </small>
    </div>
</div>