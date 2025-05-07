<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails des Vélos</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Réinitialisation globale */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', Arial, sans-serif;
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

        /* Formulaire */
        form {
            background-color: #F9F5E8;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 0 auto 40px auto;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s ease;
        }

        form:hover {
            transform: translateY(-3px);
        }

        label {
            font-size: 20px;
            color: #2e7d32;
            font-weight: 700;
        }

        select {
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
            border: 2px solid #4CAF50;
            background-color: #f8f9fa;
            color: #333;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: #2e7d32;
        }

        .btn-submit {
            padding: 12px 25px;
            background-color: #60BA97;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-submit:hover {
            background-color: #1b5e20;
            transform: translateY(-2px);
        }

        /* Conteneur de détails */
        .details-container {
            background-color: #F9F5E8;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
            transition: transform 0.2s ease;
        }

        .details-container:hover {
            transform: translateY(-3px);
        }

        .details-container h2 {
            color: #2e7d32;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .details-container p {
            font-size: 18px;
            margin: 12px 0;
            color: #555;
        }

        .details-container strong {
            color: #2e7d32;
            font-weight: 700;
        }

        /* Message d'erreur */
        .error {
            color: #e63946;
            font-size: 16px;
            margin-top: 20px;
            font-weight: 500;
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

        .footer-text {
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

            form, .details-container {
                max-width: 90%;
            }

            form {
                flex-direction: column;
                gap: 10px;
            }

            label, select, .btn-submit {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Barre de tâches à gauche -->
    <div class="taskbar">
        <div class="taskbar-container">
            <a href="index.php" class="taskbar-item"><span>🏠</span> Accueil</a>
            <a href="consulter_velos.php" class="taskbar-item"><span>🚴</span> Consulter Vélos</a>
            <a href="ajouter_velo.php" class="taskbar-item"><span>➕</span> Ajouter Vélo</a>
            <a href="logout.php" class="taskbar-item"><span>🔒</span> Déconnexion</a>
        </div>
    </div>

    <!-- Contenu principal -->
    <main>
        <header class="header">
            <div class="container">
                <h1>Détails des Vélos</h1>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </header>

        <form method="GET" action="">
            <label for="velo">Sélectionnez un vélo :</label>
            <select name="velo" id="velo">
                <option value="1">Vélo de Montagne</option>
                <option value="2">Vélo de Ville</option>
                <option value="3">Vélo Électrique</option>
            </select>
            <button type="submit" class="btn-submit">Afficher les détails</button>
        </form>

        <div class="details-container">
            <?php
            // Exemple de données pour les vélos
            $veloData = [
                1 => [
                    "nom" => "Vélo de Montagne",
                    "type" => "Sport",
                    "prix" => "20 €/jour",
                    "description" => "Parfait pour les terrains accidentés."
                ],
                2 => [
                    "nom" => "Vélo de Ville",
                    "type" => "Urbain",
                    "prix" => "15 €/jour",
                    "description" => "Idéal pour les déplacements en ville."
                ],
                3 => [
                    "nom" => "Vélo Électrique",
                    "type" => "Assistance électrique",
                    "prix" => "25 €/jour",
                    "description" => "Confort et facilité pour de longs trajets."
                ]
            ];

            // Vérifie si un vélo a été sélectionné et affiche les détails
            if (isset($_GET['velo']) && array_key_exists($_GET['velo'], $veloData)) {
                $id = $_GET['velo'];
                $details = $veloData[$id];

                echo "<h2>Détails pour le vélo sélectionné :</h2>";
                echo "<p><strong>Nom :</strong> {$details['nom']}</p>";
                echo "<p><strong>Type :</strong> {$details['type']}</p>";
                echo "<p><strong>Prix :</strong> {$details['prix']}</p>";
                echo "<p><strong>Description :</strong> {$details['description']}</p>";
            } elseif (isset($_GET['velo'])) {
                echo "<p class='error'>Vélo non trouvé. Veuillez vérifier l'ID du vélo.</p>";
            }
            ?>
        </div>

        <footer class="footer">
            <div class="container">
                <p class="footer-text">© 2025 Vélo Rental</p>
            </div>
        </footer>
    </main>
</body>
</html>