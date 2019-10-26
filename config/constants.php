<?php

return [
    'users' => [
        'api' => [
            'endpoint' => env('USER_API', 'https://gitlab.iterato.lt/snippets/3/raw')
        ]
    ],
    'tasks' => [
        'subtask' => [
            'max' => env('MAX_SUBTASK_DEPTH', 5)
        ]
    ]
];
