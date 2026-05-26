<?php
/* ============================================
   AUTHENTIFICATION CLIENT
   ============================================ */

header('Content-Type: application/json');
session_start();
require_once 'connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['succes' => false, 'message' => 'Méthode non autorisée.']));
}

$email         = trim($_POST['email'] ?? '');
$mot_de_passe  = $_POST['mot_de_passe'] ?? '';

// Validation basique
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['succes' => false, 'message' => 'Email invalide.']));
}

if (empty($mot_de_passe)) {
    die(json_encode(['succes' => false, 'message' => 'Mot de passe manquant.']));
}

try {

    // On cherche le client par email
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $client = $stmt->fetch();

    // Si client trouvé ET mot de passe correct
    if ($client && password_verify($mot_de_passe, $client['mot_de_passe'])) {

        // On crée la session
        $_SESSION['client_id']  = $client['id'];
        $_SESSION['client_nom'] = $client['nom'];
        $_SESSION['client_prenom'] = $client['prenom'];
        $_SESSION['client_email'] = $client['email'];

        echo json_encode([
            'succes'   => true,
            'message'  => 'Connexion réussie !',
            'redirect' => '/aube-proprete/espace-client/dashboard.html'
        ]);

    } else {
        echo json_encode([
            'succes'  => false,
            'message' => 'Email ou mot de passe incorrect.'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur serveur.']);
}