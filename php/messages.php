<?php
header('Content-Type: application/json');
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['client_id'])) {
    die(json_encode(['succes' => false, 'message' => 'Non connecte.']));
}

$client_id = $_SESSION['client_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? 'liste';

try {

    // ACTION : Récupérer tous les messages du client
    if ($action === 'liste') {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE client_id = :id ORDER BY date_envoi DESC");
        $stmt->execute([':id' => $client_id]);
        $messages = $stmt->fetchAll();

        // Marquer les messages admin comme lus
        $pdo->prepare("UPDATE messages SET lu = 1 WHERE client_id = :id AND expediteur = 'admin'")->execute([':id' => $client_id]);

        echo json_encode(['succes' => true, 'messages' => $messages]);
    }

    // ACTION : Envoyer un message (client → admin)
    elseif ($action === 'envoyer') {
        $sujet   = trim(htmlspecialchars($_POST['sujet'] ?? ''));
        $contenu = trim(htmlspecialchars($_POST['contenu'] ?? ''));

        if (empty($sujet) || empty($contenu)) {
            die(json_encode(['succes' => false, 'message' => 'Sujet et message obligatoires.']));
        }

        $stmt = $pdo->prepare("INSERT INTO messages (client_id, expediteur, sujet, contenu) VALUES (:id, 'client', :sujet, :contenu)");
        $stmt->execute([':id' => $client_id, ':sujet' => $sujet, ':contenu' => $contenu]);

        echo json_encode(['succes' => true, 'message' => 'Message envoye ! Reponse sous 24h.']);
    }

    // ACTION : Compter messages non lus
    elseif ($action === 'non_lus') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as nb FROM messages WHERE client_id = :id AND expediteur = 'admin' AND lu = 0");
        $stmt->execute([':id' => $client_id]);
        $result = $stmt->fetch();
        echo json_encode(['succes' => true, 'non_lus' => $result['nb']]);
    }

} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}