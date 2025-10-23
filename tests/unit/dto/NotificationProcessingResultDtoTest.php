<?php

declare(strict_types=1);

namespace tests\unit\dto;

use app\common\dto\NotificationProcessingResultDto;
use Codeception\Test\Unit;

class NotificationProcessingResultDtoTest extends Unit
{
    public function testConstructor(): void
    {
        $dto = new NotificationProcessingResultDto(
            totalProcessed: 10,
            successCount: 8,
            failedCount: 2,
            errors: [1 => 'Error message'],
        );

        $this->assertEquals(10, $dto->totalProcessed);
        $this->assertEquals(8, $dto->successCount);
        $this->assertEquals(2, $dto->failedCount);
        $this->assertCount(1, $dto->errors);
    }

    public function testConstructorWithDefaultErrors(): void
    {
        $dto = new NotificationProcessingResultDto(
            totalProcessed: 5,
            successCount: 5,
            failedCount: 0,
        );

        $this->assertIsArray($dto->errors);
        $this->assertEmpty($dto->errors);
    }

    public function testIsFullySuccessful(): void
    {
        $successDto = new NotificationProcessingResultDto(
            totalProcessed: 10,
            successCount: 10,
            failedCount: 0,
        );

        $this->assertTrue($successDto->isFullySuccessful());

        $failedDto = new NotificationProcessingResultDto(
            totalProcessed: 10,
            successCount: 8,
            failedCount: 2,
        );

        $this->assertFalse($failedDto->isFullySuccessful());
    }

    public function testHasProcessed(): void
    {
        $processedDto = new NotificationProcessingResultDto(
            totalProcessed: 5,
            successCount: 5,
            failedCount: 0,
        );

        $this->assertTrue($processedDto->hasProcessed());

        $emptyDto = new NotificationProcessingResultDto(
            totalProcessed: 0,
            successCount: 0,
            failedCount: 0,
        );

        $this->assertFalse($emptyDto->hasProcessed());
    }

    public function testGetSuccessRate(): void
    {
        $dto = new NotificationProcessingResultDto(
            totalProcessed: 10,
            successCount: 8,
            failedCount: 2,
        );

        $this->assertEquals(80.0, $dto->getSuccessRate());
    }

    public function testGetSuccessRateWithZeroProcessed(): void
    {
        $dto = new NotificationProcessingResultDto(
            totalProcessed: 0,
            successCount: 0,
            failedCount: 0,
        );

        $this->assertEquals(0.0, $dto->getSuccessRate());
    }

    public function testGetSuccessRateWithAllFailed(): void
    {
        $dto = new NotificationProcessingResultDto(
            totalProcessed: 10,
            successCount: 0,
            failedCount: 10,
        );

        $this->assertEquals(0.0, $dto->getSuccessRate());
    }

    public function testGetSuccessRateWithAllSuccess(): void
    {
        $dto = new NotificationProcessingResultDto(
            totalProcessed: 10,
            successCount: 10,
            failedCount: 0,
        );

        $this->assertEquals(100.0, $dto->getSuccessRate());
    }

    public function testToArray(): void
    {
        $dto = new NotificationProcessingResultDto(
            totalProcessed: 10,
            successCount: 7,
            failedCount: 3,
            errors: [1 => 'Error 1', 2 => 'Error 2'],
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('total_processed', $array);
        $this->assertArrayHasKey('success_count', $array);
        $this->assertArrayHasKey('failed_count', $array);
        $this->assertArrayHasKey('success_rate', $array);
        $this->assertArrayHasKey('errors', $array);

        $this->assertEquals(10, $array['total_processed']);
        $this->assertEquals(7, $array['success_count']);
        $this->assertEquals(3, $array['failed_count']);
        $this->assertEquals(70.0, $array['success_rate']);
        $this->assertCount(2, $array['errors']);
    }

    public function testImmutability(): void
    {
        $dto = new NotificationProcessingResultDto(
            totalProcessed: 10,
            successCount: 8,
            failedCount: 2,
        );

        $this->expectException(\Error::class);
        $dto->totalProcessed = 20;
    }

    public function testReadonlyProperties(): void
    {
        $dto = new NotificationProcessingResultDto(
            totalProcessed: 10,
            successCount: 8,
            failedCount: 2,
            errors: [1 => 'Error'],
        );

        $reflection = new \ReflectionClass($dto);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testSuccessRateRounding(): void
    {
        $dto = new NotificationProcessingResultDto(
            totalProcessed: 3,
            successCount: 2,
            failedCount: 1,
        );

        $rate = $dto->getSuccessRate();

        $this->assertIsFloat($rate);
        $this->assertEqualsWithDelta(66.67, $rate, 0.01);
    }
}
