<?php

namespace Analyzer;

class Url
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDate(): ?string
    {
        return $this->created_at;
    }

    public function setDate(string $created_at): void
    {
        $this->created_at = $created_at;
    }
}
