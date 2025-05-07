<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=velo_reservation', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Paramètres de pagination
    $velos_per_page = 5;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $velos_per_page;

    // Déterminer l'action à effectuer
    $action = isset($_GET['action']) ? $_GET['action'] : 'consulter';

    if ($action === 'supprimer' && isset($_GET['id']) && is_numeric($_GET['id'])) {
        // Soft delete
        $sql = "UPDATE velos SET deleted_at = NOW() WHERE id_velo = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Vélo supprimé avec succès et déplacé dans la corbeille.'
            ];
        } else {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Erreur : Vélo non trouvé.'
            ];
        }
        header('Location: gestion_velos.php');
        exit();
    }

    if ($action === 'recuperer' && isset($_GET['id']) && is_numeric($_GET['id'])) {
        // Récupérer le vélo
        $sql = "UPDATE velos SET deleted_at = NULL WHERE id_velo = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Vélo récupéré avec succès.'
            ];
        } else {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Erreur : Vélo non trouvé.'
            ];
        }
        header('Location: gestion_velos.php?action=corbeille');
        exit();
    }

    // Récupérer les vélos selon l'action
    if ($action === 'corbeille') {
        // Compter le nombre total de vélos supprimés
        $sql_count = "SELECT COUNT(*) FROM velos WHERE deleted_at IS NOT NULL";
        $stmt_count = $pdo->query($sql_count);
        $total_velos = $stmt_count->fetchColumn();
        $total_pages = ceil($total_velos / $velos_per_page);

        // Récupérer les vélos supprimés
        $sql = "SELECT * FROM velos WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $velos_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $velos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Récupérer les vélos actifs avec recherche et tri
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'id_velo';

        // Compter le nombre total de vélos actifs
        $sql_count = "SELECT COUNT(*) FROM velos WHERE nom_velo LIKE :search AND deleted_at IS NULL";
        $stmt_count = $pdo->prepare($sql_count);
        $stmt_count->execute(['search' => "%$search%"]);
        $total_velos = $stmt_count->fetchColumn();
        $total_pages = ceil($total_velos / $velos_per_page);

        // Récupérer les vélos actifs
        $sql = "SELECT * FROM velos WHERE nom_velo LIKE :search AND deleted_at IS NULL ORDER BY $sort_by ASC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $velos_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $velos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Statistiques
        $available_velos = count(array_filter($velos, fn($velo) => $velo['disponibilite']));
    }

} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'corbeille' ? 'Corbeille des Vélos' : 'Consulter les Vélos'; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #60BA97;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        .taskbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            background-color: #60BA97;
            color: #FFFFFF;
            padding-top: 40px;
            height: 100vh;
            box-shadow: 3px 0 6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
            border-radius: 10px;
        }

        .taskbar-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }

        .taskbar-item {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: #FFFFFF;
            font-size: 18px;
            font-weight: 500;
            padding: 15px 25px;
            width: 100%;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .taskbar-item:hover {
            background-color: #1b5e20;
            color: #F9F5E8;
        }

        .taskbar-item span {
            font-size: 24px;
            transition: transform 0.3s ease;
        }

        .taskbar-item:hover span {
            transform: scale(1.2);
        }

        main {
            flex: 1;
            margin-left: 270px;
            padding: 40px;
            background-color: #FFFFFF;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            min-height: 100vh;
        }

        .header {
            background-color: #F9F5E8;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
            border-bottom: 3px solid #2e7d32;
            margin-bottom: 30px;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header h1 {
            color: #2e7d32;
            font-size: 28px;
            font-weight: 700;
        }

        .header .btn-logout {
            background-color: #dc3545;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
        }

        .header .btn-logout:hover {
            background-color: #c82333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            color: #2e7d32;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-search-sort {
            background-color: #F9F5E8;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .form-search-sort input,
        .form-search-sort select {
            padding: 10px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-search-sort button {
            padding: 10px 20px;
            background-color: #60BA97;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .form-search-sort button:hover {
            background-color: #1b5e20;
        }

        .stats {
            background-color: #F9F5E8;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .stats canvas {
            max-width: 300px;
            margin: 0 auto 20px auto;
        }

        .stats p {
            font-size: 16px;
            color: #2e7d32;
            margin: 5px 0;
        }

        .btn-add {
            display: inline-block;
            padding: 10px 20px;
            background-color: #60BA97;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .btn-add:hover {
            background-color: #1b5e20;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #F9F5E8;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #60BA97;
            color: #fff;
            font-weight: bold;
        }

        td {
            color: #333;
        }

        .status.available {
            color: #4CAF50;
            font-weight: bold;
        }

        .status.unavailable {
            color: #e63946;
            font-weight: bold;
        }

        .btn-edit, .btn-delete, .btn-details, .btn-recover {
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            margin-right: 5px;
            transition: background-color 0.3s ease;
        }

        .btn-edit {
            background-color: #4CAF50;
            color: #fff;
        }

        .btn-edit:hover {
            background-color: #388E3C;
        }

        .btn-delete {
            background-color: #e63946;
            color: #fff;
        }

        .btn-delete:hover {
            background-color: #d32f2f;
        }

        .btn-details {
            background-color: #60BA97;
            color: #fff;
        }

        .btn-details:hover {
            background-color: #1b5e20;
        }

        .btn-recover {
            background-color: #2196F3;
            color: #fff;
        }

        .btn-recover:hover {
            background-color: #1976D2;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 10px 15px;
            background-color: #60BA97;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .pagination a:hover {
            background-color: #1b5e20;
        }

        .pagination a.disabled {
            background-color: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination a.active {
            background-color: #2e7d32;
            cursor: default;
        }

        .notification {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .notification.success {
            background-color: #d4edda;
            color: #155724;
        }

        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .notification .message {
            font-weight: bold;
        }

        .notification .timestamp {
            font-size: 12px;
            color: #6c757d;
        }

        .footer {
            background-color: #F9F5E8;
            padding: 20px 0;
            margin-top: auto;
            border-top: 3px solid #2e7d32;
            text-align: center;
        }

        .footer .container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .footer-text {
            color: #60BA97;
            text-align: center;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .taskbar {
                width: 220px;
                padding-top: 30px;
            }

            .taskbar-item {
                font-size: 16px;
                padding: 12px 20px;
            }

            .taskbar-item span {
                font-size: 20px;
            }

            main {
                margin-left: 240px;
            }

            .header h1 {
                font-size: 24px;
            }

            .form-search-sort {
                flex-direction: column;
                gap: 10px;
            }

            .form-search-sort input,
            .form-search-sort select,
            .form-search-sort button {
                width: 100%;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px;
            }

            .btn-edit, .btn-delete, .btn-details, .btn-recover {
                padding: 6px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="taskbar">
        <div class="taskbar-container">
            <a href="index.php" class="taskbar-item"><span>🏠</span> Accueil</a>
            <a href="dashboard.php" class="taskbar-item"><span>📊</span> Tableau de Bord</a>
            <a href="ajouter_velo.php" class="taskbar-item"><span>🚲</span> Ajouter un Vélo</a>
            <a href="gestion_velos.php" class="taskbar-item"><span>🔍</span> Consulter les Vélos</a>
            <a href="gestion_velos.php?action=corbeille" class="taskbar-item"><span>🗑️</span> Corbeille</a>
            <a href="logout.php" class="taskbar-item"><span>🚪</span> Déconnexion</a>
        </div>
    </div>

    <main>
        <header class="header">
            <div class="container">
                <h1><?php echo $action === 'corbeille' ? 'Corbeille des Vélos' : 'Gestion des Vélos'; ?></h1>
                <a href="logout.php" class="btn-logout">🚪 Déconnexion</a>
            </div>
        </header>

        <div class="container">
            <?php if (isset($_SESSION['notification'])): ?>
                <div class="notification <?php echo htmlspecialchars($_SESSION['notification']['type']); ?>">
                    <div class="message"><?php echo htmlspecialchars($_SESSION['notification']['message']); ?></div>
                    <div class="timestamp"><?php echo date('H:i, d M Y'); ?></div>
                </div>
                <?php unset($_SESSION['notification']); ?>
            <?php endif; ?>

            <?php if ($action === 'corbeille'): ?>
                <a href="gestion_velos.php" class="btn-add">🔍 Consulter les Vélos</a>
                <h2>Vélos Supprimés</h2>

                <?php if (count($velos) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Prix par jour (En euro)</th>
                                <th>Date de Suppression</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($velos as $velo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($velo['id_velo']); ?></td>
                                    <td><?php echo htmlspecialchars($velo['nom_velo']); ?></td>
                                    <td><?php echo htmlspecialchars($velo['type_velo']); ?></td>
                                    <td><?php echo number_format($velo['prix_par_jour'], 2, '.', ''); ?> €</td>
                                    <td><?php echo htmlspecialchars($velo['deleted_at']); ?></td>
                                    <td>
                                        <a href="gestion_velos.php?action=recuperer&id=<?php echo $velo['id_velo']; ?>" class="btn-recover" onclick="return confirm('Êtes-vous sûr de vouloir récupérer ce vélo ?');">🔄 Récupérer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?action=corbeille&page=<?php echo $current_page - 1; ?>">⬅️ Précédent</a>
                        <?php else: ?>
                            <a href="#" class="disabled">⬅️ Précédent</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?action=corbeille&page=<?php echo $i; ?>" class="<?php echo $i === $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="?action=corbeille&page=<?php echo $current_page + 1; ?>">➡️ Suivant</a>
                        <?php else: ?>
                            <a href="#" class="disabled">➡️ Suivant</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #e63946;">Aucun vélo dans la corbeille.</p>
                <?php endif; ?>

            <?php else: ?>
                <h2>Liste des Vélos</h2>

                <form action="" method="get" class="form-search-sort">
                    <input type="text" name="search" placeholder="Rechercher par nom" value="<?php echo htmlspecialchars($search); ?>">
                    <select name="sort_by">
                        <option value="id_velo" <?php echo $sort_by === 'id_velo' ? 'selected' : ''; ?>>Trier par ID</option>
                        <option value="nom_velo" <?php echo $sort_by === 'nom_velo' ? 'selected' : ''; ?>>Trier par nom</option>
                        <option value="prix_par_jour" <?php echo $sort_by === 'prix_par_jour' ? 'selected' : ''; ?>>Trier par prix</option>
                    </select>
                    <button type="submit">🔍 Rechercher</button>
                </form>

                <div class="stats">
                    <canvas id="veloStats" width="100" height="100"></canvas>
                    <p>Total des vélos : <?php echo $total_velos; ?></p>
                    <p>Vélos disponibles : <?php echo $available_velos; ?></p>
                </div>

                <a href="ajouter_velo.php" class="btn-add">🚲 Ajouter un Vélo</a>
                <a href="voir_reservations.php" class="btn-add">📋 Voir les Réservations</a>

                <?php if (count($velos) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Prix par jour (En euro)</th>
                                <th>Disponibilité</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($velos as $velo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($velo['id_velo']); ?></td>
                                    <td><?php echo htmlspecialchars($velo['nom_velo']); ?></td>
                                    <td><?php echo htmlspecialchars($velo['type_velo']); ?></td>
                                    <td><?php echo number_format($velo['prix_par_jour'], 2, '.', ''); ?> €</td>
                                    <td>
                                        <?php echo $velo['disponibilite'] ? '<span class="status available">Disponible</span>' : '<span class="status unavailable">Non disponible</span>'; ?>
                                    </td>
                                    <td>
                                        <a href="modifier_velo.php?id=<?php echo $velo['id_velo']; ?>" class="btn-edit">✏️ Modifier</a>
                                        <a href="gestion_velos.php?action=supprimer&id=<?php echo $velo['id_velo']; ?>" class="btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce vélo ? Il sera déplacé dans la corbeille.');">🗑️ Supprimer</a>
                                        <a href="details.php?velo=<?php echo $velo['id_velo']; ?>" class="btn-details">ℹ️ Détails</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>">⬅️ Précédent</a>
                        <?php else: ?>
                            <a href="#" class="disabled">⬅️ Précédent</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>" class="<?php echo $i === $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&sort_by=<?php echo $sort_by; ?>">➡️ Suivant</a>
                        <?php else: ?>
                            <a href="#" class="disabled">➡️ Suivant</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #e63946;">Aucun vélo trouvé.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <footer class="footer">
            <div class="container">
                <p class="footer-text">© <?php echo date("Y"); ?> Vélo Rental</p>
            </div>
        </footer>
    </main>

    <?php if ($action !== 'corbeille'): ?>
    <script>
        const ctx = document.getElementById('veloStats').getContext('2d');
        const total = <?php echo $total_velos; ?>;
        const available = <?php echo $available_velos; ?>;

        const data = {
            labels: ['Vélos disponibles', 'Vélos non disponibles'],
            datasets: [{
                data: [available, total - available],
                backgroundColor: ['#4CAF50', '#F44336'],
                borderColor: ['#388E3C', '#D32F2F'],
                borderWidth: 1
            }]
        };

        const config = {
            type: 'pie',
            data: data,
        };

        new Chart(ctx, config);
    </script>
    <?php endif; ?>
</body>
</html>