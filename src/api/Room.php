<?php

namespace src\api;

class Room
{

    public string $owner;
    public array $users = [];

    public function __construct(?array $load = null)
    {
        if ($load !== null) {
            foreach ($load as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }
    }

}