<?php

namespace App\Services;

use App\Dto\TradingPairDTO;
use App\Enums\CryptoExchangeEnum;
use App\Services\Api\BinanceService;
use App\Services\Api\BybitService;
use App\Services\Api\ExchangeApiContract;
use App\Services\Api\JbexService;
use App\Services\Api\PoloniexService;
use App\Services\Api\WhitebitService;
use Exception;

class CryptoExchangesService
{
    /**
     * Retrieves the Exchange API service instance corresponding to the given crypto exchange name.
     *
     * @param CryptoExchangeEnum $name The enumeration value representing the name of the crypto exchange.
     *
     * @return ExchangeApiContract The service instance implementing the ExchangeApiContract for the specified exchange.
     * @throws Exception if the specified exchange is not found.
     */
    public function getExchange(CryptoExchangeEnum $name): ExchangeApiContract
    {
        $class = match($name) {
            CryptoExchangeEnum::Bybit => BybitService::class,
            CryptoExchangeEnum::Binance => BinanceService::class,
            CryptoExchangeEnum::Jbex => JbexService::class,
            CryptoExchangeEnum::Poloniex => PoloniexService::class,
            CryptoExchangeEnum::Whitebit => WhitebitService::class,
            default => throw new Exception('Exchange not found'),
        };

        return resolve($class);
    }

    /**
     * Retrieves an array of trading pair data transfer objects (DTOs) for the specified currencies from various exchanges.
     *
     * @param string $firstCurrency The first currency in the trading pair.
     * @param string $secondCurrency The second currency in the trading pair.
     *
     * @return TradingPairDTO[] An array containing the trading pair DTOs from different exchanges.
     * @throws Exception
     */
    public function getTradingPairs(string $firstCurrency, string $secondCurrency): array
    {
        $result = [];

        foreach (CryptoExchangeEnum::cases() as $item) {
            $class = $this->getExchange($item);

            $dto = $class->getTradingPair($firstCurrency, $secondCurrency);

            if(!is_null($dto)) {
                $result[] = $dto;
            }
        }

        return $result;
    }

    /**
     * Retrieves trading pair prices for the specified currencies from various exchanges.
     *
     * @param string $firstCurrency The first currency in the trading pair.
     * @param string $secondCurrency The second currency in the trading pair.
     *
     * @return array An associative array where the key is the exchange name and the value is the trading pair price.
     * @throws Exception
     */
    public function getTradingPairPrices(string $firstCurrency, string $secondCurrency): array
    {
        $result = [];

        $tradingPairs = $this->getTradingPairs($firstCurrency, $secondCurrency);

        foreach ($tradingPairs as $dto) {
            $result[$dto->exchange] = $dto->price;
        }

        return $result;
    }

    /**
     * Finds the maximum and minimum trading pair prices between the given currencies.
     *
     * @param string $firstCurrency The code of the first currency.
     * @param string $secondCurrency The code of the second currency.
     *
     * @return array Returns an associative array containing:
     *               - 'max': The trading pair with the maximum price.
     *               - 'min': The trading pair with the minimum price.
     *               - 'diff': The difference between the max and min prices.
     *               - 'diffPercent': The percentage difference between the max and min prices.
     *               - 'others': An array of other trading pairs between the specified currencies.
     *
     * @throws Exception If no trading pairs are found.
     */
    public function getMaxMinPrices(string $firstCurrency, string $secondCurrency): array
    {
        $tradingPairs = $this->getTradingPairs($firstCurrency, $secondCurrency);

        if(count($tradingPairs) === 0) {
            throw new Exception('No pairs found');
        }

        if(count($tradingPairs) === 1) {
            return [
                'max' => $tradingPairs[0],
                'min' => null,
                'diff' => 0,
                'diffPercent' => 0,
                'others' => [],
            ];
        }

        $prices = array_map(fn($pair) => $pair->price, $tradingPairs);


        $maxIndex = array_search(max($prices), $prices);
        $minIndex = array_search(min($prices), $prices);


        $maxPair = $tradingPairs[$maxIndex];
        $minPair = $tradingPairs[$minIndex];

        if($maxPair->price > 0 && $minPair->price > 0) {
            $diff = $maxPair->price - $minPair->price;
            $diffPercent = ($maxPair->price - $minPair->price) / $minPair->price * 100;
        } else {
            $diff = 0;
            $diffPercent = 0;
        }


        $otherPairs = [];
        foreach ($tradingPairs as $key => $pair) {
            if ($key !== $maxIndex && $key !== $minIndex) {
                $otherPairs[] = $pair;
            }
        }

        return [
            'max' => $maxPair,
            'min' => $minPair,
            'diff' => number_format($diff, 2),
            'diffPercent' => number_format($diffPercent, 2) . '%',
            'others' => $otherPairs,
        ];
    }

    /**
     * Calculate the possible profit percentage by comparing trading pairs of a given currency pair.
     *
     * @param string $firstCurrency The base currency.
     * @param string $secondCurrency The quote currency.
     *
     * @return array An array containing details of potential profitable trades, including buy and sell exchanges, prices, and profit percentages.
     *
     * @throws Exception
     */
    public function getPossibleProfit(string $firstCurrency, string $secondCurrency): array
    {
        $tradingPairs = $this->getTradingPairs($firstCurrency, $secondCurrency);

        $groupedBySymbol = [];
        foreach ($tradingPairs as $pair) {
            $groupedBySymbol[$pair->symbol][] = $pair;
        }

        $result = [];

        foreach ($groupedBySymbol as $pairs) {
            $minPair = collect($pairs)->sortBy('price')->first();
            $maxPair = collect($pairs)->sortByDesc('price')->first();

            if ($minPair && $maxPair && $minPair->price < $maxPair->price) {
                $profitPercentage = (($maxPair->price - $minPair->price) / $minPair->price) * 100;

                $result[] = [
                    'min_exchange' => $minPair->exchange,
                    'max_exchange' => $maxPair->exchange,
                    'min_price' => $minPair->price,
                    'max_price' => $maxPair->price,
                    'profit' => round($profitPercentage, 2)
                ];
            }
        }

        return $result;
    }
}
