<?php
require('libs/fpdf.php');  // Assurez-vous que FPDF est bien inclus dans ce dossier

// Vérification de la session et du rôle utilisateur
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: index.php');
    exit();
}

// Connexion à la base de données
try {
    $pdo = new PDO('mysql:host=localhost;dbname=velo_reservation', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les réservations de l'utilisateur
    $stmt = $pdo->prepare('SELECT r.*, u.nom AS nom_utilisateur FROM reservation r INNER JOIN utilisateur u ON r.id_client = u.id_utilisateur WHERE r.id_client = :id_client');
    $stmt->bindParam(':id_client', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// Création de l'objet PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);

// Titre du PDF
$pdf->Cell(0, 10, 'Liste des Reservations', 0, 1, 'C');
$pdf->Ln(10); // Ligne vide

// Entêtes des colonnes
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(20, 10, 'ID Reservation', 1);
$pdf->Cell(20, 10, 'ID Velo', 1);
$pdf->Cell(50, 10, 'Nom Utilisateur', 1);
$pdf->Cell(30, 10, 'Date Debut', 1);
$pdf->Cell(30, 10, 'Date Fin', 1);
$pdf->Cell(30, 10, 'Gouvernorat', 1);
$pdf->Cell(40, 10, 'Telephone', 1);
$pdf->Ln();

// Corps du tableau
$pdf->SetFont('Arial', '', 12);
foreach ($reservations as $reservation) {
    $pdf->Cell(20, 10, $reservation['id_reservation'], 1);
    $pdf->Cell(20, 10, $reservation['id_velo'], 1);
    $pdf->Cell(50, 10, $reservation['nom_utilisateur'], 1);
    $pdf->Cell(30, 10, $reservation['date_debut'], 1);
    $pdf->Cell(30, 10, $reservation['date_fin'], 1);
    $pdf->Cell(30, 10, $reservation['gouvernorat'], 1);
    $pdf->Cell(40, 10, $reservation['telephone'], 1);
    $pdf->Ln();
}

// Sortie du PDF (téléchargement)
$pdf->Output('D', 'reservations_' . $_SESSION['user_id'] . '.pdf');
exit();
?>
