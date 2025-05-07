<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php';

// Vérifier si l'utilisateur est connecté et a le rôle d'admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Initialiser les messages d'erreur ou de succès
$message = '';

// Vérifier si l'ID du vélo est passé dans l'URL
if (isset($_GET['id'])) {
    $id_velo = $_GET['id'];
    
    // Requête pour récupérer les informations du vélo
    $query = "SELECT * FROM velos WHERE id_velo = :id_velo";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_velo', $id_velo, PDO::PARAM_INT);
    $stmt->execute();
    $velo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$velo) {
        $message = "Vélo introuvable.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les valeurs du formulaire
    $id_velo = $_POST['id_velo'];
    $nom_velo = trim($_POST['nom_velo'] ?? '');
    $type_velo = trim($_POST['type_velo'] ?? '');
    $etat_velo = $_POST['etat_velo'] ?? '';
    $prix_par_jour = $_POST['prix_par_jour'] ?? '';
    $disponibilite = $_POST['disponibilite'] ?? '';

    // Validation côté serveur
    if (empty($nom_velo)) {
        $message = "Le nom du vélo est requis.";
    } elseif (strlen($nom_velo) < 3) {
        $message = "Le nom du vélo doit contenir au moins 3 caractères.";
    } elseif (empty($type_velo)) {
        $message = "Le type de vélo est requis.";
    } elseif (strlen($type_velo) < 3) {
        $message = "Le type de vélo doit contenir au moins 3 caractères.";
    } elseif (empty($etat_velo)) {
        $message = "L'état du vélo est requis.";
    } elseif (empty($prix_par_jour) || $prix_par_jour <= 0) {
        $message = "Le prix par jour doit être supérieur à 0.";
    } elseif (!isset($disponibilite)) {
        $message = "La disponibilité est requise.";
    } else {
        // Requête pour mettre à jour les informations du vélo
        $query = "UPDATE velos 
                  SET nom_velo = :nom_velo,
                      type_velo = :type_velo,
                      etat_velo = :etat_velo,
                      prix_par_jour = :prix_par_jour,
                      disponibilite = :disponibilite
                  WHERE id_velo = :id_velo";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_velo', $id_velo, PDO::PARAM_INT);
        $stmt->bindParam(':nom_velo', $nom_velo, PDO::PARAM_STR);
        $stmt->bindParam(':type_velo', $type_velo, PDO::PARAM_STR);
        $stmt->bindParam(':etat_velo', $etat_velo, PDO::PARAM_STR);
        $stmt->bindParam(':prix_par_jour', $prix_par_jour, PDO::PARAM_STR);
        $stmt->bindParam(':disponibilite', $disponibilite, PDO::PARAM_STR);

        // Exécuter la requête de mise à jour
        try {
            if ($stmt->execute()) {
                $message = "Vélo modifié avec succès. Redirection...";
                echo '<meta http-equiv="refresh" content="2;url=consulter_velos.php">';
            } else {
                $message = "Erreur lors de la modification.";
            }
        } catch (PDOException $e) {
            $message = "Erreur de base de données : " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Vélo</title>
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

        /* Messages d'erreur ou de succès */
        .message {
            color: #e63946;
            font-size: 16px;
            text-align: center;
            margin-bottom: 20px;
        }

        .message.success {
            color: #2e7d32;
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
                <h1>Modifier un Vélo</h1>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </header>

        <!-- Affichage des messages d'erreur ou de succès -->
        <?php if ($message): ?>
            <p class="message <?= strpos($message, 'succès') !== false ? 'success' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($velo)): ?>
        <form method="POST" id="velo-form">
            <input type="hidden" name="id_velo" value="<?= htmlspecialchars($velo['id_velo']) ?>">

            <label for="nom_velo">Nom du Vélo :</label>
            <input type="text" name="nom_velo" id="nom_velo" value="<?= htmlspecialchars($velo['nom_velo']) ?>" required>
            <span class="error-message" id="nom_velo-error"></span>

            <label for="type_velo">Type de Vélo :</label>
            <input type="text" name="type_velo" id="type_velo" value="<?= htmlspecialchars($velo['type_velo']) ?>" required>
            <span class="error-message" id="type_velo-error"></span>

            <label for="etat_velo">État du Vélo :</label>
            <select name="etat_velo" id="etat_velo" required>
                <option value="Disponible" <?= $velo['etat_velo'] == 'Disponible' ? 'selected' : '' ?>>Disponible</option>
                <option value="En réparation" <?= $velo['etat_velo'] == 'En réparation' ? 'selected' : '' ?>>En réparation</option>
                <option value="Indisponible" <?= $velo['etat_velo'] == 'Indisponible' ? 'selected' : '' ?>>Indisponible</option>
            </select>
            <span class="error-message" id="etat_velo-error"></span>

            <label for="prix_par_jour">Prix par Jour :</label>
            <input type="number" name="prix_par_jour" id="prix_par_jour" value="<?= htmlspecialchars($velo['prix_par_jour']) ?>" min="1" required>
            <span class="error-message" id="prix_par_jour-error"></span>

            <label for="disponibilite">Disponibilité :</label>
            <select name="disponibilite" id="disponibilite" required>
                <option value="1" <?= $velo['disponibilite'] == '1' ? 'selected' : '' ?>>Disponible</option>
                <option value="0" <?= $velo['disponibilite'] == '0' ? 'selected' : '' ?>>Indisponible</option>
            </select>
            <span class="error-message" id="disponibilite-error"></span>

            <button type="submit">Modifier</button>
        </form>
        <?php else: ?>
            <p class="message">Vélo introuvable.</p>
        <?php endif; ?>

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
            const value = input.value.trim();

            if (!value) {
                error.textContent = 'Veuillez entrer le type du vélo.';
                error.style.display = 'block';
                return false;
            }
            if (value.length < 3) {
                error.textContent = 'Le type doit contenir au moins 3 caractères.';
                error.style.display = 'block';
                return false;
            }
            if (!/^[a-zA-Z0-9\s-]+$/.test(value)) {
                error.textContent = 'Le type ne doit contenir que des lettres, chiffres, espaces ou tirets.';
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

        function validateDisponibilite() {
            const input = document.getElementById('disponibilite');
            const error = document.getElementById('disponibilite-error');
            const value = input.value;

            if (!value) {
                error.textContent = 'Veuillez sélectionner la disponibilité.';
                error.style.display = 'block';
                return false;
            }
            error.style.display = 'none';
            return true;
        }

        // Event Listeners for Real-Time Validation
        document.getElementById('nom_velo').addEventListener('input', validateNomVelo);
        document.getElementById('type_velo').addEventListener('input', validateTypeVelo);
        document.getElementById('etat_velo').addEventListener('change', validateEtatVelo);
        document.getElementById('prix_par_jour').addEventListener('input', validatePrixParJour);
        document.getElementById('disponibilite').addEventListener('change', validateDisponibilite);

        // Form Submission Validation
        document.getElementById('velo-form').addEventListener('submit', function(event) {
            const nomVelo = document.getElementById('nom_velo').value.trim();
            const typeVelo = document.getElementById('type_velo').value.trim();
            const etatVelo = document.getElementById('etat_velo').value;
            const prixParJour = document.getElementById('prix_par_jour').value;
            const disponibilite = document.getElementById('disponibilite').value;

            if (!nomVelo) {
                event.preventDefault();
                validateNomVelo();
                alert('Veuillez entrer le nom du vélo.');
                return;
            }

            if (!typeVelo) {
                event.preventDefault();
                validateTypeVelo();
                alert('Veuillez entrer le type du vélo.');
                return;
            }

            if (!etatVelo) {
                event.preventDefault();
                validateEtatVelo();
                alert('Veuillez sélectionner l\'état du vélo.');
                return;
            }

            if (!prixParJour || prixParJour <= 0) {
                event.preventDefault();
                validatePrixParJour();
                alert('Veuillez entrer un prix par jour valide.');
                return;
            }

            if (!disponibilite) {
                event.preventDefault();
                validateDisponibilite();
                alert('Veuillez sélectionner la disponibilité.');
                return;
            }

            // Additional validation to ensure all fields meet requirements
            if (!validateNomVelo() || !validateTypeVelo() || !validateEtatVelo() || !validatePrixParJour() || !validateDisponibilite()) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>