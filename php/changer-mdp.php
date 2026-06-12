<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['client_id'])) {
    die(json_encode(['succes' => false, 'message' => 'Non connecte.']));
}

require_once 'connexion.php';
$client_id = (int)$_SESSION['client_id'];

$ancien = $_POST['ancien_mdp'] ?? '';
$nouveau = $_POST['nouveau_mdp'] ?? '';
$confirm = $_POST['confirmation'] ?? '';

if (strlen($nouveau) < 8) {
    die(json_encode(['succes' => false, 'message' => 'Nouveau mot de passe trop court (min 8 caracteres).']));
}
if ($nouveau !== $confirm) {
    die(json_encode(['succes' => false, 'message' => 'Les mots de passe ne correspondent pas.']));
}

try {
    $stmt = $pdo->prepare("SELECT mot_de_passe FROM clients WHERE id = :id");
    $stmt->execute([':id' => $client_id]);
    $client = $stmt->fetch();

    if (!$client || !password_verify($ancien, $client['mot_de_passe'])) {
        die(json_encode(['succes' => false, 'message' => 'Ancien mot de passe incorrect.']));
    }

    $hash = password_hash($nouveau, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE clients SET mot_de_passe = :mdp WHERE id = :id")
        ->execute([':mdp' => $hash, ':id' => $client_id]);

    echo json_encode(['succes' => true, 'message' => 'Mot de passe modifie avec succes !']);
} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}