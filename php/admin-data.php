<?php
header('Content-Type: application/json');
session_start();

// Sécurité admin simple
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    // Connexion admin basique
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
        ($_POST['login'] ?? '') === 'admin' && 
        ($_POST['password'] ?? '') === 'admin2026') {
        $_SESSION['admin'] = true;
        echo json_encode(['succes' => true, 'message' => 'Connecté']);
        exit;
    }
    die(json_encode(['succes' => false, 'message' => 'Non autorisé.']));
}

require_once 'connexion.php';

try {
    $contacts = $pdo->query("SELECT * FROM contacts ORDER BY date_envoi DESC")->fetchAll();
    $clients = $pdo->query("SELECT id, nom, prenom, email, telephone, date_inscription FROM clients ORDER BY date_inscription DESC")->fetchAll();
    $interventions = $pdo->query("SELECT i.*, c.nom, c.prenom FROM interventions i JOIN clients c ON i.client_id = c.id ORDER BY i.date_intervention DESC")->fetchAll();
    $factures = $pdo->query("SELECT f.*, c.nom, c.prenom FROM factures f JOIN clients c ON f.client_id = c.id ORDER BY f.date_facture DESC")->fetchAll();

    echo json_encode([
        'succes' => true,
        'contacts' => $contacts,
        'clients' => $clients,
        'interventions' => $interventions,
        'factures' => $factures,
        'stats' => [
            'nb_contacts' => count($contacts),
            'nb_clients' => count($clients),
            'nb_interventions' => count($interventions),
            'nb_factures' => count($factures)
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => $e->getMessage()]);
}
