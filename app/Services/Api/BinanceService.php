<?php

namespace App\Services\Api;

use App\Dto\TradingPairDTO;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class BinanceService implements ExchangeApiContract
{
    private string $name = 'Binance';
    private const BASE_URL = 'https://api.binance.com/api/v3';
    private PendingRequest $client;

    public function __construct()
    {
        $this->client = Http::withHeaders([]);
    }

    /**
     * @throws Exception
     */
    public function getTradingPairs(): Collection
    {
        try {
            return $this->client->get(self::BASE_URL.'/exchangeInfo')->collect('symbols');
        } catch (\Exception $e) {
            throw new Exception('Error while getting trading pairs from Binance');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws Exception
     */
    public function checkTradingPair(string $firstCurrency, string $secondCurrency): bool
    {
        $tradingPairs = $this->getTradingPairs();

        $tradingPairs = $tradingPairs->where('status', 'TRADING')->pluck('symbol');

        return $tradingPairs->contains(strtoupper($firstCurrency . $secondCurrency)) || $tradingPairs->contains(strtoupper($secondCurrency . $firstCurrency));
    }

    /**
     * @throws Exception
     */
    public function getTradingPair(string $firstCurrency, string $secondCurrency): null|TradingPairDTO
    {
        try {
            if($this->checkTradingPair($firstCurrency, $secondCurrency)) {
                $response = $this->client->get(self::BASE_URL . '/ticker/price?symbol=' . strtoupper($firstCurrency.$secondCurrency));
                if(!$response->successful()) {
                    $response = $this->client->get(self::BASE_URL . '/ticker/price?symbol=' . strtoupper($secondCurrency.$firstCurrency));
                    if(!$response->successful()) {
                        throw new Exception('Error while getting exchange rate from '. $this->name);
                    }
                }
                $data = $response->json();

                return new TradingPairDTO(
                    $this->getName(),
                    $data['symbol'],
                    (float)($data['price'] ?? 0),
                );
            }

            return null;
        } catch (Exception $e) {
            throw new Exception('Error while getting exchange rate from '. $this->name);
        }
    }
}
