<?php
header('Content-Type: application/json');

// Détruire l'ancienne session proprement
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();

// Nouvelle session propre
session_start();
session_regenerate_id(true);

require_once 'connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['succes' => false, 'message' => 'Methode non autorisee.']));
}

$email        = trim($_POST['email'] ?? '');
$mot_de_passe = $_POST['mot_de_passe'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['succes' => false, 'message' => 'Email invalide.']));
}

try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $client = $stmt->fetch();

    if ($client && password_verify($mot_de_passe, $client['mot_de_passe'])) {
        $_SESSION['client_id']     = (int)$client['id'];
        $_SESSION['client_nom']    = $client['nom'];
        $_SESSION['client_prenom'] = $client['prenom'];
        $_SESSION['client_email']  = $client['email'];

        echo json_encode([
            'succes'   => true,
            'message'  => 'Connexion reussie !',
            'redirect' => '/aube-proprete/espace-client/dashboard.html'
        ]);
    } else {
        echo json_encode(['succes' => false, 'message' => 'Email ou mot de passe incorrect.']);
    }
} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur serveur.']);
}