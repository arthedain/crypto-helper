<?php

namespace App\Services\Api;

use App\Dto\TradingPairDTO;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class BybitService implements ExchangeApiContract
{
    private string $name = 'Bybit';
    private const BASE_URL = 'https://api-testnet.bybit.com';

    private PendingRequest $client;

    public function __construct()
    {
        $this->client = Http::withHeaders([]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws Exception
     */
    public function getTradingPairs(): Collection
    {
        try {
            return $this->client->get(self::BASE_URL . '/v5/market/tickers', ['category' => 'spot'])->collect('result.list');
        } catch (Exception $e) {
            throw new Exception('Error while getting trading pairs from ' . $this->getName() . ' API.');
        }
    }

    /**
     * @throws Exception
     */
    public function checkTradingPair(string $firstCurrency, string $secondCurrency): bool
    {
        try {
            $tradingPairs = $this->getTradingPairs();
            return $tradingPairs->contains('symbol', strtoupper($firstCurrency . $secondCurrency)) || $tradingPairs->contains('symbol', strtoupper($secondCurrency . $firstCurrency));
        } catch (Exception $e) {
            throw new Exception('Error while checking trading pair ' . $firstCurrency . '/' . $secondCurrency . ' from ' . $this->getName());
        }
    }

    /**
     * @throws Exception
     */
    public function getTradingPair(string $firstCurrency, string $secondCurrency): null|TradingPairDTO
    {
        try {
            if ($this->checkTradingPair($firstCurrency, $secondCurrency)) {
                $response = $this->requestMarketTicker($firstCurrency, $secondCurrency, 'linear');

                if(!$response || !$response->successful() || $response->json('retCode') !== 0) {
                    $response = $this->requestMarketTicker($secondCurrency, $firstCurrency, 'spot');
                    if(!$response || !$response->successful() || $response->json('retCode') !== 0) {
                        return null;
                    }
                }

                $data = $response->json('result.list.0');

                if(!$data) {
                    return null;
                }

                return new TradingPairDTO(
                    $this->getName(),
                    $data['symbol'],
                    (float)($data['indexPrice'] ?? $data['lastPrice'] ?? 0),
                );
            }

            return null;
        } catch (Exception $e) {
            throw new Exception('Error while getting exchange rate from ' . $this->getName());
        }
    }

    /**
     * @throws Exception
     */
    public function requestMarketTicker(string $firstCurrency, string $secondCurrency, string $category): ?Response
    {
        try {
            $response = $this->client->get(self::BASE_URL . '/v5/market/tickers', [
                'symbol' => strtoupper($firstCurrency . $secondCurrency),
                'category' => $category,
            ]);

            if (!$response->successful() || $response->json('retCode') !== 0) {
                $response = $this->client->get(self::BASE_URL . '/v5/market/tickers', [
                    'symbol' => strtoupper($secondCurrency . $firstCurrency),
                    'category' => $category,
                ]);

                if (!$response->successful() || $response->json('retCode') !== 0) {
                    return null;
                }
            }

            return $response;
        } catch (Exception $e) {
            throw new Exception('Error while getting exchange rate from ' . $this->getName());
        }
    }
}
