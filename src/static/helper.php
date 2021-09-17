<?php

function getRandomString($length): string
{
    $id = '';
    $characters = 'abcdefghjkmnopqrstuvwxyz0123456789';
    $max = strlen(RANDOM_CHARACTERS);
    for ($i = 0; $i < $length; $i++) {
        $number = random_int(0, $max - 1);
        $character = RANDOM_CHARACTERS[$number];
        $id .= $character;
    }
    return $id;
}

if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
