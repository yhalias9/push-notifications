<?php

namespace App\Database;

use PDO;
use PDOException;

class DB
{
    public PDO $pdo;

    public function __construct()
    {
        try {
            $host = config('DB_HOST');
            $port = config('DB_PORT');
            $dbName = config('DB_NAME');
            $user = config('DB_USER_NAME');
            $pass = config('DB_PASSWORD');

            $this->pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $user, $pass);
            // $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            response(false, [
                'error' => $e->getMessage(),
            ]);
            throw new $e;
        }
    }

    public static function connect(): self
    {
        return new self();
    }

    public function insert(string $table, array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_map(fn($value) => ":$value", array_keys($data)));

        $statement = $this->pdo->prepare("INSERT INTO $table ($columns) VALUES ($values)");

        return $statement->execute($data);
    }

    public function update(string $table, int $id, array $data): bool
    {
        $columns = implode(', ', array_map(fn($value) => "$value = :$value", array_keys($data)));

        $statement = $this->pdo->prepare("UPDATE $table SET $columns WHERE id = :id");
        $data['id'] = $id;

        return $statement->execute($data);
    }

    public function getAll(string $table)
    {
        $statement = $this->pdo->prepare("SELECT * FROM $table");
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(string $table, int $id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM $table WHERE id = :id");
        $statement->execute(['id' => $id]);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function getByIds(string $table, array $ids, $column = 'id')
    {
        $placeholders = str_repeat('?, ', count($ids) - 1).'?';
        $statement = $this->pdo->prepare("SELECT * FROM $table WHERE $column IN ($placeholders)");
        $statement->execute($ids);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
