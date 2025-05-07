<?php
session_start();
include 'C:\xampp\htdocs\projet\connextion.php'; // Vérifiez bien le chemin

// Afficher les erreurs pour déboguer
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    error_log("Utilisateur non connecté ou rôle incorrect : " . ($_SESSION['role'] ?? 'non défini'));
    header("Location: login.php");
    exit;
}

// Initialiser les messages d'erreur
$errors = [];

// Vérifier si une requête AJAX est envoyée pour valider les dates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_dates') {
    $dateDebut = $_POST['date_debut'];
    $dateFin = $_POST['date_fin'];

    // Validation côté serveur des dates
    if ($dateFin <= $dateDebut) {
        echo json_encode(['error' => "La date de fin doit être postérieure à la date de début."]);
        exit;
    }

    try {
        $query = "
            SELECT v.id_velo, v.nom_velo
            FROM velos v
            WHERE v.disponibilite >= 1
            AND v.id_velo NOT IN (
                SELECT r.id_velo
                FROM reservation r
                WHERE 
                    (r.date_debut BETWEEN :dateDebut AND :dateFin)
                    OR (r.date_fin BETWEEN :dateDebut AND :dateFin)
                    OR (:dateDebut BETWEEN r.date_debut AND r.date_fin)
                    OR (:dateFin BETWEEN r.date_debut AND r.date_fin)
            )
        ";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':dateDebut', $dateDebut);
        $stmt->bindParam(':dateFin', $dateFin);
        $stmt->execute();
        $velosDisponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($velosDisponibles);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => "Erreur lors de la récupération des vélos : " . $e->getMessage()]);
        exit;
    }
}

// Traitement du formulaire pour ajouter une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_reservation') {
    $id_velo = $_POST['id_velo'] ?? '';
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin = $_POST['date_fin'] ?? '';
    $gouvernorat = $_POST['gouvernorat'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $captcha = $_POST['captcha'] ?? '';

    // Débogage : Vérifier les valeurs
    error_log("CAPTCHA soumis : " . $captcha);
    error_log("CAPTCHA attendu : " . ($_SESSION['captcha_code'] ?? 'non défini'));

    // Validation côté serveur
    if (empty($date_debut)) {
        $errors['date_debut'] = "Tu dois choisir une date.";
    }
    if (empty($date_fin) || $date_fin <= $date_debut) {
        $errors['date_fin'] = "La date de fin doit être postérieure à la date de début.";
    }
    if (empty($id_velo)) {
        $errors['id_velo'] = "Veuillez sélectionner un vélo.";
    }
    if (empty($gouvernorat)) {
        $errors['gouvernorat'] = "Veuillez sélectionner un gouvernorat.";
    }
    if (empty($telephone) || !preg_match('/^\d{8}$/', $telephone)) {
        $errors['telephone'] = "Le numéro de téléphone doit contenir exactement 8 chiffres.";
    }
    if (empty($captcha) || strlen($captcha) !== 6) {
        $errors['captcha'] = "Le code CAPTCHA doit contenir 6 caractères.";
    } elseif (!isset($_SESSION['captcha_code']) || strtoupper($captcha) !== strtoupper($_SESSION['captcha_code'])) {
        $errors['captcha'] = "Code CAPTCHA incorrect.";
    }

    if (empty($errors)) {
        $duree_reservation = (strtotime($date_fin) - strtotime($date_debut)) / (60 * 60 * 24);

        try {
            $query = "
                INSERT INTO reservation (
                    id_client, id_velo, date_debut, date_fin, gouvernorat, telephone, duree_reservation, date_reservation
                ) VALUES (
                    :id_client, :id_velo, :date_debut, :date_fin, :gouvernorat, :telephone, :duree_reservation, NOW()
                )
            ";
            $stmt = $conn->prepare($query);

            $stmt->bindParam(':id_client', $_SESSION['user_id']);
            $stmt->bindParam(':id_velo', $id_velo);
            $stmt->bindParam(':date_debut', $date_debut);
            $stmt->bindParam(':date_fin', $date_fin);
            $stmt->bindParam(':gouvernorat', $gouvernorat);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':duree_reservation', $duree_reservation);

            if ($stmt->execute()) {
                // Réinitialiser le CAPTCHA après une soumission réussie
                unset($_SESSION['captcha_code']);
                // Définir un message de succès dans la session
                $_SESSION['notification'] = [
                    'type' => 'success',
                    'message' => 'Réservation ajoutée avec succès !'
                ];
                // Rediriger vers consulter_reservations.php
                echo "<script>window.location.href='consulter_reservations.php';</script>";
                exit;
            } else {
                $errors['general'] = "Erreur lors de l'ajout de la réservation.";
            }
        } catch (PDOException $e) {
            $errors['general'] = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Réservation</title>
    <style>
        /* Réinitialisation suisse */
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
            color: #1b5e20;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #1b5e20;
            border-radius: 5px;
            font-size: 16px;
        }

        /* Style pour le placeholder */
        input::placeholder {
            color: #1b5e20;
            opacity: 0.7;
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

        .hidden {
            display: none;
        }

        .error-message {
            color: #f44336;
            font-size: 12px;
            margin-bottom: 10px;
            display: none;
            text-align: center;
        }

        .error-message.active {
            display: block;
        }

        /* Styles pour le CAPTCHA */
        .captcha-container {
            margin: 15px 0;
            text-align: center;
        }
        .captcha-image {
            border: 2px solid #1b5e20;
            border-radius: 5px;
            margin-bottom: 10px;
            width: 200px;
            height: 60px;
        }
        .refresh-captcha {
            background-color: #60BA97;
            color: #FFFFFF;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            border: none;
            margin-top: 5px;
            width: auto;
            display: inline-block;
        }
        .refresh-captcha:hover {
            background-color: #2e7d32;
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

        /* Responsive */
        @media (max-width: 768px) {
            .taskbar {
                width: 200px;
            }

            main {
                margin-left: 200px;
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
                <h1>Ajouter une Réservation</h1>
            </div>
        </header>

        <div class="container">
            <?php if (!empty($errors['general'])): ?>
                <p style="color: red; text-align: center; margin-bottom: 20px;"><?php echo $errors['general']; ?></p>
            <?php endif; ?>
            <form method="POST" id="reservation-form">
                <input type="hidden" name="action" value="add_reservation">

                <label for="date_debut">Date Début :</label>
                <input type="date" id="date_debut" name="date_debut" required value="<?php echo isset($_POST['date_debut']) ? htmlspecialchars($_POST['date_debut']) : ''; ?>">
                <span class="error-message" id="date_debut-error"><?php echo $errors['date_debut'] ?? ''; ?></span>

                <label for="date_fin">Date Fin :</label>
                <input type="date" id="date_fin" name="date_fin" required value="<?php echo isset($_POST['date_fin']) ? htmlspecialchars($_POST['date_fin']) : ''; ?>">
                <span class="error-message" id="date_fin-error"><?php echo $errors['date_fin'] ?? ''; ?></span>

                <div id="extra-fields" class="<?php echo !empty($_POST['id_velo']) ? '' : 'hidden'; ?>">
                    <label for="id_velo">Vélo Disponible :</label>
                    <select name="id_velo" id="id_velo" >
                        <option value="" disabled selected>-- Sélectionnez un vélo --</option>
                        <?php if (!empty($_POST['id_velo'])): ?>
                            <?php
                            // Requête pour recharger les vélos disponibles si le formulaire a été soumis
                            $dateDebut = $_POST['date_debut'];
                            $dateFin = $_POST['date_fin'];
                            $query = "
                                SELECT v.id_velo, v.nom_velo
                                FROM velos v
                                WHERE v.disponibilite >= 1
                                AND v.id_velo NOT IN (
                                    SELECT r.id_velo
                                    FROM reservation r
                                    WHERE 
                                        (r.date_debut BETWEEN :dateDebut AND :dateFin)
                                        OR (r.date_fin BETWEEN :dateDebut AND :dateFin)
                                        OR (:dateDebut BETWEEN r.date_debut AND r.date_fin)
                                        OR (:dateFin BETWEEN r.date_debut AND r.date_fin)
                                )
                            ";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':dateDebut', $dateDebut);
                            $stmt->bindParam(':dateFin', $dateFin);
                            $stmt->execute();
                            $velosDisponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($velosDisponibles as $velo) {
                                $selected = ($velo['id_velo'] == $_POST['id_velo']) ? 'selected' : '';
                                echo "<option value=\"{$velo['id_velo']}\" $selected>{$velo['nom_velo']}</option>";
                            }
                            ?>
                        <?php endif; ?>
                    </select>
                    <span class="error-message" id="id_velo-error"><?php echo $errors['id_velo'] ?? ''; ?></span>

                    <label for="gouvernorat">Gouvernorat :</label>
                    <select name="gouvernorat" id="gouvernorat" >
                        <option value="" disabled selected>-- Sélectionnez un gouvernorat --</option>
                        <option value="Tunis" <?php echo (isset($_POST['gouvernorat']) && $_POST['gouvernorat'] == 'Tunis') ? 'selected' : ''; ?>>Tunis</option>
                        <option value="Sfax" <?php echo (isset($_POST['gouvernorat']) && $_POST['gouvernorat'] == 'Sfax') ? 'selected' : ''; ?>>Sfax</option>
                        <option value="Sousse" <?php echo (isset($_POST['gouvernorat']) && $_POST['gouvernorat'] == 'Sousse') ? 'selected' : ''; ?>>Sousse</option>
                        <option value="Gabès" <?php echo (isset($_POST['gouvernorat']) && $_POST['gouvernorat'] == 'Gabès') ? 'selected' : ''; ?>>Gabès</option>
                    </select>
                    <span class="error-message" id="gouvernorat-error"><?php echo $errors['gouvernorat'] ?? ''; ?></span>

                    <label for="telephone">Téléphone :</label>
                    <input type="text" name="telephone" id="telephone" value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                    <span class="error-message" id="telephone-error"><?php echo $errors['telephone'] ?? ''; ?></span>

                    <!-- Champ CAPTCHA -->
                    <div class="captcha-container">
                        <label for="captcha">Vérification CAPTCHA :</label>
                        <img src="captcha.php" alt="CAPTCHA" class="captcha-image" id="captcha-image" onerror="this.style.display='none'; document.getElementById('captcha-error').textContent='Erreur lors du chargement du CAPTCHA.'; document.getElementById('captcha-error').style.display='block';">
                        <input type="text" name="captcha" id="captcha" placeholder="Entrez le code CAPTCHA" value="<?php echo isset($_POST['captcha']) ? htmlspecialchars($_POST['captcha']) : ''; ?>">
                        <button type="button" class="refresh-captcha" id="refresh-captcha">Rafraîchir CAPTCHA</button>
                        <span class="error-message" id="captcha-error"><?php echo $errors['captcha'] ?? ''; ?></span>
                    </div>
                </div>

                <button type="button" id="validate-dates">Valider les Dates</button>
                <button type="submit" id="submit-btn" class="hidden">Ajouter Réservation</button>
            </form>
        </div>

        <footer class="footer">
            <div class="container">
                <p>© <?= date("Y"); ?> Green.tn</p>
            </div>
        </footer>
    </main>

    <script>
        // Validation Functions
        function validateDateDebut() {
            const input = document.getElementById('date_debut');
            const error = document.getElementById('date_debut-error');
            const value = input.value;

            if (!value) {
                error.textContent = 'Tu dois choisir une date.';
                error.classList.add('active');
                return false;
            }
            error.classList.remove('active');
            return true;
        }

        function validateDateFin() {
            const input = document.getElementById('date_fin');
            const error = document.getElementById('date_fin-error');
            const value = input.value;
            const dateDebut = document.getElementById('date_debut').value;

            if (!value) {
                error.textContent = 'Veuillez choisir une date de fin.';
                error.classList.add('active');
                return false;
            }
            if (dateDebut && value <= dateDebut) {
                error.textContent = 'La date de fin doit être postérieure à la date de début.';
                error.classList.add('active');
                return false;
            }
            error.classList.remove('active');
            return true;
        }

        function validateIdVelo() {
            const input = document.getElementById('id_velo');
            const error = document.getElementById('id_velo-error');
            const value = input.value;

            if (!value) {
                error.textContent = 'Veuillez sélectionner un vélo.';
                error.classList.add('active');
                return false;
            }
            error.classList.remove('active');
            return true;
        }

        function validateGouvernorat() {
            const input = document.getElementById('gouvernorat');
            const error = document.getElementById('gouvernorat-error');
            const value = input.value;

            if (!value) {
                error.textContent = 'Veuillez sélectionner un gouvernorat.';
                error.classList.add('active');
                return false;
            }
            error.classList.remove('active');
            return true;
        }

        function validateTelephone() {
            const input = document.getElementById('telephone');
            const error = document.getElementById('telephone-error');
            const value = input.value;

            if (!value) {
                error.textContent = 'Veuillez entrer un numéro de téléphone.';
                error.classList.add('active');
                return false;
            }
            if (!/^\d{8}$/.test(value)) {
                error.textContent = 'Le numéro de téléphone doit contenir exactement 8 chiffres.';
                error.classList.add('active');
                return false;
            }
            error.classList.remove('active');
            return true;
        }

        function validateCaptcha() {
            const input = document.getElementById('captcha');
            const error = document.getElementById('captcha-error');
            const value = input.value;

            if (!value) {
                error.textContent = 'Veuillez entrer le code CAPTCHA.';
                error.classList.add('active');
                return false;
            }
            if (value.length !== 6) {
                error.textContent = 'Le code CAPTCHA doit contenir 6 caractères.';
                error.classList.add('active');
                return false;
            }
            error.classList.remove('active');
            return true;
        }

        // Validate All Fields
        function validateAllFields() {
            const isDateDebutValid = validateDateDebut();
            const isDateFinValid = validateDateFin();
            const isIdVeloValid = document.getElementById('extra-fields').classList.contains('hidden') || validateIdVelo();
            const isGouvernoratValid = document.getElementById('extra-fields').classList.contains('hidden') || validateGouvernorat();
            const isTelephoneValid = document.getElementById('extra-fields').classList.contains('hidden') || validateTelephone();
            const isCaptchaValid = document.getElementById('extra-fields').classList.contains('hidden') || validateCaptcha();

            return isDateDebutValid && isDateFinValid && isIdVeloValid && isGouvernoratValid && isTelephoneValid && isCaptchaValid;
        }

        // Event Listeners for Real-Time Validation
        document.getElementById('date_debut').addEventListener('input', validateDateDebut);
        document.getElementById('date_fin').addEventListener('input', validateDateFin);
        document.getElementById('id_velo').addEventListener('change', validateIdVelo);
        document.getElementById('gouvernorat').addEventListener('change', validateGouvernorat);
        document.getElementById('telephone').addEventListener('input', validateTelephone);
        document.getElementById('captcha').addEventListener('input', validateCaptcha);

        // Modify Validate Dates Button
        document.getElementById('validate-dates').addEventListener('click', function() {
            if (!validateAllFields()) {
                return;
            }

            const dateDebut = document.getElementById('date_debut').value;
            const dateFin = document.getElementById('date_fin').value;

            if (dateDebut && dateFin) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=check_dates&date_debut=${dateDebut}&date_fin=${dateFin}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        const errorMessage = document.createElement('p');
                        errorMessage.style.color = 'red';
                        errorMessage.style.textAlign = 'center';
                        errorMessage.style.marginBottom = '20px';
                        errorMessage.textContent = data.error;
                        document.querySelector('.container').insertBefore(errorMessage, document.querySelector('form'));
                    } else {
                        const veloSelect = document.getElementById('id_velo');
                        veloSelect.innerHTML = '<option value="" disabled selected>-- Sélectionnez un vélo --</option>';
                        data.forEach(velo => {
                            const option = document.createElement('option');
                            option.value = velo.id_velo;
                            option.textContent = velo.nom_velo;
                            veloSelect.appendChild(option);
                        });
                        document.getElementById('extra-fields').classList.remove('hidden');
                        document.getElementById('submit-btn').classList.remove('hidden');
                        this.classList.add('hidden');
                    }
                });
            }
        });

        // Refresh CAPTCHA
        document.getElementById('refresh-captcha').addEventListener('click', function() {
            const captchaImage = document.getElementById('captcha-image');
            captchaImage.style.display = 'block';
            captchaImage.src = 'captcha.php?' + new Date().getTime();
            document.getElementById('captcha').value = '';
            document.getElementById('captcha-error').classList.remove('active');
        });

        // Prevent Form Submission if Invalid
        document.getElementById('reservation-form').addEventListener('submit', function(event) {
            if (!validateAllFields()) {
                event.preventDefault();
            }
        });

        // Afficher les messages d'erreur côté serveur au chargement
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $key => $message): ?>
                    if (['date_debut', 'date_fin', 'id_velo', 'gouvernorat', 'telephone', 'captcha'].includes('<?php echo $key; ?>')) {
                        const errorElement = document.getElementById('<?php echo $key; ?>-error');
                        errorElement.textContent = '<?php echo $message; ?>';
                        errorElement.classList.add('active');
                        document.getElementById('extra-fields').classList.remove('hidden');
                        document.getElementById('submit-btn').classList.remove('hidden');
                        document.getElementById('validate-dates').classList.add('hidden');
                    }
                <?php endforeach; ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>