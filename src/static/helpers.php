<?php

/**
 * @throws Exception
 */
function getRandomString($length): string
{
    $id = '';
    $characters = 'abcdefghjkmnopqrstuvwxyz0123456789';
    $max = strlen($characters);
    for ($i = 0; $i < $length; $i++) {
        $number = random_int(0, $max - 1);
        $character = $characters[$number];
        $id .= $character;
    }
    return $id;
}

function getAppUri(): string
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' or $_SERVER['SERVER_PORT'] == 443) $protocol = 'https://';
    else $protocol = 'http://';
    return $protocol . $_SERVER['SERVER_NAME'];
}
