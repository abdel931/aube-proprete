<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

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

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    http_response_code(401);
    die(json_encode(['succes' => false, 'message' => 'Session expiree. Reconnectez-vous.']));
}

require_once 'connexion.php';
$action = $_POST['action'] ?? '';

try {

    if ($action === 'repondre_client') {
        $client_id = intval($_POST['client_id'] ?? 0);
        $sujet     = trim(htmlspecialchars($_POST['sujet'] ?? ''));
        $contenu   = trim(htmlspecialchars($_POST['contenu'] ?? ''));
        if (!$client_id || empty($sujet) || empty($contenu)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }
        $pdo->prepare("INSERT INTO messages (client_id, expediteur, sujet, contenu) VALUES (:id,'admin',:s,:c)")
            ->execute([':id' => $client_id, ':s' => $sujet, ':c' => $contenu]);
        echo json_encode(['succes' => true, 'message' => 'Message envoye.']);
    }

    elseif ($action === 'repondre_contact') {
        $email   = trim($_POST['email'] ?? '');
        $sujet   = trim(htmlspecialchars($_POST['sujet'] ?? ''));
        $contenu = trim(htmlspecialchars($_POST['contenu'] ?? ''));
        if (empty($email) || empty($sujet) || empty($contenu)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }
        $stmt = $pdo->prepare("SELECT id, prenom, nom FROM clients WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $client = $stmt->fetch();
        if ($client) {
            $pdo->prepare("INSERT INTO messages (client_id, expediteur, sujet, contenu) VALUES (:id,'admin',:s,:c)")
                ->execute([':id' => $client['id'], ':s' => $sujet, ':c' => $contenu]);
            echo json_encode(['succes' => true, 'message' => 'Message envoye a '.$client['prenom'].' '.$client['nom'].'.']);
        } else {
            echo json_encode(['succes' => true, 'message' => 'Email externe — reponse simulee vers '.$email.'.']);
        }
    }

    elseif ($action === 'statut_contact') {
        $id     = intval($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        if (!$id || !in_array($statut, ['nouveau','lu','traite'])) {
            die(json_encode(['succes' => false, 'message' => 'Invalide.']));
        }
        $pdo->prepare("UPDATE contacts SET statut=:s WHERE id=:id")->execute([':s'=>$statut,':id'=>$id]);
        echo json_encode(['succes' => true, 'message' => 'Statut mis a jour.']);
    }

    elseif ($action === 'statut_candidature') {
        $id     = intval($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        if (!$id || !in_array($statut, ['nouvelle','en_etude','acceptee','refusee'])) {
            die(json_encode(['succes' => false, 'message' => 'Invalide.']));
        }
        $pdo->prepare("UPDATE candidatures SET statut=:s WHERE id=:id")->execute([':s'=>$statut,':id'=>$id]);
        echo json_encode(['succes' => true, 'message' => 'Candidature mise a jour.']);
    }

    elseif ($action === 'statut_intervention') {
        $id     = intval($_POST['id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        if (!$id || !in_array($statut, ['planifiee','en_cours','terminee','annulee'])) {
            die(json_encode(['succes' => false, 'message' => 'Invalide.']));
        }
        $pdo->prepare("UPDATE interventions SET statut=:s WHERE id=:id")->execute([':s'=>$statut,':id'=>$id]);
        if ($statut === 'terminee') {
            $i = $pdo->prepare("SELECT * FROM interventions WHERE id=:id");
            $i->execute([':id'=>$id]);
            $inter = $i->fetch();
            if ($inter && $inter['prix'] > 0) {
                $check = $pdo->prepare("SELECT id FROM factures WHERE intervention_id=:iid");
                $check->execute([':iid'=>$id]);
                if (!$check->fetch()) {
                    $pdo->prepare("INSERT INTO factures (client_id,intervention_id,montant,statut) VALUES (:c,:i,:m,'en_attente')")
                        ->execute([':c'=>$inter['client_id'],':i'=>$id,':m'=>$inter['prix']]);
                    $montantF = number_format($inter['prix'],2);
                    $pdo->prepare("INSERT INTO messages (client_id,expediteur,sujet,contenu) VALUES (:cid,'admin','Intervention terminee','Votre intervention est terminee. Une facture de ".$montantF." EUR a ete generee dans votre espace client.')")
                        ->execute([':cid'=>$inter['client_id']]);
                }
            }
        }
        echo json_encode(['succes' => true, 'message' => 'Intervention mise a jour.']);
    }

    elseif ($action === 'planifier_intervention') {
        $client_id = intval($_POST['client_id'] ?? 0);
        $type      = trim(htmlspecialchars($_POST['type_service'] ?? ''));
        $date      = $_POST['date_intervention'] ?? '';
        $heure     = $_POST['heure'] ?? '09:00';
        $prix      = floatval($_POST['prix'] ?? 0);
        if (!$client_id || empty($type) || empty($date)) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }
        $pdo->prepare("INSERT INTO interventions (client_id,type_service,date_intervention,heure,statut,prix) VALUES (:c,:t,:d,:h,'planifiee',:p)")
            ->execute([':c'=>$client_id,':t'=>$type,':d'=>$date,':h'=>$heure,':p'=>$prix]);
        $dateF = date('d/m/Y', strtotime($date));
        $pdo->prepare("INSERT INTO messages (client_id,expediteur,sujet,contenu) VALUES (:cid,'admin','Intervention planifiee','Bonne nouvelle ! Une intervention a ete planifiee : ".$type." le ".$dateF." a ".$heure.". Notre equipe sera a votre disposition.')")
            ->execute([':cid'=>$client_id]);
        echo json_encode(['succes' => true, 'message' => 'Intervention planifiee et client notifie.']);
    }

    elseif ($action === 'creer_facture') {
        $client_id = intval($_POST['client_id'] ?? 0);
        $montant   = floatval($_POST['montant'] ?? 0);
        if (!$client_id || $montant <= 0) {
            die(json_encode(['succes' => false, 'message' => 'Donnees manquantes.']));
        }
        $pdo->prepare("INSERT INTO factures (client_id,montant,statut) VALUES (:c,:m,'en_attente')")
            ->execute([':c'=>$client_id,':m'=>$montant]);
        $montantF = number_format($montant,2);
        $pdo->prepare("INSERT INTO messages (client_id,expediteur,sujet,contenu) VALUES (:cid,'admin','Nouvelle facture','Une facture de ".$montantF." EUR a ete emise. Consultez votre espace client pour la regler.')")
            ->execute([':cid'=>$client_id]);
        echo json_encode(['succes' => true, 'message' => 'Facture creee et client notifie.']);
    }

    else {
        echo json_encode(['succes' => false, 'message' => 'Action inconnue: '.$action]);
    }

} catch (PDOException $e) {
    echo json_encode(['succes' => false, 'message' => 'Erreur BDD: '.$e->getMessage()]);
}