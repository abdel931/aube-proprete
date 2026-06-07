<?php
header('Content-Type: application/json');
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['client_id'])) {
    die(json_encode(['succes' => false, 'message' => 'Non connecté.']));
}

$client_id = $_SESSION['client_id'];

try {
    // Infos du client
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
    $stmt->execute([':id' => $client_id]);
    $client = $stmt->fetch();

    // Interventions
    $stmt2 = $pdo->prepare("SELECT * FROM interventions WHERE client_id = :id ORDER BY date_intervention DESC");
    $stmt2->execute([':id' => $client_id]);
    $interventions = $stmt2->fetchAll();

    // Factures
    $stmt3 = $pdo->prepare("SELECT * FROM factures WHERE client_id = :id ORDER BY date_facture DESC");
    $stmt3->execute([':id' => $client_id]);
    $factures = $stmt3->fetchAll();

    // Stats
    $total = count($interventions);
    $terminees = count(array_filter($interventions, fn($i) => $i['statut'] === 'terminee'));
    $total_factures = array_sum(array_column($factures, 'montant'));
    $prochaine = array_filter($interventions, fn($i) => $i['statut'] === 'planifiee');
    $prochaine = array_values($prochaine);

    echo json_encode([
        'succes' => true,
        'client' => [
            'nom'    => $client['nom'],
            'prenom' => $client['prenom'],
            'email'  => $client['email'],
            'telephone' => $client['telephone'] ?? ''
        ],
        'stats' => [
            'total_interventions' => $total,
            'terminees'           => $terminees,
            'total_factures'      => number_format($total_factures, 2),
            'prochaine'           => $prochaine[0] ?? null
        ],
        'interventions' => $interventions,
        'factures'      => $factures
    ]);

} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => $e->getMessage()]);
}