<?php
session_start();

// Vérifiez si l'utilisateur est connecté et a le rôle adéquat
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirigez si non connecté
    exit();
}

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=velo_reservation', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Préparer et exécuter la requête
    $query = "
    SELECT 
        r.id_reservation,
        r.id_velo,
        u.nom AS nom_utilisateur,
        r.date_debut,
        r.date_fin,
        r.gouvernorat,
        r.telephone,
        TIMESTAMPDIFF(DAY, r.date_debut, r.date_fin) AS duree_reservation
    FROM 
        reservation r
    INNER JOIN 
        utilisateur u 
    ON 
        r.id_client = u.id_utilisateur
    WHERE 
        r.id_client = ? 
        AND r.date_fin <= NOW()
    ORDER BY 
        r.date_fin DESC
    LIMIT 25;
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id']]); // Utilisez l'ID client
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Réservations</title>
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
        }

        .header h1 {
            font-size: 28px;
            color: #1b5e20;
            font-weight: bold;
            font-family: "Bauhaus 93", Arial, sans-serif;
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
            margin: 10px 0;
        }

        .btn-primary {
            background-color: #1b5e20;
            color: #FFFFFF;
        }

        .btn-primary:hover {
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
                <li><a href="index.php">Accueil</a></li>
                <li><a href="reserver_velo.php">Réserver un Vélo</a></li>
                <li><a href="consulter_reservations.php">Mes Réservations</a></li>
                <li><a href="historique_reservations.php">Historique</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </div>
    </div>

    <main>
        <header class="header">
            <div class="container">
                <h1>Historique des Réservations</h1>
            </div>
        </header>

        <div class="container">
            <h2>Liste des Réservations Terminées</h2>
            <?php if (!empty($reservations)): ?>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reservation['id_reservation']); ?></td>
                                <td><?= htmlspecialchars($reservation['id_velo']); ?></td>
                                <td><?= htmlspecialchars($reservation['nom_utilisateur']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_debut']); ?></td>
                                <td><?= htmlspecialchars($reservation['date_fin']); ?></td>
                                <td><?= htmlspecialchars($reservation['gouvernorat']); ?></td>
                                <td><?= htmlspecialchars($reservation['telephone']); ?></td>
                                <td><?= htmlspecialchars($reservation['duree_reservation']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucune réservation terminée trouvée.</p>
            <?php endif; ?>

            <a href="consulter_reservations.php" class="btn btn-primary">Retour au Tableau de Bord</a>
        </div>

        <footer class="footer">
            <div class="container">
                <p>© <?= date('Y'); ?> Green.tn</p>
            </div>
        </footer>
    </main>
</body>
</html>