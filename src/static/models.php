<?php

const RANDOM_CHARACTERS = 'abcdefghjkmnopqrstuvwxyz0123456789';
const TOKEN_LENGTH = 16;

$CARD_SETS = (object)array(
    'default' => (object)array(
        '0' => (object)array('v' => '0', 'c' => 'G'),
        '1' => (object)array('v' => '1', 'c' => 'G'),
        '2' => (object)array('v' => '2', 'c' => 'G'),
        '3' => (object)array('v' => '3', 'c' => 'G'),
        '4' => (object)array('v' => '5', 'c' => 'Y'),
        '5' => (object)array('v' => '8', 'c' => 'Y'),
        '6' => (object)array('v' => '13', 'c' => 'Y'),
        '7' => (object)array('v' => '20', 'c' => 'R'),
        '8' => (object)array('v' => '40', 'c' => 'R'),
        '9' => (object)array('v' => '100', 'c' => 'R'),
        '10' => (object)array('v' => '?', 'c' => 'B'),
        '11' => (object)array('v' => 'break', 'c' => 'B')
    )
);
