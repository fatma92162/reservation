
<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$sort_by = 'id_reservation'; // Tri par ID de réservation par défaut
$order = 'ASC'; // Ordre croissant par défaut
$search = ''; // Variable pour la recherche

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

    // Récupérer les réservations avec tri et recherche
    $query = '
    SELECT r.*, u.nom AS nom_utilisateur
    FROM reservation r
    INNER JOIN utilisateur u ON r.id_client = u.id_utilisateur';

    // Ajouter le filtre de recherche si un terme est saisi
    if ($search) {
        $query .= ' WHERE u.nom LIKE :search OR r.gouvernorat LIKE :search OR r.id_reservation LIKE :search';
    }

    $query .= ' ORDER BY ' . $sort_by . ' ' . $order;

    // Préparer la requête SQL
    $stmt = $pdo->prepare($query);

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
    <title>Dashboard Admin - Gestion des Réservations</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="header">
        <div class="container">
            <h1>Gestion des Réservations</h1>
            <a href="logout.php" class="btn btn-logout">Déconnexion</a>
        </div>
    </header>

    <main class="main-container">
        <div class="container">
            <h2>Liste des Réservations</h2>
            <a href="ajouter_reservation.php" class="btn btn-add">Ajouter une Réservation</a>

            <!-- Ajout du bouton Historique -->
            <a href="historique.php" class="btn btn-historique">Voir l'Historique</a>

            <!-- Formulaire de recherche -->
            <form method="get" action="" class="search-form">
                <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Rechercher par nom, gouvernorat ou ID" />
                <button type="submit">Rechercher</button>
            </form>

            <!-- Box de sélection du tri -->
            <form method="get" action="" class="sort-form">
                <label for="sort_by">Trier par :</label>
                <select name="sort_by" id="sort_by">
                    <option value="id_reservation" <?= $sort_by == 'id_reservation' ? 'selected' : ''; ?>>ID Réservation</option>
                    <option value="date_debut" <?= $sort_by == 'date_debut' ? 'selected' : ''; ?>>Date Début</option>
                    <option value="gouvernorat" <?= $sort_by == 'gouvernorat' ? 'selected' : ''; ?>>Gouvernorat</option>
                    <option value="nom_utilisateur" <?= $sort_by == 'nom_utilisateur' ? 'selected' : ''; ?>>Nom Utilisateur</option>
                </select>

                <label for="order">Ordre :</label>
                <select name="order" id="order">
                    <option value="ASC" <?= $order == 'ASC' ? 'selected' : ''; ?>>Croissant</option>
                    <option value="DESC" <?= $order == 'DESC' ? 'selected' : ''; ?>>Décroissant</option>
                </select>

                <button type="submit">Appliquer</button>
            </form>

            <?php if (count($reservations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Réservation</th>
                            <th>ID Client</th>
                            <th>ID Vélo</th>
                            <th>Date Début</th>
                            <th>Date Fin</th>
                            <th>Gouvernorat</th>
                            <th>Téléphone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation['id_reservation']); ?></td>
                                <td><?= htmlspecialchars($reservation['id_client']); ?></td>
                                <td><?= htmlspecialchars($reservation['id_velo']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_debut']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_fin']); ?></td>
                                <td><?= htmlspecialchars($reservation['gouvernorat']); ?></td>
                                <td><?= htmlspecialchars($reservation['telephone']); ?></td>
                                <td>
                                    <a href="modifier_reservation.php?id=<?= $reservation['id_reservation']; ?>" class="btn btn-edit">Modifier</a>
                                    <a href="supprimer_reservation.php?id=<?= $reservation['id_reservation']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucune réservation trouvée.</p>
            <?php endif; ?>

        </div>

        <!-- Section des statistiques -->
        <div class="stat-section">
            <h2>Statistiques des Gouvernorats</h2>
        </div>

        <!-- Cercle de statistiques pour chaque gouvernorat -->
        <div class="stat-circles-container">
            <?php foreach ($gouvernoratStats as $stat) {
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
            <p>&copy; <?= date("Y"); ?> Gestion des Réservations. Tous droits réservés.</p>
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
