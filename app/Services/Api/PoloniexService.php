<?php

namespace App\Services\Api;

use App\Dto\TradingPairDTO;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PoloniexService implements ExchangeApiContract
{
    private string $name = 'Poloniex';
    private PendingRequest $client;

    private const BASE_URL = 'https://api.poloniex.com';

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
            return $this->client->get(self::BASE_URL . '/markets/price')->collect();
        } catch (Exception $e) {
            throw new Exception('Error while getting trading pairs from ' . $this->getName());
        }
    }

    /**
     * @throws Exception
     */
    public function checkTradingPair(string $firstCurrency, string $secondCurrency): bool
    {
        try {
            $tradingPairs = $this->getTradingPairs();

            return $tradingPairs->contains('symbol', strtoupper($firstCurrency . '_' . $secondCurrency)) || $tradingPairs->contains('symbol', strtoupper($secondCurrency . '_' . $firstCurrency));
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
            if($this->checkTradingPair($firstCurrency, $secondCurrency)) {
                $tradingPairs = $this->getTradingPairs();

                $data = $tradingPairs->where('symbol', strtoupper($firstCurrency . '_' . $secondCurrency))->first() ?? $tradingPairs->where('symbol', strtoupper($secondCurrency . '_' . $firstCurrency))->first();

                if(!$data) {
                    throw new Exception('Error while getting exchange rate from '. $this->getName());
                }

                return new TradingPairDTO(
                    $this->getName(),
                    $data['symbol'],
                    (float)($data['price'] ?? 0)
                );
            }

            return null;
        } catch (\Exception $e) {
            throw new Exception('Error while getting exchange rate from '.$this->getName());
        }
    }
}
