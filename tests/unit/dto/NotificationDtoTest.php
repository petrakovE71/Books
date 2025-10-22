<?php

declare(strict_types=1);

namespace tests\unit\dto;

use app\common\dto\NotificationDto;
use Codeception\Test\Unit;

class NotificationDtoTest extends Unit
{
    public function testConstructor(): void
    {
        $dto = new NotificationDto(
            subscriptionId: 1,
            bookId: 10,
            phone: '+79991234567',
            message: 'Test notification message'
        );

        $this->assertEquals(1, $dto->subscriptionId);
        $this->assertEquals(10, $dto->bookId);
        $this->assertEquals('+79991234567', $dto->phone);
        $this->assertEquals('Test notification message', $dto->message);
    }

    public function testImmutability(): void
    {
        $dto = new NotificationDto(
            subscriptionId: 1,
            bookId: 2,
            phone: '+71234567890',
            message: 'Test'
        );

        $this->expectException(\Error::class);

        $dto->message = 'Modified';
    }

    public function testAllPropertiesArePublic(): void
    {
        $dto = new NotificationDto(
            subscriptionId: 5,
            bookId: 20,
            phone: '+79999999999',
            message: 'Public test'
        );

        $this->assertEquals(5, $dto->subscriptionId);
        $this->assertEquals(20, $dto->bookId);
        $this->assertEquals('+79999999999', $dto->phone);
        $this->assertEquals('Public test', $dto->message);
    }
}
