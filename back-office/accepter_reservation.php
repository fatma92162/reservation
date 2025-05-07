<?php
session_start();

// Vérifier si l'utilisateur a le rôle 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Vérifier si l'ID de la réservation est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: voir_reservations.php');
    exit();
}

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=velo_reservation', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si la réservation existe et est en attente
    $query = 'SELECT id_velo, statut FROM reservation WHERE id_reservation = :id_reservation';
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id_reservation', $_GET['id'], PDO::PARAM_INT);
    $stmt->execute();
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation || $reservation['statut'] !== 'en_attente') {
        $_SESSION['error'] = 'Réservation introuvable ou déjà traitée.';
        header('Location: voir_reservations.php');
        exit();
    }

    // Mettre à jour le statut de la réservation
    $updateQuery = 'UPDATE reservation SET statut = "acceptee" WHERE id_reservation = :id_reservation';
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->bindParam(':id_reservation', $_GET['id'], PDO::PARAM_INT);

    // Décrémenter la disponibilité du vélo
    $updateVeloQuery = 'UPDATE velos SET disponibilite = disponibilite - 1 WHERE id_velo = :id_velo';
    $updateVeloStmt = $pdo->prepare($updateVeloQuery);
    $updateVeloStmt->bindParam(':id_velo', $reservation['id_velo'], PDO::PARAM_INT);

    // Exécuter les mises à jour
    $pdo->beginTransaction();
    $updateStmt->execute();
    $updateVeloStmt->execute();
    $pdo->commit();

    $_SESSION['success'] = 'Réservation acceptée avec succès.';
    header('Location: voir_reservations.php');
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
    header('Location: voir_reservations.php');
    exit();
}
?>