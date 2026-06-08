<?php
header('Content-Type: application/json');
require_once 'connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['succes' => false, 'message' => 'Methode non autorisee.']));
}

$nom        = trim(htmlspecialchars($_POST['nom'] ?? ''));
$prenom     = trim(htmlspecialchars($_POST['prenom'] ?? ''));
$email      = trim($_POST['email'] ?? '');
$telephone  = trim(htmlspecialchars($_POST['telephone'] ?? ''));
$poste      = trim(htmlspecialchars($_POST['poste'] ?? ''));
$experience = trim(htmlspecialchars($_POST['experience'] ?? ''));
$lettre     = trim(htmlspecialchars($_POST['lettre'] ?? ''));

if (strlen($nom) < 2 || strlen($prenom) < 2) {
    die(json_encode(['succes' => false, 'message' => 'Nom et prenom invalides.']));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['succes' => false, 'message' => 'Email invalide.']));
}
if (empty($poste)) {
    die(json_encode(['succes' => false, 'message' => 'Veuillez choisir un poste.']));
}

try {
    $stmt = $pdo->prepare("INSERT INTO candidatures (nom, prenom, email, telephone, poste, experience, lettre) VALUES (:nom, :prenom, :email, :telephone, :poste, :experience, :lettre)");
    $stmt->execute([
        ':nom'        => $nom,
        ':prenom'     => $prenom,
        ':email'      => $email,
        ':telephone'  => $telephone,
        ':poste'      => $poste,
        ':experience' => $experience,
        ':lettre'     => $lettre
    ]);
    echo json_encode(['succes' => true, 'message' => 'Candidature envoyee ! Nous vous recontacterons sous 72h.']);
} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}