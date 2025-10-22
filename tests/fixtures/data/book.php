<?php

declare(strict_types=1);

return [
    'book1' => [
        'id' => 1,
        'title' => 'Test Book 1',
        'year' => 2023,
        'description' => 'Description for test book 1',
        'isbn' => '978-3-16-148410-0',
        'cover_photo' => null,
        'created_at' => 1577836800,
        'updated_at' => 1577836800,
        'deleted_at' => null,
    ],
    'book2' => [
        'id' => 2,
        'title' => 'Test Book 2',
        'year' => 2024,
        'description' => 'Description for test book 2',
        'isbn' => '978-3-16-148410-1',
        'cover_photo' => null,
        'created_at' => 1577836800,
        'updated_at' => 1577836800,
        'deleted_at' => null,
    ],
    'deleted_book' => [
        'id' => 3,
        'title' => 'Deleted Book',
        'year' => 2022,
        'description' => 'This book was deleted',
        'isbn' => '978-3-16-148410-2',
        'cover_photo' => null,
        'created_at' => 1577836800,
        'updated_at' => 1577836800,
        'deleted_at' => 1609459200,
    ],
];
