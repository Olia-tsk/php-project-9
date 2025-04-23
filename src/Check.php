<?php

namespace Analyzer;

class Check
{
    private ?string $last_check = null;
    private ?int $status_code = null;

    public function getLastCheck()
    {
        return $this->last_check;
    }

    public function getStatusCode()
    {
        return  $this->status_code;
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
