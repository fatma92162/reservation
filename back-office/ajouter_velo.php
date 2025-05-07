<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php'; // Vérifiez bien le chemin

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Variable pour stocker les messages d'erreur ou de succès
$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_velo = trim($_POST['nom_velo'] ?? '');
    $type_velo = $_POST['type_velo'] ?? '';
    $etat_velo = $_POST['etat_velo'] ?? '';
    $prix_par_jour = $_POST['prix_par_jour'] ?? '';

    // Validation côté serveur
    if (empty($nom_velo)) {
        $message = "<p style='color: red;'>Erreur : Le nom du vélo est requis.</p>";
    } elseif (strlen($nom_velo) < 3) {
        $message = "<p style='color: red;'>Erreur : Le nom du vélo doit contenir au moins 3 caractères.</p>";
    } elseif (empty($type_velo)) {
        $message = "<p style='color: red;'>Erreur : Le type de vélo est requis.</p>";
    } elseif (empty($etat_velo)) {
        $message = "<p style='color: red;'>Erreur : L'état du vélo est requis.</p>";
    } elseif (empty($prix_par_jour) || $prix_par_jour <= 0) {
        $message = "<p style='color: red;'>Erreur : Le prix par jour doit être supérieur à 0.</p>";
    } else {
        try {
            // Préparation de la requête pour ajouter un vélo
            $query = "INSERT INTO velos (nom_velo, type_velo, etat_velo, prix_par_jour)
                      VALUES (:nom_velo, :type_velo, :etat_velo, :prix_par_jour)"; // AJOUTER EMAIL 
                      
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nom_velo', $nom_velo);
            $stmt->bindParam(':type_velo', $type_velo);
            $stmt->bindParam(':etat_velo', $etat_velo);
            $stmt->bindParam(':prix_par_jour', $prix_par_jour, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $message = "<p style='color: green;'>Vélo ajouté avec succès. Redirection...</p>";
                // Redirection après un court délai pour permettre à l'utilisateur de voir le message
                echo '<meta http-equiv="refresh" content="2;url=consulter_velos.php">';
            } else {
                $message = "<p style='color: red;'>Erreur lors de l'ajout du vélo.</p>";
            }
        } catch (PDOException $e) {
            $message = "<p style='color: red;'>Erreur de base de données : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Vélo</title>
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

        /* Formulaire */
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
            color: #2e7d32;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #60BA97;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        button:hover {
            background-color: #1b5e20;
        }

        .error-message {
            color: #e63946;
            font-size: 12px;
            margin-bottom: 10px;
            display: none;
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

            form {
                max-width: 90%;
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
                <h1>Ajouter un Vélo</h1>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </header>

        <?php if (!empty($message)) echo $message; ?>

        <form method="POST" id="velo-form">
            <label for="nom_velo">Nom du Vélo :</label>
            <input type="text" name="nom_velo" id="nom_velo">
            <span class="error-message" id="nom_velo-error"></span>

            <label for="type_velo">Type de Vélo :</label>
            <select name="type_velo" id="type_velo">
                <option value="VTT">VTT</option>
                <option value="Vélo de route">Vélo de route</option>
                <option value="Vélo électrique">Vélo électrique</option>
                <option value="Vélo pliant">Vélo pliant</option>
            </select>
            <span class="error-message" id="type_velo-error"></span>

            <label for="etat_velo">État du Vélo :</label>
            <select name="etat_velo" id="etat_velo">
                <option value="Neuf">Neuf</option>
                <option value="Bon état">Bon état</option>
                <option value="Réparable">Réparable</option>
            </select>
            <span class="error-message" id="etat_velo-error"></span>

            <label for="prix_par_jour">Prix par Jour (en euro) :</label>
            <input type="number" name="prix_par_jour" id="prix_par_jour" min="1">
            <span class="error-message" id="prix_par_jour-error"></span>

            <button type="submit">Ajouter Vélo</button>
        </form>

        <footer class="footer">
            <div class="container">
                <p class="footer-text">© 2025 Vélo Rental</p>
            </div>
        </footer>
    </main>

    <script>
        // Validation Functions
        function validateNomVelo() {
            const input = document.getElementById('nom_velo');
            const error = document.getElementById('nom_velo-error');
            const value = input.value.trim();

            if (!value) {
                error.textContent = 'Veuillez entrer le nom du vélo.';
                error.style.display = 'block';
                return false;
            }
            if (value.length < 3) {
                error.textContent = 'Le nom doit contenir au moins 3 caractères.';
                error.style.display = 'block';
                return false;
            }
            if (!/^[a-zA-Z0-9\s-]+$/.test(value)) {
                error.textContent = 'Le nom ne doit contenir que des lettres, chiffres, espaces ou tirets.';
                error.style.display = 'block';
                return false;
            }
            error.style.display = 'none';
            return true;
        }

        function validateTypeVelo() {
            const input = document.getElementById('type_velo');
            const error = document.getElementById('type_velo-error');
            const value = input.value;

            if (!value) {
                error.textContent = 'Veuillez sélectionner un type de vélo.';
                error.style.display = 'block';
                return false;
            }
            error.style.display = 'none';
            return true;
        }

        function validateEtatVelo() {
            const input = document.getElementById('etat_velo');
            const error = document.getElementById('etat_velo-error');
            const value = input.value;

            if (!value) {
                error.textContent = 'Veuillez sélectionner l\'état du vélo.';
                error.style.display = 'block';
                return false;
            }
            error.style.display = 'none';
            return true;
        }

        function validatePrixParJour() {
            const input = document.getElementById('prix_par_jour');
            const error = document.getElementById('prix_par_jour-error');
            const value = input.value;

            if (!value) {
                error.textContent = 'Veuillez entrer un prix par jour.';
                error.style.display = 'block';
                return false;
            }
            if (isNaN(value) || value <= 0) {
                error.textContent = 'Le prix doit être supérieur à 0.';
                error.style.display = 'block';
                return false;
            }
            error.style.display = 'none';
            return true;
        }

        // Event Listeners for Real-Time Validation
        document.getElementById('nom_velo').addEventListener('input', validateNomVelo);
        document.getElementById('type_velo').addEventListener('change', validateTypeVelo);
        document.getElementById('etat_velo').addEventListener('change', validateEtatVelo);
        document.getElementById('prix_par_jour').addEventListener('input', validatePrixParJour);

        // Form Submission Validation (sans alertes)
        document.getElementById('velo-form').addEventListener('submit', function(event) {
            const nomVelo = document.getElementById('nom_velo').value.trim();
            const typeVelo = document.getElementById('type_velo').value;
            const etatVelo = document.getElementById('etat_velo').value;
            const prixParJour = document.getElementById('prix_par_jour').value;

            // Vérifie chaque champ et affiche les messages d'erreur sous les champs
            if (!nomVelo) {
                event.preventDefault();
                validateNomVelo();
                return;
            }

            if (!typeVelo) {
                event.preventDefault();
                validateTypeVelo();
                return;
            }

            if (!etatVelo) {
                event.preventDefault();
                validateEtatVelo();
                return;
            }

            if (!prixParJour || prixParJour <= 0) {
                event.preventDefault();
                validatePrixParJour();
                return;
            }

            // Vérification finale pour s'assurer que tous les champs sont valides
            if (!validateNomVelo() || !validateTypeVelo() || !validateEtatVelo() || !validatePrixParJour()) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>