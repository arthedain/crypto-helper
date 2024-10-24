<?php

namespace App\Services\Api;

use App\Dto\TradingPairDTO;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class JbexService implements ExchangeApiContract
{
    private string $name = 'Jbex';
    private const BASE_URL = 'https://api.jbex.com';
    private PendingRequest $client;

    public function __construct()
    {
        $this->client = Http::withHeaders([]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTradingPairs(): Collection
    {
        return $this->client->get(self::BASE_URL . '/openapi/v1/brokerInfo', ['status' => 'TRADING'])->collect('symbols');
    }

    public function checkTradingPair(string $firstCurrency, string $secondCurrency): bool
    {
        $response = $this->getTradingPairs();
        return $response->contains('symbol', strtoupper($firstCurrency.$secondCurrency)) || $response->contains('symbol', strtoupper($secondCurrency.$firstCurrency));
    }

    /**
     * @throws Exception
     */
    public function getTradingPair(string $firstCurrency, string $secondCurrency): null|TradingPairDTO
    {
        try {
            if($this->checkTradingPair($firstCurrency, $secondCurrency)) {
                $response = $this->client->get(self::BASE_URL . '/openapi/quote/v1/ticker/price', [
                    'symbol' => strtoupper($firstCurrency.$secondCurrency),
                ]);
                if(!$response->successful()) {
                    $response = $this->client->get(self::BASE_URL . '/openapi/quote/v1/ticker/price', [
                        'symbol' => strtoupper($secondCurrency.$firstCurrency),
                    ]);
                    if(!$response->successful()) {
                        throw new Exception('Error while getting exchange rate from '. $this->getName());
                    }
                }

                $data = $response->json();

                return new TradingPairDTO(
                    $this->getName(),
                    $data['symbol'],
                    (float) ($data['price'] ?? 0),
                );
            }

            return null;
        } catch (\Exception $e) {
            throw new Exception('Error while getting exchange rate from '.$this->getName());
        }
    }
}
