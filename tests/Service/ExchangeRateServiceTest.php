<?php

namespace App\Tests\Service;

use App\Service\ExchangeRateService;
use PHPUnit\Framework\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    private ExchangeRateService $exchangeRateService;
    
    protected function setUp(): void
    {
        $this->exchangeRateService = new ExchangeRateService();
    }
    
    public function testConvertTNDToUSD(): void
    {
        $result = $this->exchangeRateService->convertir(100, 'TND', 'USD');
        $this->assertEquals(33.33, round($result, 2));
    }
    
    public function testConvertTNDToEUR(): void
    {
        $result = $this->exchangeRateService->convertir(100, 'TND', 'EUR');
        $this->assertEquals(31.25, round($result, 2));
    }
    
    public function testConvertUSDToTND(): void
    {
        $result = $this->exchangeRateService->convertir(100, 'USD', 'TND');
        $this->assertEquals(303.03, round($result, 2));
    }
    
    public function testConvertEURToTND(): void
    {
        $result = $this->exchangeRateService->convertir(100, 'EUR', 'TND');
        $this->assertEquals(322.58, round($result, 2));
    }
    
    public function testConvertSameCurrencyReturnsSameValue(): void
    {
        $result = $this->exchangeRateService->convertir(100, 'TND', 'TND');
        $this->assertEquals(100, $result);
    }
    
    public function testConvertZeroReturnsZero(): void
    {
        $result = $this->exchangeRateService->convertir(0, 'TND', 'USD');
        $this->assertEquals(0, $result);
    }
    
    public function testConvertNegativeAmount(): void
    {
        $result = $this->exchangeRateService->convertir(-100, 'TND', 'USD');
        $this->assertEquals(-33.33, round($result, 2));
    }
    
    public function testConvertLargeAmount(): void
    {
        $result = $this->exchangeRateService->convertir(1000000, 'TND', 'USD');
        $this->assertEquals(333333.33, round($result, 2));
    }
}