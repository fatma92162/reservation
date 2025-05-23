<?php
session_start();

// Vérifier si l'utilisateur a le rôle 'client'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: index.php');
    exit();
}

// Définir le critère de tri par défaut
$sort_by = 'id_reservation'; // Tri par ID de réservation par défaut
$order = 'ASC'; // Ordre croissant par défaut
$search = ''; // Variable pour la recherche
$items_per_page = 5; // Nombre d'éléments par page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1; // Page actuelle
$offset = ($page - 1) * $items_per_page; // Calcul de l'offset

// Vérifier si un critère de tri est défini dans l'URL
if (isset($_GET['sort_by'])) {
    $sort_by = $_GET['sort_by'];
}
if (isset($_GET['order']) && in_array($_GET['order'], ['ASC', 'DESC'])) {
    $order = $_GET['order'];
}

// Vérifier si un terme de recherche est saisi
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=velo_reservation', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer le nombre total de réservations pour la pagination
    $countQuery = '
    SELECT COUNT(*) as total
    FROM reservation r
    INNER JOIN utilisateur u ON r.id_client = u.id_utilisateur
    WHERE r.id_client = :id_client';
    
    if ($search) {
        $countQuery .= ' AND (u.nom LIKE :search OR r.gouvernorat LIKE :search OR r.id_reservation LIKE :search)';
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->bindParam(':id_client', $_SESSION['user_id'], PDO::PARAM_INT);
    if ($search) {
        $searchTerm = "%" . $search . "%";
        $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total_items = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $items_per_page); // Calcul du nombre total de pages

    // Récupérer les réservations avec tri, recherche et pagination
    $query = '
    SELECT r.*, u.nom AS nom_utilisateur
    FROM reservation r
    INNER JOIN utilisateur u ON r.id_client = u.id_utilisateur
    WHERE r.id_client = :id_client';

    // Ajouter le filtre de recherche si un terme est saisi
    if ($search) {
        $query .= ' AND (u.nom LIKE :search OR r.gouvernorat LIKE :search OR r.id_reservation LIKE :search)';
    }

    $query .= ' ORDER BY ' . $sort_by . ' ' . $order;
    $query .= ' LIMIT :offset, :items_per_page';

    // Préparer la requête SQL
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_client', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    
    // Lier le paramètre de recherche
    if ($search) {
        $searchTerm = "%" . $search . "%";
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    }

    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les statistiques des gouvernorats
    $statQuery = '
    SELECT gouvernorat, COUNT(*) AS total
    FROM reservation
    GROUP BY gouvernorat
    ORDER BY total DESC
    ';
    $statStmt = $pdo->query($statQuery);
    $gouvernoratStats = $statStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer le nombre total de réservations
    $totalReservationsQuery = 'SELECT COUNT(*) AS total FROM reservation';
    $totalStmt = $pdo->query($totalReservationsQuery);
    $totalReservations = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulter les Réservations</title>
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

        .btn-add, .btn-historique, .btn-edit, .btn-detail {
            background-color: #1b5e20;
            color: #FFFFFF;
        }

        .btn-add:hover, .btn-historique:hover, .btn-edit:hover, .btn-detail:hover {
            background-color: #2e7d32;
        }

        .btn-delete {
            background-color: #f44336;
            color: #FFFFFF;
        }

        .btn-delete:hover {
            background-color: #d32f2f;
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

        /* Formulaires de recherche et de tri */
        .search-form, .sort-form {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }

        .search-form input, .sort-form select {
            padding: 10px;
            border: 1px solid #1b5e20;
            border-radius: 5px;
            font-size: 16px;
        }

        .search-form button, .sort-form button {
            padding: 10px 20px;
            background-color: #1b5e20;
            color: #FFFFFF;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .search-form button:hover, .sort-form button:hover {
            background-color: #2e7d32;
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

        /* Section des statistiques */
        .stat-section {
            margin-top: 40px;
            text-align: center;
        }

        .stat-section h2 {
            color: #1b5e20;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .stat-circles-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .stat-circle-container {
            text-align: center;
        }

        .stat-circle {
            width: 100px;
            height: 100px;
            background: conic-gradient(#1b5e20 calc(var(--value) * 1%), #ddd 0);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin: 0 auto;
        }

        .stat-circle::before {
            content: '';
            position: absolute;
            width: 80px;
            height: 80px;
            background-color: #F9F5E8;
            border-radius: 50%;
        }

        .stat-value {
            position: relative;
            font-size: 18px;
            font-weight: bold;
            color: #1b5e20;
        }

        .stat-info {
            margin-top: 10px;
        }

        .stat-info p {
            color: #1b5e20;
            font-size: 16px;
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

            .search-form, .sort-form {
                flex-direction: column;
            }

            .stat-circle {
                width: 80px;
                height: 80px;
            }

            .stat-circle::before {
                width: 60px;
                height: 60px;
            }

            .stat-value {
                font-size: 16px;
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
                <li><a href="historique.php"><span>🕒</span> Historique</a></li>
                <li><a href="logout.php"><span>🚪</span> Déconnexion</a></li>
            </ul>
        </div>
    </div>

    <main>
        <header class="header">
            <div class="container">
                <h1>Gestion des Réservations</h1>
                <a href="logout.php" class="btn btn-logout" title="Déconnexion">🚪</a>
            </div>
        </header>

        <div class="container">
            <!-- Afficher la notification si elle existe -->
            <?php if (isset($_SESSION['notification'])): ?>
                <div class="notification <?= htmlspecialchars($_SESSION['notification']['type']); ?>">
                    <?= htmlspecialchars($_SESSION['notification']['message']); ?>
                </div>
                <?php unset($_SESSION['notification']); // Supprimer la notification après affichage ?>
            <?php endif; ?>

            <h2>Liste des Réservations</h2>
            <a href="ajouter_reservation.php" class="btn btn-add" title="Ajouter une Réservation">➕</a>

            <!-- Bouton Historique -->
            <a href="historique.php" class="btn btn-historique" title="Voir l'Historique">🕒</a>

            <!-- Formulaire de recherche -->
            <form method="get" action="" class="search-form">
                <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Rechercher par nom, gouvernorat ou ID" />
                <button type="submit" title="Rechercher">🔍</button>
            </form>

            <!-- Box de sélection du tri -->
            <form method="get" action="" class="sort-form">
                <label for="sort_by">Trier par :</label>
                <select name="sort_by" id="sort_by">
                    <option value="id_reservation" <?= $sort_by == 'id_reservation' ? 'selected' : ''; ?>>ID Réservation</option>
                    <option value="date_debut" <?= $sort_by == 'date_debut' ? 'selected' : ''; ?>>Date Début</option>
                    <option value="gouvernorat" <?= $sort_by == 'gouvernorat' ? 'selected' : ''; ?>>Gouvernorat</option>
                    <!-- Option de tri par nom d'utilisateur retirée -->
                </select>

                <label for="order">Ordre :</label>
                <select name="order" id="order">
                    <option value="ASC" <?= $order == 'ASC' ? 'selected' : ''; ?>>Croissant</option>
                    <option value="DESC" <?= $order == 'DESC' ? 'selected' : ''; ?>>Décroissant</option>
                </select>

                <button type="submit" title="Appliquer">🔽</button>
            </form>

            <?php if (count($reservations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Réservation</th>
                            <th>ID Vélo</th>
                            <th>Nom Utilisateur</th>
                            <th>Date Début</th>
                            <th>Date Fin</th>
                            <th>Gouvernorat</th>
                            <th>Téléphone</th>
                            <th>Durée Réservation (jours)</th>
                            <th>Date Réservation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation['id_reservation']); ?></td>
                                <td><?= htmlspecialchars($reservation['id_velo']); ?></td>
                                <td></td> <!-- Champ nom d'utilisateur laissé vide -->
                                <td><?= htmlspecialchars($reservation['date_debut']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_fin']); ?></td>
                                <td><?= htmlspecialchars($reservation['gouvernorat']); ?></td>
                                <td><?= htmlspecialchars($reservation['telephone']); ?></td>
                                <td><?= htmlspecialchars($reservation['duree_reservation']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_reservation']); ?></td>
                                <td>
                                    <a href="modifier_reservation.php?id=<?= $reservation['id_reservation']; ?>" class="btn btn-edit" title="Modifier">✏️</a>
                                    <a href="supprimer_reservation.php?id=<?= $reservation['id_reservation']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');" title="Supprimer">🗑️</a>
                                    <a href="details_reservation.php?id=<?= $reservation['id_reservation']; ?>" class="btn btn-detail" title="Détails">ℹ️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <?php
                    // Lien "Précédent"
                    $prev_page = $page - 1;
                    $prev_class = $page == 1 ? 'disabled' : '';
                    $prev_url = $prev_page > 0 ? "consulter_reservations.php?page=$prev_page&sort_by=$sort_by&order=$order" . ($search ? "&search=" . urlencode($search) : "") : "#";
                    echo "<a href='$prev_url' class='$prev_class'>⬅️</a>";

                    // Liens numérotés pour les pages
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active_class = $i == $page ? 'active' : '';
                        $page_url = "consulter_reservations.php?page=$i&sort_by=$sort_by&order=$order" . ($search ? "&search=" . urlencode($search) : "");
                        echo "<a href='$page_url' class='$active_class'>$i</a>";
                    }

                    // Lien "Suivant"
                    $next_page = $page + 1;
                    $next_class = $page == $total_pages ? 'disabled' : '';
                    $next_url = $next_page <= $total_pages ? "consulter_reservations.php?page=$next_page&sort_by=$sort_by&order=$order" . ($search ? "&search=" . urlencode($search) : "") : "#";
                    echo "<a href='$next_url' class='$next_class'>➡️</a>";
                    ?>
                </div>

            <?php else: ?>
                <p>Aucune réservation trouvée.</p>
            <?php endif; ?>
        </div>

        <!-- Section des statistiques -->
        <div class="stat-section">
            <h2>Statistiques</h2>
        </div>

        <!-- Cercle de statistiques pour chaque gouvernorat -->
        <div class="stat-circles-container">
            <?php
            foreach ($gouvernoratStats as $stat) {
                $percentage = ($stat['total'] / $totalReservations) * 100;
            ?>
                <div class="stat-circle-container">
                    <div class="stat-circle" data-value="<?= round($percentage); ?>">
                        <span class="stat-value"><?= round($percentage); ?>%</span>
                    </div>
                    <div class="stat-info">
                        <p><?= htmlspecialchars($stat['gouvernorat']); ?></p>
                    </div>
                </div>
            <?php } ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© <?= date("Y"); ?> Green.tn</p>
        </div>
    </footer>

    <!-- Code JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.stat-circle').forEach(circle => {
                const value = circle.getAttribute('data-value');
                circle.style.setProperty('--value', value); // Applique dynamiquement la valeur au cercle
            });
        });
    </script>
</body>
</html>