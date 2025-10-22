<?php

declare(strict_types=1);

namespace app\components\sms;

use Yii;
use app\common\interfaces\SmsProviderInterface;
use app\common\exceptions\SmsDeliveryException;
use yii\base\Component;

/**
 * SmsService
 * Фасад для работы с SMS провайдерами
 * Включает retry logic, rate limiting, circuit breaker pattern
 */
final class SmsService extends Component
{
    private int $maxRequestsPerMinute = 30;
    private array $requestTimestamps = [];

    private int $failureThreshold = 5;
    private int $failureResetTime = 300; // 5 minutes
    private array $failures = [];

    public function __construct(
        private readonly SmsProviderInterface $provider,
    ) {
        parent::__construct();
    }

    /**
     *
     * @param string $phone
     * @param string $message
     * @param int $maxRetries
     * @return bool
     * @throws SmsDeliveryException
     */
    public function send(string $phone, string $message, int $maxRetries = 3): bool
    {
        if ($this->isCircuitOpen()) {
            throw new SmsDeliveryException(
                'SMS service temporarily unavailable due to too many failures (circuit breaker is open)'
            );
        }

        if (!$this->checkRateLimit()) {
            throw new SmsDeliveryException('Rate limit exceeded. Please try again later.');
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            $attempt++;

            try {
                Yii::info("SMS sending attempt {$attempt}/{$maxRetries} to {$phone}", __METHOD__);

                $result = $this->provider->sendSms($phone, $message);

                if ($result) {
                    $this->recordSuccess();
                    $this->recordRequest();

                    Yii::info("SMS sent successfully on attempt {$attempt}", __METHOD__);
                    return true;
                }
            } catch (SmsDeliveryException $e) {
                $lastException = $e;

                Yii::warning(
                    "SMS sending attempt {$attempt} failed: {$e->getMessage()}",
                    __METHOD__
                );

                $this->recordFailure();

                if ($attempt < $maxRetries) {
                    $delay = $this->calculateRetryDelay($attempt);
                    Yii::info("Waiting {$delay}s before retry...", __METHOD__);
                    sleep($delay);
                }
            }
        }

        $errorMessage = $lastException ? $lastException->getMessage() : 'Unknown error';

        Yii::error("Failed to send SMS after {$maxRetries} attempts: {$errorMessage}", __METHOD__);

        throw new SmsDeliveryException(
            "Failed to send SMS after {$maxRetries} attempts: {$errorMessage}",
            0,
            $lastException
        );
    }

    /**
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->provider->getProviderName();
    }

    /**
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !$this->isCircuitOpen() && $this->provider->isAvailable();
    }

    /**
     *
     * @param int $attempt Номер попытки (1, 2, 3...)
     * @return int Задержка в секундах
     */
    private function calculateRetryDelay(int $attempt): int
    {
        // Exponential backoff: 1s, 2s, 4s, 8s...
        return min(pow(2, $attempt - 1), 30); // max 30 seconds
    }

    /**
     *
     * @return bool
     */
    private function checkRateLimit(): bool
    {
        $now = time();
        $oneMinuteAgo = $now - 60;

        $this->requestTimestamps = array_filter(
            $this->requestTimestamps,
            fn($timestamp) => $timestamp > $oneMinuteAgo
        );

        return count($this->requestTimestamps) < $this->maxRequestsPerMinute;
    }

    private function recordRequest(): void
    {
        $this->requestTimestamps[] = time();
    }

    /**
     * @return bool
     */
    private function isCircuitOpen(): bool
    {
        $now = time();
        $resetTime = $now - $this->failureResetTime;

        $this->failures = array_filter(
            $this->failures,
            fn($timestamp) => $timestamp > $resetTime
        );

        return count($this->failures) >= $this->failureThreshold;
    }

    private function recordSuccess(): void
    {
        $this->failures = [];
    }

    private function recordFailure(): void
    {
        $this->failures[] = time();
    }

    /**
     * @return array
     */
    public function getStats(): array
    {
        return [
            'provider' => $this->getProviderName(),
            'available' => $this->isAvailable(),
            'circuit_open' => $this->isCircuitOpen(),
            'recent_failures' => count($this->failures),
            'requests_last_minute' => count($this->requestTimestamps),
        ];
    }
}
