<?php

namespace Analyzer;

use Analyzer\Url;

class UrlRepository
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function getEntities(): array
    {
        $urls = [];
        $sql = "SELECT * FROM urls";
        $stmt = $this->connection->query($sql);

        while ($row = $stmt->fetch()) {
            $url = Url::fromArray($row);
            $url->setId($row['id']);
            $urls[] = $url;
        }

        return $urls;
    }

    public function save(Url $url)
    {
        $message = '';

        if ($url->exists()) {
            $this->update($url);
            $message = 'Страница уже существует';
        } else {
            $this->create($url);
            $message = 'Страница успешно добавлена';
        }

        return $message;
    }

    public function find(int $id)
    {
        $sql = "SELECT * FROM urls WHERE id = ? ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id]);
        if ($row = $stmt->fetch()) {
            $url = Url::fromArray($row);
            $url->setId($row['id']);
            return $url;
        }
    }

    private function update(Url $url): void
    {
        $sql = "UPDATE urls SET name = :name, created_at = :created_at WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        $id = $url->getId();
        $name = $url->getName();
        $created_at = $url->getDate();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    private function create(Url $url): void
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->connection->prepare($sql);
        $name = $url->getName();
        $date = $url->getDate();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':created_at', $date);
        $stmt->execute();
        $id = (int) $this->connection->lastInsertId();
        $url->setId($id);
    }
}
