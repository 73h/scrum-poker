<?php

namespace src\api;

class Structures
{

    public static function sessions_users(): array
    {
        return [
            new Structure('user', required: true, children: [
                new Structure('name', required: true, max_length: 20),
                new Structure('password')
            ]),
            new Structure('session', children: [
                new Structure('name', max_length: 20),
                new Structure('password')
            ])
        ];
    }

    public static function vote(): array
    {
        return [
            new Structure('card', required: true),
        ];
    }

    public static function vote_put(): array
    {
        return [
            new Structure('task', required: true),
        ];
    }

    public static function empty(): array
    {
        return [];
    }

}
