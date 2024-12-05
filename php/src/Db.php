<?php

/**
 *
 */
class Db
{
    /**
     * Пока просто метод для создания PDO
     */
    public function getPDO(): PDO
    {
        $env = getenv();

        $host   = $env['POSTGRES_HOST']     ?? 'localhost';
        $port   = $env['POSTGRES_PORT']     ?? false;
        $dbname = $env['POSTGRES_DB']       ?? 'bjn_test';
        $user   = $env['POSTGRES_USER']     ?? 'bjn_test';
        $pass   = $env['POSTGRES_PASSWORD'] ?? '123';

        if ($port !== false) {
            $port = "port={$port};";
        }

        $pdo = new PDO(
            "pgsql:host={$host};{$port};dbname={$dbname}",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true,
            ]
        );

        // Может быть незаконченная транзакция от предыдущего запроса к PHP
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return $pdo;
    }

    /**
     * Экранирует название поля
     */
    public function quoteColumn(string $key): false|string
    {
        $key = trim($key);
        if (strlen($key) < 1) {
            return false;
        }
        $key = str_replace('"', '', $key);
        return '"' . $key . '"';
    }
}
