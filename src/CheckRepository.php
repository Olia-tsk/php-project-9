<?php

namespace Analyzer;

use Carbon\Carbon;

class CheckRepository
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function addCheck(int $url_id, int $status_code, ?string $h1, ?string $title, ?string $description): void
    {
        $sql = "INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at) 
        VALUES (:url_id, :status_code, :h1, :title, :description, :created_at)";
        $stmt = $this->connection->prepare($sql);
        $created_at = Carbon::now();
        $stmt->bindParam(':url_id', $url_id);
        $stmt->bindParam(':status_code', $status_code);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->execute();
    }

    public function getChecks(int $url_id): array
    {
        $checkData = [];
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$url_id]);

        while ($row = $stmt->fetch()) {
            $check = new Check();
            $check->setId($row['id']);
            $check->setUrlId($row['url_id']);
            $check->setStatusCode($row['status_code']);
            $check->setH1($row['h1']);
            $check->setTitle($row['title']);
            $check->setDescription($row['description']);
            $check->setCheckDate($row['created_at']);
            $checkData[] = $check;
        }

        return $checkData;
    }

    public function getLastCheck(int $url_id): ?Check
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$url_id]);

        if ($row = $stmt->fetch()) {
            $lastCheck = new Check();
            $lastCheck->setId($row['id']);
            $lastCheck->setUrlId($row['url_id']);
            $lastCheck->setStatusCode($row['status_code']);
            $lastCheck->setCheckDate($row['created_at']);
            return $lastCheck;
        }

        return null;
    }
}
