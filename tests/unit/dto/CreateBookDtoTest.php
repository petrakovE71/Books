<?php

declare(strict_types=1);

namespace tests\unit\dto;

use app\common\dto\CreateBookDto;
use Codeception\Test\Unit;

class CreateBookDtoTest extends Unit
{
    public function testConstructor(): void
    {
        $dto = new CreateBookDto(
            title: 'Test Book',
            year: 2024,
            isbn: '978-3-16-148410-0',
            authorIds: [1, 2],
            description: 'Test description',
            coverPhoto: '/uploads/test.jpg'
        );

        $this->assertEquals('Test Book', $dto->title);
        $this->assertEquals(2024, $dto->year);
        $this->assertEquals('978-3-16-148410-0', $dto->isbn);
        $this->assertEquals([1, 2], $dto->authorIds);
        $this->assertEquals('Test description', $dto->description);
        $this->assertEquals('/uploads/test.jpg', $dto->coverPhoto);
    }

    public function testConstructorWithOptionalFields(): void
    {
        $dto = new CreateBookDto(
            title: 'Test Book',
            year: 2024,
            isbn: '978-3-16-148410-0',
            authorIds: [1]
        );

        $this->assertNull($dto->description);
        $this->assertNull($dto->coverPhoto);
    }

    public function testFromArray(): void
    {
        $data = [
            'title' => 'Array Book',
            'year' => 2023,
            'isbn' => '978-3-16-148410-1',
            'authorIds' => [1, 2, 3],
            'description' => 'From array',
            'coverPhoto' => '/test.jpg',
        ];

        $dto = CreateBookDto::fromArray($data);

        $this->assertInstanceOf(CreateBookDto::class, $dto);
        $this->assertEquals('Array Book', $dto->title);
        $this->assertEquals(2023, $dto->year);
        $this->assertEquals('978-3-16-148410-1', $dto->isbn);
        $this->assertEquals([1, 2, 3], $dto->authorIds);
        $this->assertEquals('From array', $dto->description);
        $this->assertEquals('/test.jpg', $dto->coverPhoto);
    }

    public function testFromArrayWithMissingOptionalFields(): void
    {
        $data = [
            'title' => 'Minimal Book',
            'year' => 2024,
            'isbn' => '978-3-16-148410-2',
        ];

        $dto = CreateBookDto::fromArray($data);

        $this->assertEquals('Minimal Book', $dto->title);
        $this->assertEquals([], $dto->authorIds);
        $this->assertNull($dto->description);
        $this->assertNull($dto->coverPhoto);
    }

    public function testImmutability(): void
    {
        $dto = new CreateBookDto(
            title: 'Test',
            year: 2024,
            isbn: '123',
            authorIds: [1]
        );

        $this->expectException(\Error::class);

        $dto->title = 'Modified';
    }
}
