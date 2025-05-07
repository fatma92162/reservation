<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php'; // Vérifiez bien le chemin

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

// Vérifier si l'ID de la réservation est passé
if (isset($_GET['id'])) {
    $id_reservation = $_GET['id'];
    $query = "SELECT * FROM reservation WHERE id_reservation = :id_reservation";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
    $stmt->execute();
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reservation) {
        die("Réservation introuvable.");
    }
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reservation = $_POST['id_reservation'];
    $id_velo = $_POST['id_velo'];
    $id_client = $_POST['id_client'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $gouvernorat = $_POST['gouvernorat'];
    $telephone = $_POST['telephone'];

    // Requête pour mettre à jour les informations de la réservation
    $query = "UPDATE reservation 
              SET id_velo = :id_velo,
                  id_client = :id_client,
                  date_debut = :date_debut,
                  date_fin = :date_fin,
                  gouvernorat = :gouvernorat,
                  telephone = :telephone
              WHERE id_reservation = :id_reservation";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
    $stmt->bindParam(':id_velo', $id_velo, PDO::PARAM_INT);
    $stmt->bindParam(':id_client', $id_client, PDO::PARAM_INT);
    $stmt->bindParam(':date_debut', $date_debut, PDO::PARAM_STR);
    $stmt->bindParam(':date_fin', $date_fin, PDO::PARAM_STR);
    $stmt->bindParam(':gouvernorat', $gouvernorat, PDO::PARAM_STR);
    $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>Réservation modifiée avec succès.</p>";
        header("Location: consulter_reservations.php");
        exit;
    } else {
        echo "<p style='color: red;'>Erreur lors de la modification de la réservation.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Réservation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #F5F5F5;
            flex-direction: column;
        }

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

        .taskbar-logo h1 {
            font-size: 24px;
            font-weight: bold;
            color: #FFFFFF;
            text-align: center;
            margin-bottom: 20px;
        }

        .taskbar-menu ul {
            list-style: none;
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

        main {
            margin-left: 250px;
            padding: 40px;
            background-color: #FFFFFF;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            flex: 1;
        }

        form {
            background-color: #F9F5E8;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 0 auto;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            color: #1b5e20;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 5px;
            border: 1px solid #1b5e20;
            border-radius: 5px;
            font-size: 16px;
        }

        .error-message {
            color: #f44336;
            font-size: 12px;
            margin-bottom: 10px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #1b5e20;
            color: #FFFFFF;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        button:hover {
            background-color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="taskbar">
        <div class="taskbar-logo">
            <h1>Green.tn</h1>
        </div>
        <div class="taskbar-menu">
            <ul>
                <li><a href="index.php">🏠 Accueil</a></li>
                <li><a href="reserver_velo.php">🚲 Réserver un Vélo</a></li>
                <li><a href="consulter_reservations.php">📋 Mes Réservations</a></li>
                <li><a href="historique_reservations.php">🕒 Historique</a></li>
                <li><a href="logout.php">🚪 Déconnexion</a></li>
            </ul>
        </div>
    </div>

    <main>
        <form method="POST">
            <input type="hidden" name="id_reservation" value="<?= $reservation['id_reservation'] ?>">

            <label for="id_velo">ID du Vélo :</label>
            <input type="number" name="id_velo" value="<?= $reservation['id_velo'] ?>">
            <span class="error-message"></span>

            <label for="id_client">ID du Client :</label>
            <input type="number" name="id_client" value="<?= $reservation['id_client'] ?>">
            <span class="error-message"></span>

            <label for="date_debut">Date de Début :</label>
            <input type="date" name="date_debut" value="<?= $reservation['date_debut'] ?>">
            <span class="error-message"></span>

            <label for="date_fin">Date de Fin :</label>
            <input type="date" name="date_fin" value="<?= $reservation['date_fin'] ?>">
            <span class="error-message"></span>

            <label for="gouvernorat">Gouvernorat :</label>
            <input type="text" name="gouvernorat" value="<?= $reservation['gouvernorat'] ?>">
            <span class="error-message"></span>

            <label for="telephone">Téléphone :</label>
            <input type="tel" name="telephone" pattern="[0-9]{8}" value="<?= $reservation['telephone'] ?>">
            <span class="error-message"></span>

            <button type="submit" title="Modifier">✏️</button>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');

            form.addEventListener('submit', function (event) {
                let isValid = true;
                const errorMessages = {
                    id_velo: "Veuillez entrer un ID de vélo valide.",
                    id_client: "Veuillez entrer un ID de client valide.",
                    date_debut: "Veuillez sélectionner une date de début valide.",
                    date_fin: "Veuillez sélectionner une date de fin valide et postérieure à la date de début.",
                    gouvernorat: "Veuillez entrer un gouvernorat valide (au moins 3 caractères).",
                    telephone: "Veuillez entrer un numéro de téléphone valide (8 chiffres).",
                };

                const idVelo = form.querySelector('[name="id_velo"]');
                const idClient = form.querySelector('[name="id_client"]');
                const dateDebut = form.querySelector('[name="date_debut"]');
                const dateFin = form.querySelector('[name="date_fin"]');
                const gouvernorat = form.querySelector('[name="gouvernorat"]');
                const telephone = form.querySelector('[name="telephone"]');

                form.querySelectorAll('.error-message').forEach(el => el.textContent = '');

                if (!idVelo.value || isNaN(idVelo.value) || parseInt(idVelo.value) <= 0) {
                    isValid = false;
                    showError(idVelo, errorMessages.id_velo);
                }

                if (!idClient.value || isNaN(idClient.value) || parseInt(idClient.value) <= 0) {
                    isValid = false;
                    showError(idClient, errorMessages.id_client);
                }

                if (!dateDebut.value) {
                    isValid = false;
                    showError(dateDebut, errorMessages.date_debut);
                }
                if (!dateFin.value || dateDebut.value > dateFin.value) {
                    isValid = false;
                    showError(dateFin, errorMessages.date_fin);
                }

                if (!gouvernorat.value || gouvernorat.value.trim().length < 3) {
                    isValid = false;
                    showError(gouvernorat, errorMessages.gouvernorat);
                }

                if (!telephone.value || !/^\d{8}$/.test(telephone.value)) {
                    isValid = false;
                    showError(telephone, errorMessages.telephone);
                }

                if (!isValid) {
                    event.preventDefault();
                }
            });

            function showError(input, message) {
                const errorContainer = input.nextElementSibling;
                if (errorContainer && errorContainer.classList.contains('error-message')) {
                    errorContainer.textContent = message;
                }
            }
        });
    </script>
</body>
</html>
