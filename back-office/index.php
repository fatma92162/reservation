<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<link rel="stylesheet" href="style.css">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Accueil</title>
</head>
<body>
    <!-- Barre des tâches verticale -->
<div class="sidebar">
    <div class="sidebar-container">
        <a href="index.php" class="sidebar-item">
            <span>🏠</span> Accueil
        </a>
        <a href="dashboard.php" class="sidebar-item">
            <span>📊</span> Tableau de Bord
        </a>
        <a href="ajouter_velo.php" class="sidebar-item">
            <span>🚲</span> Ajouter un Vélo
        </a>
        <a href="consulter_velos.php" class="sidebar-item">
            <span>🔍</span> Consulter les Vélos
        </a>
        <a href="logout.php" class="sidebar-item">
            <span>🚪</span> Déconnexion
        </a>
    </div>
</div>

    <header class="header">
        <div class="container">
            <h1 class="welcome-text">Bienvenue sur notre plateforme</h1>
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
                    <h2>Bienvenue, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?> !</h2>
                    <div class="action-links">
                        <a href="/projet/back-office/dashboard.php" class="btn btn-admin">Accéder au Back-Office</a>
                        <a href="/projet/back-office/ajouter_velo.php" class="btn btn-secondary">Ajouter un Vélo</a>
                        <a href="/projet/back-office/dashboard.php" class="btn btn-secondary">Consulter les Vélos</a>
                        <a href="logout.php" class="btn btn-logout">Se déconnecter</a>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p class="footer-text">© 2025 Votre Entreprise. Tous droits réservés.</p>
        </div>
    </footer>

</body>
</html>