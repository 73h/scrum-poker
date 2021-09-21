<?php

namespace src\api;

class Cards
{

    public static function default(): object
    {
        return (object)array(
            '1' => new Card('1', Card::LOW),
            '2' => new Card('2', Card::LOW),
            '3' => new Card('3', Card::LOW),
            '4' => new Card('5', Card::MIDDLE),
            '5' => new Card('8', Card::MIDDLE),
            '6' => new Card('13', Card::MIDDLE),
            '7' => new Card('20', Card::HIGH),
            '8' => new Card('40', Card::HIGH),
            '9' => new Card('100', Card::HIGH),
            '10' => new Card('?', Card::UNKNOWN),
            '11' => new Card('break', Card::UNKNOWN),
        );
    }

    public static function fibonacci(): object
    {
        return (object)array(
            '1' => new Card('1', Card::LOW),
            '2' => new Card('2', Card::LOW),
            '3' => new Card('3', Card::MIDDLE),
            '4' => new Card('5', Card::MIDDLE),
            '5' => new Card('8', Card::MIDDLE),
            '6' => new Card('13', Card::HIGH),
            '7' => new Card('21', Card::HIGH),
            '8' => new Card('?', Card::UNKNOWN),
            '9' => new Card('break', Card::UNKNOWN),
        );
    }

    public static function fibonacci_ext(): object

    {
        return (object)array(
            '1' => new Card('1', Card::LOW),
            '2' => new Card('2', Card::LOW),
            '3' => new Card('3', Card::LOW),
            '4' => new Card('5', Card::MIDDLE),
            '5' => new Card('8', Card::MIDDLE),
            '6' => new Card('13', Card::MIDDLE),
            '7' => new Card('21', Card::MIDDLE),
            '8' => new Card('34', Card::HIGH),
            '9' => new Card('55', Card::HIGH),
            '10' => new Card('89', Card::HIGH),
            '11' => new Card('144', Card::HIGH),
            '12' => new Card('?', Card::UNKNOWN),
            '13' => new Card('break', Card::UNKNOWN),
        );
    }

    public static function smilies(): object
    {
        return (object)array(
            '1' => new Card(':D', Card::LOW),
            '2' => new Card(':)', Card::LOW),
            '3' => new Card(':|', Card::MIDDLE),
            '4' => new Card(':/', Card::MIDDLE),
            '5' => new Card(':(', Card::HIGH),
            '6' => new Card(':O', Card::HIGH),
            '7' => new Card('?', Card::UNKNOWN),
            '8' => new Card('break', Card::UNKNOWN),
        );
    }

    public static function sizes(): object
    {
        return (object)array(
            '1' => new Card('XS', Card::LOW),
            '2' => new Card('S', Card::LOW),
            '3' => new Card('M', Card::MIDDLE),
            '4' => new Card('L', Card::MIDDLE),
            '5' => new Card('XL', Card::HIGH),
            '6' => new Card('XXL', Card::HIGH),
            '7' => new Card('?', Card::UNKNOWN),
            '8' => new Card('break', Card::UNKNOWN),
        );
    }

    public static function school(): object
    {
        return (object)array(
            '1' => new Card('1', Card::LOW),
            '2' => new Card('2', Card::LOW),
            '3' => new Card('3', Card::MIDDLE),
            '4' => new Card('4', Card::MIDDLE),
            '5' => new Card('5', Card::HIGH),
            '6' => new Card('6', Card::HIGH),
            '7' => new Card('?', Card::UNKNOWN),
            '8' => new Card('break', Card::UNKNOWN),
        );
    }

    public static function allCards(): object
    {
        return (object)array(
            'default' => self::default(),
            'fibonacci' => self::fibonacci(),
            'fibonacci_ext' => self::fibonacci_ext(),
            'smilies' => self::smilies(),
            'sizes' => self::sizes(),
            'school' => self::school()
        );
    }

}
