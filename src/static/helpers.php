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
