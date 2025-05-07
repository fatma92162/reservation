<?php
session_start();

// Empêcher la mise en cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Débogage : Vérifier si GD est activé
if (!extension_loaded('gd')) {
    error_log("Erreur : L'extension GD n'est pas activée dans PHP.");
    header('Content-Type: text/plain');
    echo "Erreur : L'extension GD n'est pas activée dans PHP.";
    exit;
}

// Générer un code aléatoire de 6 caractères
$captcha_code = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
$_SESSION['captcha_code'] = $captcha_code;

// Débogage : Journaliser le code généré
error_log("CAPTCHA généré : " . $captcha_code);

// Créer une image CAPTCHA
$width = 200;
$height = 60;
$image = imagecreate($width, $height);
if (!$image) {
    error_log("Erreur : Impossible de créer l'image CAPTCHA.");
    header('Content-Type: text/plain');
    echo "Erreur : Impossible de créer l'image CAPTCHA.";
    exit;
}

// Définir les couleurs
$background_color = imagecolorallocate($image, 249, 245, 232); // #F9F5E8
$text_color = imagecolorallocate($image, 27, 94, 32); // #1b5e20
$noise_color = imagecolorallocate($image, 96, 186, 151); // #60BA97

// Remplir l'arrière-plan
imagefill($image, 0, 0, $background_color);

// Ajouter du bruit (points aléatoires)
for ($i = 0; $i < 200; $i++) {
    imagesetpixel(
        $image,
        rand(0, $width),
        rand(0, $height),
        $noise_color
    );
}

// Utiliser une police par défaut (sans TTF pour simplifier)
$font = 5;
$text_x = 10;
$text_y = 20;
imagestring($image, $font, $text_x, $text_y, $captcha_code, $text_color);

// Définir le type de contenu et afficher l'image
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>