<?php

require_once dirname(__DIR__) . '/config.php';

function createPDOConnection(?string $databaseName = null): PDO
{
    $resolvedDatabaseName = $databaseName;

    if ($resolvedDatabaseName === null || trim($resolvedDatabaseName) === '') {
        $resolvedDatabaseName = isset($GLOBALS['dbName']) ? trim((string)$GLOBALS['dbName']) : '';
    }

    if ($resolvedDatabaseName === '') {
        throw new InvalidArgumentException('Aucun nom de base de données n\'est configuré.');
    }

    $dsn = 'mysql:host=' . $GLOBALS['dbServer']
        . ';dbname=' . $resolvedDatabaseName
        . ';charset=utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
        $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = true;
    }

    return new PDO(
        $dsn,
        $GLOBALS['dbUser'],
        $GLOBALS['dbPassword'],
        $options
    );
}

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = createPDOConnection();
    }

    return $pdo;
}
