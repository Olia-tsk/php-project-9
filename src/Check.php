<?php

namespace Analyzer;

class Check
{
    private ?int $url_id = null;
    private ?string $name = null;
    private ?string $last_check = null;
    private ?int $status_code = null;

    public function getUrlId()
    {
        return $this->url_id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLastCheck()
    {
        return $this->last_check;
    }

    public function getStatusCode()
    {
        return  $this->status_code;
    }

    public function setUrlId(?int $url_id)
    {
        $this->url_id = $url_id;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function setLastCheck(string $last_check): void
    {
        $this->last_check = $last_check;
    }

    public function setStatusCode(?int $status_code): void
    {
        $this->status_code = $status_code;
    }
}
