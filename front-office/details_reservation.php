<?php
session_start();

// Vérifier si l'utilisateur a le rôle 'client'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id'])) {
    die('ID de réservation non spécifié.');
}

$id_reservation = $_GET['id'];

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=velo_reservation', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les détails de la réservation
    $query = '
    SELECT r.*, u.nom AS nom_utilisateur
    FROM reservation r
    INNER JOIN utilisateur u ON r.id_client = u.id_utilisateur
    WHERE r.id_reservation = :id_reservation AND r.id_client = :id_client
    ';
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
    $stmt->bindParam(':id_client', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        die('Réservation non trouvée.');
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
    <title>Détails de la Réservation</title>
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

        /* Bouton Déconnexion */
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

        /* Pied de page */
        .footer {
            background-color: #F9F5E8;
            padding: 15px 0;
            text-align: center;
            color: #60BA97;
            border-top: 3px solid #1b5e20;
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
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </div>
    </div>

    <main>
        <header class="header">
            <div class="container">
                <h1>Détails de la Réservation</h1>
            </div>
        </header>

        <div class="container">
            <h2>Réservation #<?= htmlspecialchars($reservation['id_reservation']); ?></h2>

            <table>
                <thead>
                    <tr>
                        <th>Champ</th>
                        <th>Valeur</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th>ID Vélo</th>
                        <td><?= htmlspecialchars($reservation['id_velo']); ?></td>
                    </tr>
                    <tr>
                        <th>Nom Utilisateur</th>
                        <td><?= htmlspecialchars($reservation['nom_utilisateur']); ?></td>
                    </tr>
                    <tr>
                        <th>Date Début</th>
                        <td><?= htmlspecialchars($reservation['date_debut']); ?></td>
                    </tr>
                    <tr>
                        <th>Date Fin</th>
                        <td><?= htmlspecialchars($reservation['date_fin']); ?></td>
                    </tr>
                    <tr>
                        <th>Gouvernorat</th>
                        <td><?= htmlspecialchars($reservation['gouvernorat']); ?></td>
                    </tr>
                    <tr>
                        <th>Téléphone</th>
                        <td><?= htmlspecialchars($reservation['telephone']); ?></td>
                    </tr>
                    <tr>
                        <th>Durée Réservation (jours)</th>
                        <td><?= htmlspecialchars($reservation['duree_reservation']); ?></td>
                    </tr>
                    <tr>
                        <th>Date Réservation</th>
                        <td><?= htmlspecialchars($reservation['date_reservation']); ?></td>
                    </tr>
                </tbody>
            </table>

            <a href="consulter_reservations.php" class="btn btn-primary">Retour à la liste</a>
        </div>

        <footer class="footer">
            <div class="container">
                <p>© <?= date("Y"); ?> Vélo Rental</p>
            </div>
        </footer>
    </main>
</body>
</html>