<?php

namespace Analyzer;

use Carbon\Carbon;

class Url
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $created_at = null;
    private ?string $last_check = null;
    private ?int $status_code = null;

    public static function fromArray(array $urlData): Url
    {
        $url = new Url();
        $url->setName($urlData['name']);
        $url->setDate(Carbon::now());
        return $url;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDate(): ?string
    {
        return $this->created_at;
    }

    public function getLastCheck()
    {
        return $this->last_check;
    }

    public function getStatusCode()
    {
        return  $this->status_code;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDate(string $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function setLastCheck($last_check): void
    {
        $this->last_check = $last_check;
    }

    public function setStatusCode($status_code): void
    {
        $this->status_code = $status_code;
    }
}
