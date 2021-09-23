<?php

namespace src\api;

class Structure
{

    public string $name;
    public bool $required;
    public string $type;
    public ?int $max_length;
    public ?int $min_length;
    public ?array $children;

    public function __construct(
        string $name,
        bool   $required = false,
        string $type = 'string',
        ?int   $max_length = null,
        ?int   $min_length = null,
        ?array $children = null
    )
    {
        $this->name = $name;
        $this->required = $required;
        $this->type = $type;
        $this->max_length = $max_length;
        $this->min_length = $min_length;
        $this->children = $children;
        if ($this->children !== null) {
            $this->type = 'object';
        }
    }

}