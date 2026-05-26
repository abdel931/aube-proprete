<?php
/* ============================================
   CONNEXION À LA BASE DE DONNÉES
   Auteur : Abdel-Rahmane — Stage IPSSI Paris
   ============================================ */

// Informations de connexion
define('DB_HOST', 'localhost');
define('DB_NAME', 'aube_proprete');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Connexion avec PDO (méthode sécurisée moderne)
try {

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

} catch (PDOException $e) {
    // En production on n'affiche JAMAIS l'erreur à l'utilisateur
    http_response_code(500);
    die(json_encode(['succes' => false, 'message' => 'Erreur de connexion à la base de données.']));
}