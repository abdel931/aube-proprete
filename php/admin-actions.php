<?php
header('Content-Type: application/json');
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    die(json_encode(['succes' => false, 'message' => 'Non autorise.']));
}

$action = $_POST['action'] ?? '';

try {

    // Répondre à un client
    if ($action === 'repondre_client') {
        $client_id = intval($_POST['client_id'] ?? 0);
        $sujet     = trim(htmlspecialchars($_POST['sujet'] ?? ''));
        $contenu   = trim(htmlspecialchars($_POST['contenu'] ?? ''));

        if (!$client_id || empty($sujet) || empty($contenu)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }

        $stmt = $pdo->prepare("INSERT INTO messages (client_id, expediteur, sujet, contenu) VALUES (:id, 'admin', :sujet, :contenu)");
        $stmt->execute([':id' => $client_id, ':sujet' => $sujet, ':contenu' => $contenu]);
        echo json_encode(['succes' => true, 'message' => 'Message envoye au client.']);
    }

    // Changer statut contact
    elseif ($action === 'statut_contact') {
        $id     = intval($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        $valides = ['nouveau', 'lu', 'traite'];
        if (!$id || !in_array($statut, $valides)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees invalides.']));
        }
        $pdo->prepare("UPDATE contacts SET statut = :statut WHERE id = :id")->execute([':statut' => $statut, ':id' => $id]);
        echo json_encode(['succes' => true, 'message' => 'Statut mis a jour.']);
    }

    // Changer statut candidature
    elseif ($action === 'statut_candidature') {
        $id     = intval($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        $valides = ['nouvelle', 'en_etude', 'acceptee', 'refusee'];
        if (!$id || !in_array($statut, $valides)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees invalides.']));
        }
        $pdo->prepare("UPDATE candidatures SET statut = :statut WHERE id = :id")->execute([':statut' => $statut, ':id' => $id]);
        echo json_encode(['succes' => true, 'message' => 'Statut candidature mis a jour.']);
    }

    // Changer statut intervention
    elseif ($action === 'statut_intervention') {
        $id     = intval($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        $valides = ['planifiee', 'en_cours', 'terminee', 'annulee'];
        if (!$id || !in_array($statut, $valides)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees invalides.']));
        }
        $pdo->prepare("UPDATE interventions SET statut = :statut WHERE id = :id")->execute([':statut' => $statut, ':id' => $id]);
        echo json_encode(['succes' => true, 'message' => 'Intervention mise a jour.']);
    }

    // Planifier intervention pour un client
    elseif ($action === 'planifier_intervention') {
        $client_id = intval($_POST['client_id'] ?? 0);
        $type      = trim(htmlspecialchars($_POST['type_service'] ?? ''));
        $date      = $_POST['date_intervention'] ?? '';
        $heure     = $_POST['heure'] ?? '09:00';
        $prix      = floatval($_POST['prix'] ?? 0);

        if (!$client_id || empty($type) || empty($date)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }

        $stmt = $pdo->prepare("INSERT INTO interventions (client_id, type_service, date_intervention, heure, statut, prix) VALUES (:cid, :type, :date, :heure, 'planifiee', :prix)");
        $stmt->execute([':cid' => $client_id, ':type' => $type, ':date' => $date, ':heure' => $heure, ':prix' => $prix]);
        echo json_encode(['succes' => true, 'message' => 'Intervention planifiee.']);
    }

    // Créer facture
    elseif ($action === 'creer_facture') {
        $client_id      = intval($_POST['client_id'] ?? 0);
        $intervention_id = intval($_POST['intervention_id'] ?? 0) ?: null;
        $montant        = floatval($_POST['montant'] ?? 0);

        if (!$client_id || $montant <= 0) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }

        $stmt = $pdo->prepare("INSERT INTO factures (client_id, intervention_id, montant, statut) VALUES (:cid, :iid, :montant, 'en_attente')");
        $stmt->execute([':cid' => $client_id, ':iid' => $intervention_id, ':montant' => $montant]);
        echo json_encode(['succes' => true, 'message' => 'Facture creee.']);
    }

    else {
        echo json_encode(['succes' => false, 'message' => 'Action inconnue.']);
    }

} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}