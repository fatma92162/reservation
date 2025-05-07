<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<link rel="stylesheet" href="assets/style.css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Accueil</title>
</head>
<body>
    <!-- En-tête -->
    <header class="header">
        <div class="container">
            <h1>Bienvenue sur notre plateforme</h1>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="main-container">
        <div class="container">
            <?php if (!isset($_SESSION['role'])): ?>
                <!-- Formulaire de connexion -->
                <section class="login-section">
                    <h2>Se connecter</h2>
                    <form action="login.php" method="POST" class="form-login">
                        <div class="input-group">
                            <label for="email">Email :</label>
                            <input type="email" id="email" name="email" placeholder="Entrez votre email" required>
                        </div>

                        <div class="input-group">
                            <label for="password">Mot de passe :</label>
                            <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </form>
                </section>
            <?php else: ?>
                <!-- Tableau de bord -->
                <section class="dashboard-links">
                    <h2>Bienvenue, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?> !</h2>
                    <div class="action-links">
                            <a href="dashboard.php" class="btn btn-admin">Accéder au front-Office</a>
                            <a href="ajouter_reservation.php" class="btn btn-secondary">ajouter une reservation</a>
                            <a href="consulter_reservations.php" class="btn btn-secondary">consulter les reservations</a>
                            <a href="logout.php" class="btn btn-logout">Se déconnecter</a>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <!-- Pied de page -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date("Y"); ?> Votre Entreprise. Tous droits réservés.</p>
        </div>
    </footer>
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="taskbar">
        <div class="taskbar-container">
            <a href="#" class="taskbar-item">
                <span>🏠</span> Home
            </a>
            <a href="#" class="taskbar-item">
                <span>📊</span> Stats
            </a>
            <a href="#" class="taskbar-item">
                <span>🔧</span> Settings
            </a>
            <a href="#" class="taskbar-item">
                <span>🚪</span> Logout
            </a>
        </div>
    </div>
   
</body>
</html>
