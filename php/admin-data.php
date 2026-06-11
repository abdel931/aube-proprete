<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Connexion admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = $_POST['login']    ?? '';
    $password = $_POST['password'] ?? '';
    if ($login === 'admin' && $password === 'admin2026') {
        $_SESSION['admin']    = true;
        $_SESSION['admin_ts'] = time();
        echo json_encode(['succes' => true, 'message' => 'Connecte', 'session_id' => session_id()]);
        exit;
    }
    die(json_encode(['succes' => false, 'message' => 'Identifiants incorrects.']));
}

// Vérification session admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    http_response_code(401);
    die(json_encode(['succes' => false, 'message' => 'Non autorise. Reconnectez-vous.', 'redirect' => true]));
}

require_once 'connexion.php';

try {
    $contacts      = $pdo->query("SELECT * FROM contacts ORDER BY date_envoi DESC")->fetchAll();
    $clients       = $pdo->query("SELECT id, nom, prenom, email, telephone, date_inscription FROM clients ORDER BY date_inscription DESC")->fetchAll();
    $interventions = $pdo->query("SELECT i.*, c.nom, c.prenom FROM interventions i JOIN clients c ON i.client_id = c.id ORDER BY i.date_intervention DESC")->fetchAll();
    $factures      = $pdo->query("SELECT f.*, c.nom, c.prenom FROM factures f JOIN clients c ON f.client_id = c.id ORDER BY f.date_facture DESC")->fetchAll();
    $messages      = $pdo->query("SELECT m.*, c.nom, c.prenom FROM messages m JOIN clients c ON m.client_id = c.id ORDER BY m.date_envoi DESC")->fetchAll();
    $candidatures  = $pdo->query("SELECT * FROM candidatures ORDER BY date_envoi DESC")->fetchAll();
    $non_lus       = $pdo->query("SELECT COUNT(*) as nb FROM messages WHERE expediteur='client' AND lu=0")->fetch()['nb'];

    echo json_encode([
        'succes'        => true,
        'contacts'      => $contacts,
        'clients'       => $clients,
        'interventions' => $interventions,
        'factures'      => $factures,
        'messages'      => $messages,
        'candidatures'  => $candidatures,
        'stats' => [
            'nb_contacts'       => count($contacts),
            'nb_clients'        => count($clients),
            'nb_interventions'  => count($interventions),
            'nb_factures'       => count($factures),
            'nb_messages'       => count($messages),
            'nb_candidatures'   => count($candidatures),
            'non_lus'           => $non_lus
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => $e->getMessage()]);
}