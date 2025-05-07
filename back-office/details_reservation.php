<?php
session_start();

// Activer le débogage (à supprimer en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur a le rôle 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    error_log('Erreur de session : role=' . (isset($_SESSION['role']) ? $_SESSION['role'] : 'non défini'));
    $_SESSION['error'] = 'Vous devez être connecté en tant qu\'administrateur pour accéder à cette page.';
    header('Location: index.php');
    exit();
}

// Vérifier si l'ID de réservation est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    error_log('ID de réservation non spécifié ou invalide : ' . (isset($_GET['id']) ? $_GET['id'] : 'non défini'));
    $_SESSION['error'] = 'ID de réservation non spécifié ou invalide.';
    header('Location: dashboard.php');
    exit();
}

$id_reservation = (int)$_GET['id'];

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=velo_reservation', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les détails de la réservation (sans restriction sur id_client pour admin)
    $query = '
        SELECT r.*, u.nom AS nom_utilisateur
        FROM reservation r
        INNER JOIN utilisateur u ON r.id_client = u.id_utilisateur
        WHERE r.id_reservation = :id_reservation
    ';
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
    $stmt->execute();
    
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        error_log('Réservationfinding failed for id_reservation=' . $id_reservation);
        $_SESSION['error'] = 'Réservation non trouvée.';
        header('Location: dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    error_log('Erreur PDO : ' . $e->getMessage());
    $_SESSION['error'] = 'Erreur de connexion à la base de données.';
    header('Location: dashboard_reservations.php');
    exit();
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

        .taskbar-logo {
            text-align: center;
            margin-bottom: 20px;
            width: 100%;
        }

        .taskbar-logo h1 {
            font-size: 24px;
            font-weight: bold;
            color: #FFFFFF;
            font-family: "Bauhaus 93", Arial, sans-serif;
        }

        .taskbar-menu {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }

        .taskbar-menu ul {
            list-style: none;
            width: 100%;
        }

        .taskbar-menu li {
            margin: 15px 0;
            width: 100%;
        }

        .taskbar-menu a {
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

        .taskbar-menu a:hover {
            background-color: #1b5e20;
            color: #F9F5E8;
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
            font-family: "Bauhaus 93", Arial, sans-serif;
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
            text-align: center;
        }

        h2 {
            color: #2e7d32;
            font-size: 24px;
            margin-bottom: 20px;
        }

        /* Tableau */
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
            background-color: #60BA97;
            color: #FFFFFF;
        }

        table tbody tr:hover {
            background-color: #f5f5f5;
        }

        /* Boutons */
        .btn-primary {
            width: auto;
            padding: 10px 20px;
            background-color: #60BA97;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            background-color: #1b5e20;
        }

        /* Styles pour le statut */
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

        /* Messages d'erreur */
        .error-message {
            color: #e63946;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
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

        .footer p {
            color: #60BA97;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .taskbar {
                width: 220px;
                padding-top: 30px;
            }

            .taskbar-menu a {
                font-size: 16px;
                padding: 12px 20px;
            }

            main {
                margin-left: 240px;
            }

            .header h1 {
                font-size: 24px;
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
                <li><a href="ajouter_velo.php">Ajouter un Vélo</a></li>
                <li><a href="consulter_velos.php">Consulter les Vélos</a></li>
                <li><a href="dashboard.php">Voir les Réservations</a></li>
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
            <?php
            // Afficher les messages d'erreur s'il y en a
            if (isset($_SESSION['error'])) {
                echo '<p class="error-message">' . htmlspecialchars($_SESSION['error']) . '</p>';
                unset($_SESSION['error']);
            }
            ?>

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
                    <tr>
                        <th>Statut</th>
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
                                default:
                                    echo 'Inconnu';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <a href="dashboard.php" class="btn-primary">Retour à la liste</a>
        </div>

        <footer class="footer">
            <div class="container">
                <p>© <?= date("Y"); ?> Green.tn</p>
            </div>
        </footer>
    </main>
</body>
</html>