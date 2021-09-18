<?php

namespace src\api;

class Card
{

    public const LOW = 'LOW';
    public const MIDDLE = 'MIDDLE';
    public const HIGH = 'HIGH';
    public const UNKNOWN = 'UNKNOWN';

    public string $value;
    public string $complexity;

    public function __construct(string $value, string $complexity)
    {
        $this->value = $value;
        $this->complexity = $complexity;
    }

}