<?php

namespace App\Tests\Service;

use App\Service\RecaptchaService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RecaptchaServiceTest extends TestCase
{
    public function testIsHumanWithHighScore(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['success' => true, 'score' => 0.9]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $service = new RecaptchaService($httpClient, 'fake_secret');
        $this->assertTrue($service->isHuman('fake_token'));
    }

    public function testIsHumanWithLowScore(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['success' => true, 'score' => 0.1]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);
        

        $service = new RecaptchaService($httpClient, 'fake_secret');
        $this->assertFalse($service->isHuman('fake_token'));
    }

    public function testIsHumanWithEmptyToken(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $service = new RecaptchaService($httpClient, 'fake_secret');
        $this->assertFalse($service->isHuman(''));
    }
}
