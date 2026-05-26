<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
session_start();
require_once 'connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['succes' => false, 'message' => 'Méthode non autorisée.']));
}

$prenom       = trim(htmlspecialchars($_POST['prenom'] ?? ''));
$nom          = trim(htmlspecialchars($_POST['nom'] ?? ''));
$email        = trim($_POST['email'] ?? '');
$telephone    = trim(htmlspecialchars($_POST['telephone'] ?? ''));
$mot_de_passe = $_POST['mot_de_passe'] ?? '';
$confirmation = $_POST['confirmation'] ?? '';

if (strlen($prenom) < 2 || strlen($nom) < 2) {
    die(json_encode(['succes' => false, 'message' => 'Prénom et nom invalides.']));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['succes' => false, 'message' => 'Email invalide.']));
}
if (strlen($mot_de_passe) < 8) {
    die(json_encode(['succes' => false, 'message' => 'Mot de passe trop court (minimum 8 caractères).']));
}
if ($mot_de_passe !== $confirmation) {
    die(json_encode(['succes' => false, 'message' => 'Les mots de passe ne correspondent pas.']));
}

try {
    $check = $pdo->prepare("SELECT id FROM clients WHERE email = :email");
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        die(json_encode(['succes' => false, 'message' => 'Cet email est déjà utilisé.']));
    }

    $hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO clients (nom, prenom, email, mot_de_passe, telephone) 
                           VALUES (:nom, :prenom, :email, :mot_de_passe, :telephone)");
    $stmt->execute([
        ':nom'          => $nom,
        ':prenom'       => $prenom,
        ':email'        => $email,
        ':mot_de_passe' => $hash,
        ':telephone'    => $telephone
    ]);

    $client_id = $pdo->lastInsertId();
    $_SESSION['client_id']     = $client_id;
    $_SESSION['client_nom']    = $nom;
    $_SESSION['client_prenom'] = $prenom;
    $_SESSION['client_email']  = $email;

    echo json_encode([
        'succes'   => true,
        'message'  => 'Compte créé avec succès !',
        'redirect' => '/aube-proprete/espace-client/dashboard.html'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}