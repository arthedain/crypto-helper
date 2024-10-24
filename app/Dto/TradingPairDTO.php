<?php

namespace App\Dto;

/**
 * Class TradingPairDTO
 *
 * Represents a trading pair and its associated data.
 *
 * @package App\DTO
 */

class TradingPairDTO
{
    public function __construct(
        public string $exchange,
        public string $symbol,
        public float $price,
    )
    {
    }
}
