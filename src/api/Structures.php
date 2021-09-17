<?php

namespace src\api;

class Structures
{

    public static function rooms(): array
    {
        return [
            new Structure('user', required: true, children: [
                new Structure('name', required: true, max_length: 20),
                new Structure('password')
            ]),
            new Structure('room', children: [
                new Structure('name', max_length: 20),
                new Structure('password')
            ])
        ];
    }

    public static function rooms_users(): array
    {
        return [
            new Structure('name', required: true, max_length: 20),
            new Structure('password')
        ];
    }

}
