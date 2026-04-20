<?php

require_once dirname(__DIR__) . '/config.php';

function getPDO() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . $GLOBALS['dbServer'] .
               ";dbname=" . $GLOBALS['dbName'] .
               ";charset=utf8mb4";

        $pdo = new PDO(
            $dsn,
            $GLOBALS['dbUser'],
            $GLOBALS['dbPassword'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }

    return $pdo;
}
