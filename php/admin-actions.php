<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    die(json_encode(['succes' => false, 'message' => 'Non autorise.']));
}

require_once 'connexion.php';
$action = $_POST['action'] ?? '';

try {

    // Répondre à un client par client_id
    if ($action === 'repondre_client') {
        $client_id = intval($_POST['client_id'] ?? 0);
        $sujet     = trim(htmlspecialchars($_POST['sujet'] ?? ''));
        $contenu   = trim(htmlspecialchars($_POST['contenu'] ?? ''));

        if (!$client_id || empty($sujet) || empty($contenu)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }

        // Vérifier que le client existe
        $check = $pdo->prepare("SELECT id FROM clients WHERE id = :id");
        $check->execute([':id' => $client_id]);
        if (!$check->fetch()) {
            die(json_encode(['succes' => false, 'message' => 'Client introuvable.']));
        }

        $stmt = $pdo->prepare("INSERT INTO messages (client_id, expediteur, sujet, contenu) VALUES (:id, 'admin', :sujet, :contenu)");
        $stmt->execute([':id' => $client_id, ':sujet' => $sujet, ':contenu' => $contenu]);
        echo json_encode(['succes' => true, 'message' => 'Message envoye au client.']);
    }

    // Répondre via email du contact (formulaire contact)
    elseif ($action === 'repondre_contact') {
        $email   = trim($_POST['email'] ?? '');
        $sujet   = trim(htmlspecialchars($_POST['sujet'] ?? ''));
        $contenu = trim(htmlspecialchars($_POST['contenu'] ?? ''));

        if (empty($email) || empty($sujet) || empty($contenu)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }

        // Chercher si cet email correspond à un client inscrit
        $stmt = $pdo->prepare("SELECT id, prenom, nom FROM clients WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $client = $stmt->fetch();

        if ($client) {
            // Client inscrit → message dans espace client
            $ins = $pdo->prepare("INSERT INTO messages (client_id, expediteur, sujet, contenu) VALUES (:id, 'admin', :sujet, :contenu)");
            $ins->execute([':id' => $client['id'], ':sujet' => $sujet, ':contenu' => $contenu]);
            echo json_encode(['succes' => true, 'message' => 'Message envoye a ' . $client['prenom'] . ' ' . $client['nom'] . ' via espace client.']);
        } else {
            // Pas de compte → simulation email externe
            echo json_encode(['succes' => true, 'message' => 'Reponse envoyee par email a ' . $email . ' (simulation).']);
        }
    }

    // Changer statut contact
    elseif ($action === 'statut_contact') {
        $id     = intval($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        if (!$id || !in_array($statut, ['nouveau','lu','traite'])) {
            die(json_encode(['succes' => false, 'message' => 'Invalide.']));
        }
        $pdo->prepare("UPDATE contacts SET statut = :s WHERE id = :id")->execute([':s' => $statut, ':id' => $id]);
        echo json_encode(['succes' => true, 'message' => 'Statut mis a jour.']);
    }

    // Changer statut candidature
    elseif ($action === 'statut_candidature') {
        $id     = intval($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        if (!$id || !in_array($statut, ['nouvelle','en_etude','acceptee','refusee'])) {
            die(json_encode(['succes' => false, 'message' => 'Invalide.']));
        }
        $pdo->prepare("UPDATE candidatures SET statut = :s WHERE id = :id")->execute([':s' => $statut, ':id' => $id]);
        echo json_encode(['succes' => true, 'message' => 'Candidature mise a jour.']);
    }

    // Changer statut intervention
    elseif ($action === 'statut_intervention') {
        $id     = intval($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        if (!$id || !in_array($statut, ['planifiee','en_cours','terminee','annulee'])) {
            die(json_encode(['succes' => false, 'message' => 'Invalide.']));
        }
        $pdo->prepare("UPDATE interventions SET statut = :s WHERE id = :id")->execute([':s' => $statut, ':id' => $id]);

        // Si terminée → créer facture automatiquement si prix défini
        if ($statut === 'terminee') {
            $inter = $pdo->prepare("SELECT * FROM interventions WHERE id = :id");
            $inter->execute([':id' => $id]);
            $i = $inter->fetch();
            if ($i && $i['prix'] > 0) {
                $check = $pdo->prepare("SELECT id FROM factures WHERE intervention_id = :iid");
                $check->execute([':iid' => $id]);
                if (!$check->fetch()) {
                    $pdo->prepare("INSERT INTO factures (client_id, intervention_id, montant, statut) VALUES (:cid, :iid, :m, 'en_attente')")
                        ->execute([':cid' => $i['client_id'], ':iid' => $id, ':m' => $i['prix']]);
                }
            }
        }
        echo json_encode(['succes' => true, 'message' => 'Intervention mise a jour.']);
    }

    // Planifier intervention
    elseif ($action === 'planifier_intervention') {
        $client_id = intval($_POST['client_id'] ?? 0);
        $type      = trim(htmlspecialchars($_POST['type_service'] ?? ''));
        $date      = $_POST['date_intervention'] ?? '';
        $heure     = $_POST['heure'] ?? '09:00';
        $prix      = floatval($_POST['prix'] ?? 0);

        if (!$client_id || empty($type) || empty($date)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }

        $stmt = $pdo->prepare("INSERT INTO interventions (client_id, type_service, date_intervention, heure, statut, prix) VALUES (:c, :t, :d, :h, 'planifiee', :p)");
        $stmt->execute([':c' => $client_id, ':t' => $type, ':d' => $date, ':h' => $heure, ':p' => $prix]);

        // Notifier le client
        $dateF = date('d/m/Y', strtotime($date));
        $pdo->prepare("INSERT INTO messages (client_id, expediteur, sujet, contenu) VALUES (:cid, 'admin', 'Intervention planifiee', 'Une intervention a ete planifiee pour vous : {$type} le {$dateF} a {$heure}. Notre equipe sera a votre disposition.')")
            ->execute([':cid' => $client_id]);

        echo json_encode(['succes' => true, 'message' => 'Intervention planifiee et client notifie.']);
    }

    // Créer facture
    elseif ($action === 'creer_facture') {
        $client_id       = intval($_POST['client_id'] ?? 0);
        $intervention_id = intval($_POST['intervention_id'] ?? 0) ?: null;
        $montant         = floatval($_POST['montant'] ?? 0);

        if (!$client_id || $montant <= 0) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }

        $pdo->prepare("INSERT INTO factures (client_id, intervention_id, montant, statut) VALUES (:c, :i, :m, 'en_attente')")
            ->execute([':c' => $client_id, ':i' => $intervention_id, ':m' => $montant]);

        // Notifier le client
        $montantF = number_format($montant, 2);
        $pdo->prepare("INSERT INTO messages (client_id, expediteur, sujet, contenu) VALUES (:cid, 'admin', 'Nouvelle facture', 'Une nouvelle facture de {$montantF} EUR a ete emise. Vous pouvez la consulter et la regler depuis votre espace client.')")
            ->execute([':cid' => $client_id]);

        echo json_encode(['succes' => true, 'message' => 'Facture creee et client notifie.']);
    }

    else {
        echo json_encode(['succes' => false, 'message' => 'Action inconnue.']);
    }

} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}