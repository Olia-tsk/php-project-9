<?php

namespace Analyzer;

class UrlCheck
{
    private ?int $id = null;
    private ?int $url_id = null;
    private ?int $status_code = null;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $created_at = null;

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
        return $this->url_id;
    }

    public function setUrlId(?int $url_id): void
    {
        $this->url_id = $url_id;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function setStatusCode(?int $status_code): void
    {
        $this->status_code = $status_code;
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
        return $this->created_at;
    }

    public function setCheckDate(string $created_at): void
    {
        $this->created_at = $created_at;
    }
}
