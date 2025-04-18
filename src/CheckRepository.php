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

    public function getCheck(int $url_id): array
    {
        $checkData = [];
        $sql = "SELECT * FROM url_checks WHERE url_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$url_id]);

        while ($row = $stmt->fetch()) {
            $check['id'] = $row['id'];
            $check['url_id'] = $row['url_id'];
            $check['status_code'] = $row['status_code'];
            $check['h1'] = $row['h1'];
            $check['title'] = $row['title'];
            $check['description'] = $row['description'];
            $check['created_at'] = $row['created_at'];
            $checkData[] = $check;
        }

        return $checkData;
    }

    public function getLastCheck(int $url_id)
    {
        $lastCheck = [];
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$url_id]);

        if ($row = $stmt->fetch()) {
            $lastCheck['id'] = $row['id'];
            $lastCheck['url_id'] = $row['url_id'];
            $lastCheck['status_code'] = $row['status_code'];
            $lastCheck['created_at'] = $row['created_at'];
        }

        return $lastCheck;
    }
}
