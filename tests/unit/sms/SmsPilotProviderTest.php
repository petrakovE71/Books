<?php

declare(strict_types=1);

namespace tests\unit\sms;

use app\common\sms\SmsPilotProvider;
use Codeception\Test\Unit;

class SmsPilotProviderTest extends Unit
{
    private SmsPilotProvider $provider;

    protected function _before(): void
    {
        parent::_before();

        // Initialize with test API key
        $this->provider = new SmsPilotProvider('test_api_key');
    }

    public function testConstructor(): void
    {
        $provider = new SmsPilotProvider('my_api_key');

        $this->assertInstanceOf(SmsPilotProvider::class, $provider);
    }

    public function testFormatPhoneNumber(): void
    {
        $testCases = [
            ['+79991234567', '79991234567'],
            ['+71234567890', '71234567890'],
        ];

        foreach ($testCases as [$input, $expected]) {
            $formatted = $this->provider->formatPhone($input);
            $this->assertEquals($expected, $formatted);
        }
    }

    public function testBuildRequestParams(): void
    {
        $phone = '+79991234567';
        $message = 'Test message';

        $params = $this->provider->buildParams($phone, $message);

        $this->assertIsArray($params);
        $this->assertArrayHasKey('apikey', $params);
        $this->assertArrayHasKey('to', $params);
        $this->assertArrayHasKey('text', $params);
        $this->assertEquals('test_api_key', $params['apikey']);
        $this->assertEquals('79991234567', $params['to']);
        $this->assertEquals('Test message', $params['text']);
    }

    public function testSendMethodStructure(): void
    {
        // This test verifies the method signature exists
        $this->assertTrue(method_exists($this->provider, 'send'));

        $reflection = new \ReflectionMethod($this->provider, 'send');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals(2, $reflection->getNumberOfParameters());
    }

    public function testParseResponse(): void
    {
        // Test successful response
        $successResponse = json_encode([
            'send' => [
                ['server_id' => '12345', 'status' => 1],
            ],
        ]);

        $result = $this->provider->parseResponse($successResponse);
        $this->assertTrue($result);

        // Test error response
        $errorResponse = json_encode([
            'error' => ['code' => 1, 'description' => 'Invalid API key'],
        ]);

        $result = $this->provider->parseResponse($errorResponse);
        $this->assertFalse($result);
    }

    public function testGetApiEndpoint(): void
    {
        $endpoint = $this->provider->getEndpoint();

        $this->assertIsString($endpoint);
        $this->assertStringContainsString('smspilot', $endpoint);
        $this->assertStringStartsWith('https://', $endpoint);
    }

    public function testValidateApiKey(): void
    {
        // Test valid API key format
        $provider1 = new SmsPilotProvider('XXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
        $this->assertInstanceOf(SmsPilotProvider::class, $provider1);

        // Test empty API key
        $this->expectException(\InvalidArgumentException::class);
        new SmsPilotProvider('');
    }

    public function testHandleRateLimiting(): void
    {
        // Test rate limiting response
        $rateLimitResponse = json_encode([
            'error' => ['code' => 429, 'description' => 'Too many requests'],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Too many requests');

        $this->provider->handleResponse($rateLimitResponse);
    }

    public function testHandleInvalidCredentials(): void
    {
        $invalidResponse = json_encode([
            'error' => ['code' => 401, 'description' => 'Invalid credentials'],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->provider->handleResponse($invalidResponse);
    }

    public function testHandleInsufficientBalance(): void
    {
        $balanceResponse = json_encode([
            'error' => ['code' => 402, 'description' => 'Insufficient balance'],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->provider->handleResponse($balanceResponse);
    }

    public function testMessageLengthLimit(): void
    {
        $shortMessage = 'Short message';
        $this->assertTrue($this->provider->validateMessage($shortMessage));

        // SMS typically limited to 160 characters for GSM, 70 for Unicode
        $longMessage = str_repeat('a', 1000);
        $this->assertFalse($this->provider->validateMessage($longMessage));
    }

    public function testSetTimeout(): void
    {
        $this->provider->setTimeout(30);

        $timeout = $this->provider->getTimeout();
        $this->assertEquals(30, $timeout);
    }

    public function testGetLastError(): void
    {
        $error = $this->provider->getLastError();

        $this->assertIsString($error);
    }

    public function testResetLastError(): void
    {
        $this->provider->resetError();

        $error = $this->provider->getLastError();
        $this->assertEmpty($error);
    }
}
