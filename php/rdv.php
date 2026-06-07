<?php
header('Content-Type: application/json');
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['client_id'])) {
    die(json_encode(['succes' => false, 'message' => 'Non connecté.']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['succes' => false, 'message' => 'Méthode non autorisée.']));
}

$client_id       = $_SESSION['client_id'];
$type_service    = trim(htmlspecialchars($_POST['type_service'] ?? ''));
$date_intervention = $_POST['date_intervention'] ?? '';
$heure           = $_POST['heure'] ?? '09:00';
$note            = trim(htmlspecialchars($_POST['note'] ?? ''));

if (empty($type_service) || empty($date_intervention)) {
    die(json_encode(['succes' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.']));
}

// Vérifier que la date est dans le futur
if (strtotime($date_intervention) < strtotime('today')) {
    die(json_encode(['succes' => false, 'message' => 'La date doit être dans le futur.']));
}

try {
    $stmt = $pdo->prepare("INSERT INTO interventions (client_id, type_service, date_intervention, heure, statut, note)
                           VALUES (:client_id, :type_service, :date_intervention, :heure, 'planifiee', :note)");
    $stmt->execute([
        ':client_id'        => $client_id,
        ':type_service'     => $type_service,
        ':date_intervention'=> $date_intervention,
        ':heure'            => $heure,
        ':note'             => $note
    ]);

    echo json_encode(['succes' => true, 'message' => 'Votre demande a été enregistrée ! Nous vous confirmons le RDV sous 24h.']);
} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}