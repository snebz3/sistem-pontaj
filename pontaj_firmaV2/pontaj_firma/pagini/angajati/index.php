<?php
// Pornește sesiunea înainte de orice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../../includes/config.php';
include '../../includes/auth.php';
include '../../includes/functions.php';
checkAuth();
checkAdmin();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestiune Angajați - Sistem Pontaj</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
        .header { background: #343a40; color: white; padding: 15px; margin-bottom: 20px; }
        .header-content { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        
        /* Carduri pentru statistici */
        .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { margin: 0; color: #666; font-size: 14px; }
        .stat-card .number { font-size: 32px; font-weight: bold; margin: 10px 0; }
        .stat-card.total { background: #4CAF50; color: white; }
        .stat-card.total h3, .stat-card.total .number { color: white; }
        
        /* Căutare și filtre */
        .search-box { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .search-form { display: flex; flex-direction: column; gap: 15px; }
        .form-group { margin-bottom: 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; font-size: 14px; }
        input, select { width: 95%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        /* Butoane */
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        
        /* Tabel angajați */
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table th { background-color: #4CAF50; color: white; position: sticky; top: 0; }
        .table tr:hover { background-color: #f5f5f5; }
        .table .actions { white-space: nowrap; }
        
        /* Badge-uri */
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-admin { background: #dc3545; color: white; }
        .badge-active { background: #28a745; color: white; }
        .badge-inactive { background: #6c757d; color: white; }
        
        /* Paginare */
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a, .pagination span { display: inline-block; padding: 8px 16px; margin: 0 4px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .pagination a:hover { background: #0056b3; }
        .pagination .current { background: #0056b3; font-weight: bold; }
        .pagination .disabled { background: #6c757d; cursor: not-allowed; }
        
        /* Mesaje */
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        /* Layout pentru formular */
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="margin: 0;">👥 Gestiune Angajați</h1>
            <div>
                <span>Bun venit, <?php echo htmlspecialchars($_SESSION['nume'] ?? 'Admin'); ?>!</span>
                <span style="margin-left: 10px; background: #dc3545; padding: 2px 8px; border-radius: 10px; font-size: 12px;">ADMIN</span>
                <a href="../../dashboard.php" style="color: white; margin-left: 15px; text-decoration: none;">🏠 Dashboard</a>
                <a href="../../logout.php" style="color: white; margin-left: 15px; text-decoration: none;">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php
        // Mesaje de succes/eroare
        if (isset($_GET['success'])) {
            $message = htmlspecialchars($_GET['success']);
            echo '<div class="alert alert-success">✅ ' . $message . '</div>';
        }
        if (isset($_GET['error'])) {
            $message = htmlspecialchars($_GET['error']);
            echo '<div class="alert alert-error">❌ ' . $message . '</div>';
        }
        ?>
        
        <!-- Statistici -->
        <div class="stats-cards">
            <?php
            // Număr total angajați
            $total_query = $conn->query("SELECT COUNT(*) as total FROM angajati");
            $total = $total_query->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Număr admini
            $admin_query = $conn->query("SELECT COUNT(*) as admini FROM angajati WHERE este_admin = 1");
            $admini = $admin_query->fetch(PDO::FETCH_ASSOC)['admini'];
            
            // Număr pe departamente
            $dept_query = $conn->query("SELECT COUNT(DISTINCT departament) as departamente FROM angajati WHERE departament IS NOT NULL AND departament != ''");
            $departamente = $dept_query->fetch(PDO::FETCH_ASSOC)['departamente'];
            
            // Angajați activi (cu pontări în ultimele 30 de zile)
            $active_query = $conn->query("SELECT COUNT(DISTINCT a.id) as activi 
                FROM angajati a 
                JOIN pontaje p ON a.id = p.angajat_id 
                WHERE p.data_pontaj >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $activi = $active_query->fetch(PDO::FETCH_ASSOC)['activi'];
            ?>
            
            <div class="stat-card total">
                <h3>TOTAL ANGAJAȚI</h3>
                <div class="number"><?php echo $total; ?></div>
            </div>
            <div class="stat-card">
                <h3>ADMINI</h3>
                <div class="number"><?php echo $admini; ?></div>
            </div>
            <div class="stat-card">
                <h3>DEPARTAMENTE</h3>
                <div class="number"><?php echo $departamente; ?></div>
            </div>
            <div class="stat-card">
                <h3>ACTIVI (30 ZILE)</h3>
                <div class="number"><?php echo $activi; ?></div>
            </div>
        </div>
        
        <!-- Căutare și acțiuni -->
        <div class="search-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;">🔍 Căutare Angajați</h2>
                <div style="display: flex; gap: 10px;">
                    <a href="adauga.php" class="btn btn-success">➕ Adaugă Angajat Nou</a>
                </div>
            </div>
            
            <form method="GET" action="" id="searchForm" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="cauta_nume">Nume:</label>
                        <input type="text" id="cauta_nume" name="cauta_nume" 
                               value="<?php echo isset($_GET['cauta_nume']) ? htmlspecialchars($_GET['cauta_nume']) : ''; ?>"
                               placeholder="Caută după nume...">
                    </div>
                    
                    <div class="form-group">
                        <label for="departament">Departament:</label>
                        <select id="departament" name="departament">
                            <option value="">-- Toate departamentele --</option>
                            <?php
                            $dept_query = $conn->query("SELECT DISTINCT departament FROM angajati WHERE departament IS NOT NULL AND departament != '' ORDER BY departament");
                            $departamente = $dept_query->fetchAll(PDO::FETCH_ASSOC);
                            foreach($departamente as $dept) {
                                $selected = (isset($_GET['departament']) && $_GET['departament'] == $dept['departament']) ? 'selected' : '';
                                echo "<option value='{$dept['departament']}' $selected>{$dept['departament']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="este_admin">Rol:</label>
                        <select id="este_admin" name="este_admin">
                            <option value="">-- Toți angajații --</option>
                            <option value="1" <?php echo (isset($_GET['este_admin']) && $_GET['este_admin'] == '1') ? 'selected' : ''; ?>>Doar Admini</option>
                            <option value="0" <?php echo (isset($_GET['este_admin']) && $_GET['este_admin'] == '0') ? 'selected' : ''; ?>>Doar Angajați</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="sortare">Sortează după:</label>
                        <select id="sortare" name="sortare">
                            <option value="nume_asc" <?php echo (isset($_GET['sortare']) && $_GET['sortare'] == 'nume_asc') ? 'selected' : 'selected'; ?>>Nume A-Z</option>
                            <option value="nume_desc" <?php echo (isset($_GET['sortare']) && $_GET['sortare'] == 'nume_desc') ? 'selected' : ''; ?>>Nume Z-A</option>
                            <option value="data_desc" <?php echo (isset($_GET['sortare']) && $_GET['sortare'] == 'data_desc') ? 'selected' : ''; ?>>Data angajare (recent)</option>
                            <option value="data_asc" <?php echo (isset($_GET['sortare']) && $_GET['sortare'] == 'data_asc') ? 'selected' : ''; ?>>Data angajare (vechi)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 0; min-width: 150px; align-self: flex-end;">
                        <a href="index.php" class="btn" style="width: 75%; height: 42px; background: #6c757d; color: white;">🔄 Resetare</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Lista angajaților -->
        <div class="table-container">
            <?php
            // Construim query-ul pe baza filtrelor
            $sql = "SELECT a.*, tp.nume_program, 
                    (SELECT COUNT(*) FROM pontaje p WHERE p.angajat_id = a.id) as total_pontaje,
                    (SELECT MAX(data_pontaj) FROM pontaje WHERE angajat_id = a.id) as ultima_pontare
                    FROM angajati a
                    LEFT JOIN tipuri_program tp ON a.tip_program_id = tp.id
                    WHERE 1=1";
            
            $params = [];
            
            if (isset($_GET['cauta_nume']) && !empty($_GET['cauta_nume'])) {
                $sql .= " AND a.nume LIKE ?";
                $params[] = '%' . $_GET['cauta_nume'] . '%';
            }
            
            if (isset($_GET['departament']) && !empty($_GET['departament'])) {
                $sql .= " AND a.departament = ?";
                $params[] = $_GET['departament'];
            }
            
            if (isset($_GET['este_admin']) && $_GET['este_admin'] !== '') {
                $sql .= " AND a.este_admin = ?";
                $params[] = $_GET['este_admin'];
            }
            
            // Sortare
            $order_by = " ORDER BY a.nume ASC";
            if (isset($_GET['sortare'])) {
                switch($_GET['sortare']) {
                    case 'nume_desc': $order_by = " ORDER BY a.nume DESC"; break;
                    case 'data_desc': $order_by = " ORDER BY a.data_angajare DESC"; break;
                    case 'data_asc': $order_by = " ORDER BY a.data_angajare ASC"; break;
                    default: $order_by = " ORDER BY a.nume ASC"; break;
                }
            }
            $sql .= $order_by;
            
            // Paginare - obține totalul înainte
            $count_sql = "SELECT COUNT(*) as total FROM angajati a WHERE 1=1";
            $count_params = [];
            
            if (isset($_GET['cauta_nume']) && !empty($_GET['cauta_nume'])) {
                $count_sql .= " AND a.nume LIKE ?";
                $count_params[] = '%' . $_GET['cauta_nume'] . '%';
            }
            
            if (isset($_GET['departament']) && !empty($_GET['departament'])) {
                $count_sql .= " AND a.departament = ?";
                $count_params[] = $_GET['departament'];
            }
            
            if (isset($_GET['este_admin']) && $_GET['este_admin'] !== '') {
                $count_sql .= " AND a.este_admin = ?";
                $count_params[] = $_GET['este_admin'];
            }
            
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->execute($count_params);
            $total_results = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $results_per_page = 15;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            if ($page < 1) $page = 1;
            
            $total_pages = ceil($total_results / $results_per_page);
            if ($page > $total_pages && $total_pages > 0) {
                $page = $total_pages;
            }
            
            $offset = ($page - 1) * $results_per_page;
            
            // Adaugă LIMIT și OFFSET direct în SQL (nu ca parametri)
            $sql .= " LIMIT " . (int)$results_per_page . " OFFSET " . (int)$offset;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $angajati = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($angajati) > 0):
            ?>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nume</th>
                        <th>Email</th>
                        <th>Departament</th>
                        <th>Program</th>
                        <th>Rol</th>
                        <th>Pontări</th>
                        <th>Data Angajare</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($angajati as $angajat): 
                        // Formatare dată
                        $data_angajare = $angajat['data_angajare'] ? date('d.m.Y', strtotime($angajat['data_angajare'])) : '-';
                        $ultima_pontare = $angajat['ultima_pontare'] ? date('d.m.Y H:i', strtotime($angajat['ultima_pontare'])) : 'Niciodată';
                    ?>
                    <tr>
                        <td><?php echo $angajat['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($angajat['nume']); ?></strong>
                            <?php if($angajat['este_admin']): ?>
                                <span class="badge badge-admin">ADMIN</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($angajat['email']); ?></td>
                        <td><?php echo htmlspecialchars($angajat['departament'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($angajat['nume_program'] ?? 'Standard'); ?></td>
                        <td>
                            <?php if($angajat['este_admin']): ?>
                                <span class="badge badge-admin">Administrator</span>
                            <?php else: ?>
                                <span class="badge badge-active">Angajat</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo $angajat['total_pontaje']; ?></strong> pontări<br>
                            <small style="color: #666;">Ultima: <?php echo $ultima_pontare; ?></small>
                        </td>
                        <td><?php echo $data_angajare; ?></td>
                        <td class="actions">
                            <a href="detalii.php?id=<?php echo $angajat['id']; ?>" class="btn btn-sm btn-primary" title="Detalii">👁️</a>
                            <a href="editeaza.php?id=<?php echo $angajat['id']; ?>" class="btn btn-sm btn-warning" title="Editează">✏️</a>
                            <a href="sterge.php?id=<?php echo $angajat['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Șterge"
                               onclick="return confirm('Sigur doriți să ștergeți angajatul <?php echo addslashes($angajat['nume']); ?>? Această acțiune este ireversibilă!')">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginare -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">« Prev</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next »</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 15px; color: #666; font-size: 14px;">
                Afișare <?php echo min($results_per_page, count($angajati)); ?> din <?php echo $total_results; ?> angajați
            </div>
            
            <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <h3>📭 Nu s-au găsit angajați</h3>
                <p>Încearcă să schimbi filtrele de căutare sau adaugă un angajat nou.</p>
                <a href="adauga.php" class="btn btn-success">➕ Adaugă Primul Angajat</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Pentru a face butonul de căutare să funcționeze cu formularul
        document.addEventListener('DOMContentLoaded', function() {
            const searchButton = document.querySelector('button[form="searchForm"]');
            if (searchButton) {
                searchButton.addEventListener('click', function(e) {
                    // Butonul va trimite formularul automat datorită atributului form="searchForm"
                });
            }
        });
    </script>
</body>
</html>