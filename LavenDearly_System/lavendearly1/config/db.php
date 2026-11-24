<?php
// Database settings (XAMPP defaults). Change if your MySQL setup is different.
define('DB_HOST', 'localhost');
define('DB_NAME', 'lavendearly');
define('DB_USER', 'root');
define('DB_PASS', '');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('<h2 style="font-family:sans-serif">LavenDearly could not connect to the database.</h2>'
                . '<p style="font-family:sans-serif">Start MySQL, import <code>database/lavendearly.sql</code>, '
                . 'and check <code>config/db.php</code>.</p>');
        }
    }
    return $pdo;
}
