<?php

declare(strict_types=1);

return [
    'admin' => [
        'id' => 1,
        'username' => 'admin',
        'auth_key' => 'test-auth-key-admin',
        'password_hash' => '$2y$13$EjaPFBnZOQsHdGuHI.xvhuDp1fHpo8hKRSk6yshqa9c5EG8s3C3lO', // password: admin123
        'email' => 'admin@example.com',
        'status' => 10,
        'created_at' => 1577836800,
        'updated_at' => 1577836800,
    ],
    'user1' => [
        'id' => 2,
        'username' => 'user1',
        'auth_key' => 'test-auth-key-user1',
        'password_hash' => '$2y$13$EjaPFBnZOQsHdGuHI.xvhuDp1fHpo8hKRSk6yshqa9c5EG8s3C3lO', // password: user123
        'email' => 'user1@example.com',
        'status' => 10,
        'created_at' => 1577836800,
        'updated_at' => 1577836800,
    ],
    'inactive' => [
        'id' => 3,
        'username' => 'inactive',
        'auth_key' => 'test-auth-key-inactive',
        'password_hash' => '$2y$13$EjaPFBnZOQsHdGuHI.xvhuDp1fHpo8hKRSk6yshqa9c5EG8s3C3lO',
        'email' => 'inactive@example.com',
        'status' => 9,
        'created_at' => 1577836800,
        'updated_at' => 1577836800,
    ],
];
