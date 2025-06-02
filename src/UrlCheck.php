<?php

namespace Analyzer;

class UrlCheck
{
    private ?int $id;
    private ?int $urlId = null;
    private ?int $statusCode = null;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $createdAt;

    public function getId()
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUrlId()
    {
        return $this->urlId;
    }

    public function setUrlId(?int $urlId): void
    {
        $this->urlId = $urlId;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setStatusCode(?int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function getH1()
    {
        return $this->h1;
    }

    public function setH1(?string $h1): void
    {
        $this->h1 = $h1;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCheckDate()
    {
        return $this->createdAt;
    }

    public function setCheckDate(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
