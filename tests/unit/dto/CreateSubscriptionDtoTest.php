<?php

declare(strict_types=1);

namespace tests\unit\dto;

use app\common\dto\CreateSubscriptionDto;
use Codeception\Test\Unit;

class CreateSubscriptionDtoTest extends Unit
{
    public function testConstructor(): void
    {
        $dto = new CreateSubscriptionDto(
            authorId: 1,
            name: 'John Doe',
            phone: '+79991234567'
        );

        $this->assertEquals(1, $dto->authorId);
        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('+79991234567', $dto->phone);
    }

    public function testFromArray(): void
    {
        $data = [
            'author_id' => 2,
            'name' => 'Jane Smith',
            'phone' => '+79997654321',
        ];

        $dto = CreateSubscriptionDto::fromArray($data);

        $this->assertInstanceOf(CreateSubscriptionDto::class, $dto);
        $this->assertEquals(2, $dto->authorId);
        $this->assertEquals('Jane Smith', $dto->name);
        $this->assertEquals('+79997654321', $dto->phone);
    }

    public function testFromArrayTypeConversion(): void
    {
        $data = [
            'author_id' => '5',
            'name' => 'Test User',
            'phone' => '+79999999999',
        ];

        $dto = CreateSubscriptionDto::fromArray($data);

        $this->assertIsInt($dto->authorId);
        $this->assertEquals(5, $dto->authorId);
    }

    public function testImmutability(): void
    {
        $dto = new CreateSubscriptionDto(
            authorId: 1,
            name: 'Test',
            phone: '+71234567890'
        );

        $this->expectException(\Error::class);

        $dto->name = 'Modified';
    }
}
