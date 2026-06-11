<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['client_id']) || empty($_SESSION['client_id'])) {
    http_response_code(401);
    die(json_encode(['succes' => false, 'message' => 'Non connecte.', 'redirect' => '/aube-proprete/espace-client/login.html']));
}

require_once 'connexion.php';

$client_id = (int)$_SESSION['client_id'];

try {
    // Client connecté
    $stmt = $pdo->prepare("SELECT id, nom, prenom, email, telephone, adresse FROM clients WHERE id = :id");
    $stmt->execute([':id' => $client_id]);
    $client = $stmt->fetch();

    if (!$client) {
        session_destroy();
        die(json_encode(['succes' => false, 'message' => 'Client introuvable.']));
    }

    // Interventions de CE client uniquement
    $stmt2 = $pdo->prepare("SELECT * FROM interventions WHERE client_id = :id ORDER BY date_intervention DESC");
    $stmt2->execute([':id' => $client_id]);
    $interventions = $stmt2->fetchAll();

    // Factures de CE client uniquement
    $stmt3 = $pdo->prepare("SELECT * FROM factures WHERE client_id = :id ORDER BY date_facture DESC");
    $stmt3->execute([':id' => $client_id]);
    $factures = $stmt3->fetchAll();

    // Messages de CE client uniquement
    $stmt4 = $pdo->prepare("SELECT * FROM messages WHERE client_id = :id ORDER BY date_envoi DESC");
    $stmt4->execute([':id' => $client_id]);
    $messages = $stmt4->fetchAll();

    // Stats
    $total        = count($interventions);
    $terminees    = count(array_filter($interventions, fn($i) => $i['statut'] === 'terminee'));
    $total_fac    = array_sum(array_column($factures, 'montant'));
    $prochaines   = array_values(array_filter($interventions, fn($i) => $i['statut'] === 'planifiee'));
    $non_lus      = count(array_filter($messages, fn($m) => $m['expediteur'] === 'admin' && !$m['lu']));

    echo json_encode([
        'succes' => true,
        'client' => [
            'id'        => $client['id'],
            'nom'       => $client['nom'],
            'prenom'    => $client['prenom'],
            'email'     => $client['email'],
            'telephone' => $client['telephone'] ?? '',
            'adresse'   => $client['adresse'] ?? ''
        ],
        'stats' => [
            'total_interventions' => $total,
            'terminees'           => $terminees,
            'total_factures'      => number_format((float)$total_fac, 2),
            'prochaine'           => $prochaines[0] ?? null,
            'non_lus'             => $non_lus
        ],
        'interventions' => $interventions,
        'factures'      => $factures,
        'messages'      => $messages
    ]);

} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()]);
}