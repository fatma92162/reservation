<?php
session_start();

// Vérifier si l'utilisateur a le rôle 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

    // Liste blanche pour les colonnes de tri
    $valid_sort_columns = ['id_reservation', 'date_debut', 'gouvernorat'];
    if (!in_array($sort_by, $valid_sort_columns)) {
        $sort_by = 'id_reservation';
    }

    // Récupérer le nombre total de réservations pour la pagination
    $countQuery = '
        SELECT COUNT(*) as total
        FROM reservation r
        INNER JOIN utilisateur u ON r.id_client = u.id_utilisateur';
    
    if ($search) {
        $countQuery .= ' WHERE u.nom LIKE :search OR r.gouvernorat LIKE :search OR r.id_reservation LIKE :search';
    }

    $countStmt = $pdo->prepare($countQuery);
    if ($search) {
        $searchTerm = "%" . $search . "%";
        $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total_items = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $items_per_page); // Calcul du nombre total de pages

    // Récupérer toutes les réservations avec tri, recherche et pagination
    $query = '
        SELECT r.*, u.nom AS nom_utilisateur
        FROM reservation r
        INNER JOIN utilisateur u ON r.id_client = u.id_utilisateur';
    
    if ($search) {
        $query .= ' WHERE u.nom LIKE :search OR r.gouvernorat LIKE :search OR r.id_reservation LIKE :search';
    }

    $query .= ' ORDER BY ' . $sort_by . ' ' . $order;
    $query .= ' LIMIT :offset, :items_per_page';

    // Préparer la requête SQL
    $stmt = $pdo->prepare($query);
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
            font-family: Arial, sans-serif;
        }

        /* Corps de la page */
        body {
            background-color: #60BA97;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        /* Barre de tâches à gauche */
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

        /* Contenu principal */
        main {
            flex: 1;
            margin-left: 270px;
            padding: 40px;
            background-color: #FFFFFF;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            min-height: 100vh;
        }

        /* Header */
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

        /* Conteneur principal */
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

        /* Formulaire de recherche et tri */
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

        /* Tableau des réservations */
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

        .status-pending {
            color: #ff9800;
            font-weight: bold;
        }

        .status-accepted {
            color: #4CAF50;
            font-weight: bold;
        }

        .status-refused {
            color: #e63946;
            font-weight: bold;
        }

        .btn-accept, .btn-refuse, .btn-details {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            margin-right: 8px;
            transition: all 0.3s ease;
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            border: 1px solid transparent;
        }

        .btn-accept {
            background-color: #4CAF50;
            color: #fff;
        }

        .btn-accept:hover {
            background-color: #388E3C;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            border-color: #2e7d32;
        }

        .btn-accept::before {
            content: '\2713'; /* Icône coche */
            font-size: 14px;
            margin-right: 8px;
            opacity: 0.8;
        }

        .btn-refuse {
            background-color: #e63946;
            color: #fff;
        }

        .btn-refuse:hover {
            background-color: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            border-color: #b71c1c;
        }

        .btn-refuse::before {
            content: '\2717'; /* Icône croix */
            font-size: 14px;
            margin-right: 8px;
            opacity: 0.8;
        }

        .btn-details {
            background-color: #60BA97;
            color: #fff;
        }

        .btn-details:hover {
            background-color: #1b5e20;
        }

        .btn-disabled {
            background-color: #ccc;
            color: #666;
            cursor: not-allowed;
            pointer-events: none;
            box-shadow: none;
            border: none;
        }

        /* Pagination */
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

        /* Section des statistiques */
        .stat-section {
            margin-top: 40px;
            text-align: center;
        }

        .stat-section h2 {
            color: #2e7d32;
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
            background: conic-gradient(#60BA97 calc(var(--value) * 1%), #ddd 0);
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
            color: #2e7d32;
        }

        .stat-info {
            margin-top: 10px;
        }

        .stat-info p {
            color: #2e7d32;
            font-size: 16px;
        }

        /* Footer */
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

        .footertext {
            color: #60BA97;
            text-align: center;
            font-size: 14px;
        }

        /* Responsive */
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

            .btn-accept, .btn-refuse, .btn-details {
                padding: 8px 12px;
                font-size: 13px;
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
        <div class="taskbar-container">
            <a href="index.php" class="taskbar-item"><span>🏠</span> Accueil</a>
            <a href="dashboard.php" class="taskbar-item"><span>📊</span> Tableau de Bord</a>
            <a href="ajouter_velo.php" class="taskbar-item"><span>🚲</span> Ajouter un Vélo</a>
            <a href="consulter_velos.php" class="taskbar-item"><span>🔍</span> Consulter les Vélos</a>
            <a href="voir_reservations.php" class="taskbar-item"><span>📋</span> Voir les Réservations</a>
            <a href="logout.php" class="taskbar-item"><span>🚪</span> Déconnexion</a>
        </div>
    </div>

    <!-- Contenu principal -->
    <main>
        <header class="header">
            <div class="container">
                <h1>Gestion des vélo </h1>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </header>

        <div class="container">
            <h2>Liste des Réservations</h2>

            <!-- Formulaire de recherche et de tri -->
            <form action="" method="get" class="form-search-sort">
                <input type="text" name="search" placeholder="Rechercher par nom, gouvernorat ou ID" value="<?php echo htmlspecialchars($search); ?>">
                <select name="sort_by">
                    <option value="id_reservation" <?php echo $sort_by === 'id_reservation' ? 'selected' : ''; ?>>ID Réservation</option>
                    <option value="date_debut" <?php echo $sort_by === 'date_debut' ? 'selected' : ''; ?>>Date Début</option>
                    <option value="gouvernorat" <?php echo $sort_by === 'gouvernorat' ? 'selected' : ''; ?>>Gouvernorat</option>
                </select>
                <select name="order">
                    <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Croissant</option>
                    <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Décroissant</option>
                </select>
                <button type="submit">Rechercher</button>
            </form>

            <!-- Tableau des réservations -->
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
                            <th>Durée (jours)</th>
                            <th>Date Réservation</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['id_reservation']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['id_velo']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['nom_utilisateur']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_debut']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_fin']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['gouvernorat']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['telephone']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['duree_reservation']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_reservation']); ?></td>
                                <td>
                                    <?php
                                    switch ($reservation['statut']) {
                                        case 'en_attente':
                                            echo '<span class="status-pending">En attente</span>';
                                            break;
                                        case 'acceptee':
                                            echo '<span class="status-accepted">Acceptée</span>';
                                            break;
                                        case 'refusee':
                                            echo '<span class="status-refused">Refusée</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($reservation['statut'] === 'en_attente'): ?>
                                        <a href="accepter_reservation.php?id=<?php echo $reservation['id_reservation']; ?>" class="btn-accept">Accepter</a>
                                        <a href="refuser_reservation.php?id=<?php echo $reservation['id_reservation']; ?>" class="btn-refuse" onclick="return confirm('Êtes-vous sûr de vouloir refuser cette réservation ?');">Refuser</a>
                                    <?php else: ?>
                                        <span class="btn-accept btn-disabled">Accepter</span>
                                        <span class="btn-refuse btn-disabled">Refuser</span>
                                    <?php endif; ?>
                                    <a href="details_reservation.php?id=<?php echo $reservation['id_reservation']; ?>" class="btn-details">Détails</a>
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
                    $prev_url = $prev_page > 0 ? "voir_reservations.php?page=$prev_page&sort_by=$sort_by&order=$order" . ($search ? "&search=" . urlencode($search) : "") : "#";
                    echo "<a href='$prev_url' class='$prev_class'>Précédent</a>";

                    // Liens numérotés pour les pages
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active_class = $i == $page ? 'active' : '';
                        $page_url = "voir_reservations.php?page=$i&sort_by=$sort_by&order=$order" . ($search ? "&search=" . urlencode($search) : "");
                        echo "<a href='$page_url' class='$active_class'>$i</a>";
                    }

                    // Lien "Suivant"
                    $next_page = $page + 1;
                    $next_class = $page == $total_pages ? 'disabled' : '';
                    $next_url = $next_page <= $total_pages ? "voir_reservations.php?page=$next_page&sort_by=$sort_by&order=$order" . ($search ? "&search=" . urlencode($search) : "") : "#";
                    echo "<a href='$next_url' class='$next_class'>Suivant</a>";
                    ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #e63946;">Aucune réservation trouvée.</p>
            <?php endif; ?>

            <!-- Section des statistiques -->
            <div class="stat-section">
                <h2>Statistiques des Réservations</h2>
            </div>

            <!-- Cercle de statistiques pour chaque gouvernorat -->
            <div class="stat-circles-container">
                <?php
                foreach ($gouvernoratStats as $stat) {
                    $percentage = ($stat['total'] / $totalReservations) * 100;
                ?>
                    <div class="stat-circle-container">
                        <div class="stat-circle" data-value="<?php echo round($percentage); ?>">
                            <span class="stat-value"><?php echo round($percentage); ?>%</span>
                        </div>
                        <div class="stat-info">
                            <p><?php echo htmlspecialchars($stat['gouvernorat']); ?></p>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <footer class="footer">
            <div class="container">
                <p class="footer-text">© <?php echo date("Y"); ?> Green.TN</p>
            </div>
        </footer>
    </main>

    <!-- Script JavaScript pour les cercles de statistiques -->
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