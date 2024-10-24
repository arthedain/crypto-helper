<?php

namespace App\Services\Api;

use App\Dto\TradingPairDTO;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class WhitebitService implements ExchangeApiContract
{
    private string $name = 'Whitebit';

    private const API_URL = 'https://whitebit.com/api/v4';

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
        return $this->client->get(self::API_URL . '/public/markets')->collect();
    }

    public function checkTradingPair(string $firstCurrency, string $secondCurrency): bool
    {
        $pairs = $this->getTradingPairs();

        return $pairs->contains('name', strtoupper($firstCurrency . '_' . $secondCurrency)) || $pairs->contains('name', strtoupper($secondCurrency . '_' . $firstCurrency));
    }

    /**
     * @throws Exception
     */
    public function getTradingPair(string $firstCurrency, string $secondCurrency): null|TradingPairDTO
    {
        try {
            if($this->checkTradingPair($firstCurrency, $secondCurrency)) {
                $response = $this->client->get(self::API_URL . '/public/ticker');

                if(!$response->successful()) {
                    throw new Exception('Error while getting exchange rate from '. $this->getName());
                }

                $data = $response->collect();

                $symbol = strtoupper($firstCurrency . '_' . $secondCurrency);
                $pair = $data[$symbol] ?? null;

                if(!$pair) {
                    $symbol = strtoupper($secondCurrency . '_' . $firstCurrency);
                    $pair = $data[$symbol] ?? null;

                    if(!$pair) {
                        return null;
                    }
                }

                return new TradingPairDTO(
                    $this->getName(),
                    $symbol,
                    (float)($pair['last_price'] ?? 0)
                );
            }

            return null;
        } catch (\Exception $e) {
            throw new Exception('Error while getting exchange rate from '.$this->getName());
        }
    }
}
