<?php
/* ============================================
   TRAITEMENT DU FORMULAIRE DE CONTACT
   ============================================ */

// On autorise les requêtes depuis notre site
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// On inclut la connexion à la base de données
require_once 'connexion.php';

// Sécurité : on accepte uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['succes' => false, 'message' => 'Méthode non autorisée.']));
}

// On récupère et nettoie les données envoyées
$nom       = trim(htmlspecialchars($_POST['nom'] ?? ''));
$email     = trim($_POST['email'] ?? '');
$telephone = trim(htmlspecialchars($_POST['telephone'] ?? ''));
$prestation = trim(htmlspecialchars($_POST['prestation'] ?? ''));
$message   = trim(htmlspecialchars($_POST['message'] ?? ''));

// --- Validation côté serveur ---
// (En plus de la validation JavaScript, toujours valider côté serveur)

if (strlen($nom) < 2) {
    die(json_encode(['succes' => false, 'message' => 'Nom invalide.']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['succes' => false, 'message' => 'Email invalide.']));
}

if (strlen($telephone) < 10) {
    die(json_encode(['succes' => false, 'message' => 'Téléphone invalide.']));
}

if (empty($prestation)) {
    die(json_encode(['succes' => false, 'message' => 'Prestation manquante.']));
}

if (strlen($message) < 10) {
    die(json_encode(['succes' => false, 'message' => 'Message trop court.']));
}

// --- Insertion en base de données ---
try {

    $sql = "INSERT INTO contacts (nom, email, telephone, prestation, message)
            VALUES (:nom, :email, :telephone, :prestation, :message)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nom'        => $nom,
        ':email'      => $email,
        ':telephone'  => $telephone,
        ':prestation' => $prestation,
        ':message'    => $message,
    ]);

    echo json_encode([
        'succes'  => true,
        'message' => 'Votre message a bien été envoyé ! Nous vous répondons sous 24h.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'succes'  => false,
        'message' => 'Une erreur est survenue. Veuillez réessayer.'
    ]);
}