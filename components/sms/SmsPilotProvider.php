<?php

declare(strict_types=1);

namespace app\components\sms;

use Yii;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use app\common\interfaces\SmsProviderInterface;
use app\common\exceptions\SmsDeliveryException;

/**
 * SmsPilotProvider
 * Реализация провайдера для SmsPilot API
 *
 * @see https://smspilot.ru/apikey.php
 */
final class SmsPilotProvider implements SmsProviderInterface
{
    private const API_URL = 'https://smspilot.ru/api.php';

    private readonly Client $httpClient;

    /**
     * @param string $apiKey API ключ от SmsPilot (или "XXXX..." для эмулятора)
     * @param bool $testMode Тестовый режим (не отправляет реальные SMS)
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly bool $testMode = false,
    ) {
        $this->httpClient = new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function sendSms(string $phone, string $message): bool
    {
        try {
            Yii::info("Sending SMS to {$phone}: {$message}", __METHOD__);

            // В тестовом режиме просто логируем
            if ($this->testMode) {
                Yii::info("TEST MODE: SMS would be sent to {$phone}", __METHOD__);
                return true;
            }

            // Отправляем запрос к API SmsPilot
            $response = $this->httpClient->post(self::API_URL, [
                'form_params' => [
                    'send' => $message,
                    'to' => $this->normalizePhone($phone),
                    'apikey' => $this->apiKey,
                    'format' => 'json',
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // Проверяем ответ
            if (isset($data['error'])) {
                throw new SmsDeliveryException(
                    "SmsPilot API error: {$data['error']['description']} (code: {$data['error']['code']})"
                );
            }

            if (isset($data['send']) && count($data['send']) > 0) {
                $sendResult = $data['send'][0];

                if (isset($sendResult['status']) && $sendResult['status'] === 0) {
                    // Успешно отправлено
                    Yii::info("SMS sent successfully to {$phone}, server_id: {$sendResult['server_id']}", __METHOD__);
                    return true;
                }

                // Ошибка отправки
                $errorDescription = $sendResult['error']['description'] ?? 'Unknown error';
                throw new SmsDeliveryException("Failed to send SMS: {$errorDescription}");
            }

            throw new SmsDeliveryException('Unexpected API response format');
        } catch (GuzzleException $e) {
            Yii::error("HTTP error while sending SMS: {$e->getMessage()}", __METHOD__);
            throw new SmsDeliveryException("HTTP error: {$e->getMessage()}", 0, $e);
        } catch (\Throwable $e) {
            Yii::error("Error while sending SMS: {$e->getMessage()}", __METHOD__);
            throw new SmsDeliveryException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'SmsPilot';
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        try {
            // Проверяем доступность API простым запросом
            $response = $this->httpClient->get(self::API_URL, [
                'query' => [
                    'apikey' => $this->apiKey,
                    'balance' => 'json',
                ],
                'timeout' => 3,
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Throwable $e) {
            Yii::warning("SmsPilot API unavailable: {$e->getMessage()}", __METHOD__);
            return false;
        }
    }

    /**
     * Нормализовать телефон для SmsPilot API
     * Убирает + и оставляет только цифры
     *
     * @param string $phone
     * @return string
     */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^\d]/', '', $phone);
    }
}
