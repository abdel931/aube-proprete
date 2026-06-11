<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['client_id'])) {
    die(json_encode(['succes' => false, 'message' => 'Non connecte.']));
}

require_once 'connexion.php';

$client_id   = (int)$_SESSION['client_id'];
$facture_id  = intval($_POST['facture_id'] ?? 0);
$methode     = trim($_POST['methode'] ?? 'carte');

if (!$facture_id) {
    die(json_encode(['succes' => false, 'message' => 'Facture introuvable.']));
}

try {
    // Vérifier que la facture appartient bien à CE client
    $stmt = $pdo->prepare("SELECT * FROM factures WHERE id = :id AND client_id = :cid");
    $stmt->execute([':id' => $facture_id, ':cid' => $client_id]);
    $facture = $stmt->fetch();

    if (!$facture) {
        die(json_encode(['succes' => false, 'message' => 'Facture non trouvee.']));
    }

    if ($facture['statut'] === 'payee') {
        die(json_encode(['succes' => false, 'message' => 'Cette facture est deja payee.']));
    }

    // Marquer comme payée
    $pdo->prepare("UPDATE factures SET statut = 'payee' WHERE id = :id AND client_id = :cid")
        ->execute([':id' => $facture_id, ':cid' => $client_id]);

    // Envoyer un message de confirmation automatique
    $montant = number_format((float)$facture['montant'], 2);
    $pdo->prepare("INSERT INTO messages (client_id, expediteur, sujet, contenu) VALUES (:cid, 'admin', 'Paiement recu', 'Nous avons bien recu votre paiement de {$montant} EUR. Merci pour votre confiance. Aube Proprete Services.')")
        ->execute([':cid' => $client_id]);

    echo json_encode(['succes' => true, 'message' => 'Paiement de ' . $montant . ' EUR effectue avec succes !']);

} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}