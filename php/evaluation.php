<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['client_id'])) {
    die(json_encode(['succes' => false, 'message' => 'Non connecte.']));
}

require_once 'connexion.php';
$client_id = (int)$_SESSION['client_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'noter') {
        $intervention_id = intval($_POST['intervention_id'] ?? 0);
        $note = intval($_POST['note'] ?? 0);
        $commentaire = trim(htmlspecialchars($_POST['commentaire'] ?? ''));

        if (!$intervention_id || $note < 1 || $note > 5) {
            die(json_encode(['succes' => false, 'message' => 'Donnees invalides.']));
        }

        // Vérifier que l'intervention appartient au client et est terminée
        $stmt = $pdo->prepare("SELECT * FROM interventions WHERE id = :id AND client_id = :cid AND statut = 'terminee'");
        $stmt->execute([':id' => $intervention_id, ':cid' => $client_id]);
        if (!$stmt->fetch()) {
            die(json_encode(['succes' => false, 'message' => 'Intervention non trouvee ou non terminee.']));
        }

        // Vérifier si colonne note existe, sinon ajouter
        $pdo->exec("ALTER TABLE interventions ADD COLUMN IF NOT EXISTS note_client INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE interventions ADD COLUMN IF NOT EXISTS commentaire_client TEXT DEFAULT NULL");

        $pdo->prepare("UPDATE interventions SET note_client = :note, commentaire_client = :com WHERE id = :id")
            ->execute([':note' => $note, ':com' => $commentaire, ':id' => $intervention_id]);

        echo json_encode(['succes' => true, 'message' => 'Merci pour votre evaluation !']);
    }
} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}