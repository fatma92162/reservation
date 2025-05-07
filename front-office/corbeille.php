<?php
session_start();

// Vérifier si l'utilisateur a le rôle 'client'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: index.php');
    exit();
}

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=velo_reservation', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Paramètres de pagination
    $items_per_page = 5;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $items_per_page;

    // Gérer l'action de récupération
    if (isset($_GET['action']) && $_GET['action'] === 'recuperer' && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $sql = "UPDATE reservation SET deleted_at = NULL WHERE id_reservation = :id AND id_client = :id_client";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->bindParam(':id_client', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Réservation récupérée avec succès.'
            ];
        } else {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Erreur : Réservation non trouvée ou vous n\'avez pas les permissions.'
            ];
        }
        header('Location: corbeille.php');
        exit();
    }

    // Compter le nombre total de réservations supprimées
    $countQuery = '
        SELECT COUNT(*) as total
        FROM reservation r
        WHERE r.id_client = :id_client AND r.deleted_at IS NOT NULL';
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->bindParam(':id_client', $_SESSION['user_id'], PDO::PARAM_INT);
    $countStmt->execute();
    $total_items = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $items_per_page);

    // Récupérer les réservations supprimées
    $query = '
        SELECT r.*, u.nom AS nom_utilisateur
        FROM reservation r
        LEFT JOIN utilisateur u ON r.id_client = u.id_utilisateur
        WHERE r.id_client = :id_client AND r.deleted_at IS NOT NULL
        ORDER BY r.deleted_at DESC
        LIMIT :offset, :items_per_page';
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_client', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug (commenter après test)
    /*
    echo 'Debug: user_id = ' . htmlspecialchars($_SESSION['user_id']) . '<br>';
    echo 'Debug: ' . count($reservations) . ' réservations supprimées trouvées.<pre>';
    print_r($reservations);
    echo '</pre>';
    */

} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corbeille des Réservations</title>
    <style>
        /* Réinitialisation globale */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Corps de la page */
        body {
            display: flex;
            min-height: 100vh;
            background-color: #F5F5F5;
            flex-direction: column;
        }

        /* Barre de tâches à gauche */
        .taskbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background-color: #1b5e20;
            color: #FFFFFF;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: space-between;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .taskbar-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .taskbar-logo h1 {
            font-size: 24px;
            font-weight: bold;
            color: #FFFFFF;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .taskbar-menu {
            width: 100%;
        }

        .taskbar-menu ul {
            list-style: none;
        }

        .taskbar-menu li {
            margin: 15px 0;
        }

        .taskbar-menu a {
            text-decoration: none;
            color: #FFFFFF;
            padding: 10px 20px;
            display: block;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-size: 16px;
            font-weight: 500;
        }

        .taskbar-menu a:hover {
            background-color: #2e7d32;
        }

        /* Contenu principal */
        main {
            margin-left: 250px;
            padding: 40px;
            background-color: #FFFFFF;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            flex: 1;
        }

        /* En-tête */
        .header {
            background-color: #FFFFFF;
            padding: 20px;
            border-bottom: 3px solid #1b5e20;
            text-align: center;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            color: #1b5e20;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        /* Notification */
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .notification.success {
            background-color: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #1b5e20;
        }
        .notification.error {
            background-color: #ffebee;
            color: #f44336;
            border: 1px solid #f44336;
        }

        /* Boutons */
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            display: inline-block;
            margin: 10px 5px;
        }

        .btn-add {
            background-color: #1b5e20;
            color: #FFFFFF;
        }

        .btn-add:hover {
            background-color: #2e7d32;
        }

        .btn-recover {
            background-color: #2196F3;
            color: #FFFFFF;
        }

        .btn-recover:hover {
            background-color: #1976D2;
        }

        .btn-logout {
            background-color: #f44336;
            color: #FFFFFF;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 16px;
        }

        .btn-logout:hover {
            background-color: #d32f2f;
        }

        /* Tableaux */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #F9F5E8;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        table thead {
            background-color: #1b5e20;
            color: #FFFFFF;
        }

        table tbody tr:hover {
            background-color: #f5f5f5;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 10px;
        }

        .pagination a {
            padding: 10px 15px;
            border: 1px solid #1b5e20;
            border-radius: 5px;
            text-decoration: none;
            color: #1b5e20;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background-color: #2e7d32;
            color: #FFFFFF;
        }

        .pagination .active {
            background-color: #1b5e20;
            color: #FFFFFF;
            border: 1px solid #1b5e20;
        }

        .pagination .disabled {
            color: #ccc;
            border: 1px solid #ccc;
            pointer-events: none;
        }

        /* Pied de page */
        .footer {
            background-color: #F9F5E8;
            padding: 15px 0;
            text-align: center;
            color: #60BA97;
            border-top: 3px solid #1b5e20;
        }

        /* Conteneur principal */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        h2 {
            color: #1b5e20;
            font-size: 24px;
            margin-bottom: 20px;
        }

        /* Message d'absence de données */
        p {
            color: #1b5e20;
            font-size: 16px;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .taskbar {
                width: 200px;
            }

            main {
                margin-left: 200px;
            }

            table th, table td {
                font-size: 14px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Barre de tâches à gauche -->
    <div class="taskbar">
        <div class="taskbar-logo">
            <h1>Green.tn</h1>
        </div>
        <div class="taskbar-menu">
            <ul>
                <li><a href="index.php"><span>🏠</span> Accueil</a></li>
                <li><a href="ajouter_reservation.php"><span>🚲</span> Réserver un Vélo</a></li>
                <li><a href="consulter_reservations.php"><span>📋</span> Mes Réservations</a></li>
                <li><a href="corbeille.php"><span>🗑️</span> Corbeille</a></li>
                <li><a href="historique.php"><span>🕒</span> Historique</a></li>
                <li><a href="logout.php"><span>🚪</span> Déconnexion</a></li>
            </ul>
        </div>
    </div>

    <main>
        <header class="header">
            <div class="container">
                <h1>Corbeille des Réservations</h1>
                <a href="logout.php" class="btn btn-logout" title="Déconnexion">🚪</a>
            </div>
        </header>

        <div class="container">
            <!-- Afficher la notification si elle existe -->
            <?php if (isset($_SESSION['notification'])): ?>
                <div class="notification <?= htmlspecialchars($_SESSION['notification']['type']); ?>">
                    <?= htmlspecialchars($_SESSION['notification']['message']); ?>
                </div>
                <?php unset($_SESSION['notification']); ?>
            <?php endif; ?>

            <h2>Réservations Supprimées</h2>
            <a href="consulter_reservations.php" class="btn btn-add" title="Retour aux Réservations">📋 Mes Réservations</a>

            <?php if (count($reservations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Réservation</th>
                            <th>ID Vélo</th>
                            <th>Date Début</th>
                            <th>Date Fin</th>
                            <th>Gouvernorat</th>
                            <th>Téléphone</th>
                            <th>Durée Réservation (jours)</th>
                            <th>Date Réservation</th>
                            <th>Date de Suppression</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation['id_reservation']); ?></td>
                                <td><?= htmlspecialchars($reservation['id_velo']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_debut']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_fin']); ?></td>
                                <td><?= htmlspecialchars($reservation['gouvernorat']); ?></td>
                                <td><?= htmlspecialchars($reservation['telephone']); ?></td>
                                <td><?= htmlspecialchars($reservation['duree_reservation']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_reservation']); ?></td>
                                <td><?= htmlspecialchars($reservation['deleted_at']); ?></td>
                                <td>
                                    <a href="corbeille.php?action=recuperer&id=<?= $reservation['id_reservation']; ?>" class="btn btn-recover" onclick="return confirm('Êtes-vous sûr de vouloir récupérer cette réservation ?');" title="Récupérer">🔄</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <?php
                    $prev_page = $page - 1;
                    $prev_class = $page == 1 ? 'disabled' : '';
                    $prev_url = $prev_page > 0 ? "corbeille.php?page=$prev_page" : "#";
                    echo "<a href='$prev_url' class='$prev_class'>⬅️</a>";

                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active_class = $i == $page ? 'active' : '';
                        $page_url = "corbeille.php?page=$i";
                        echo "<a href='$page_url' class='$active_class'>$i</a>";
                    }

                    $next_page = $page + 1;
                    $next_class = $page == $total_pages ? 'disabled' : '';
                    $next_url = $next_page <= $total_pages ? "corbeille.php?page=$next_page" : "#";
                    echo "<a href='$next_url' class='$next_class'>➡️</a>";
                    ?>
                </div>

            <?php else: ?>
                <p>Aucune réservation dans la corbeille.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© <?= date("Y"); ?> Green.tn</p>
        </div>
    </footer>
</body>
</html>