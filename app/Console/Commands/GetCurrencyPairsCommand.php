<?php

namespace App\Console\Commands;

use App\Services\CryptoExchangesService;
use Exception;
use Illuminate\Console\Command;

class GetCurrencyPairsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-currency-pairs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get currency pairs from crypto exchanges';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            $firstCurrency = $this->ask('Enter first currency');
            $secondCurrency = $this->ask('Enter second currency');

            $service = new CryptoExchangesService();

            $result = $service->getMaxMinPrices(trim($firstCurrency), trim($secondCurrency));


            $this->info("Max value:");
            $this->table(
                ['Exchange', 'Price', 'Trading pair'],
                [[$result['max']->exchange, '$'.$result['max']->price, $result['max']->symbol]]
            );

            if($result['min']) {
                $this->info("Min value:");
                $this->table(
                    ['Exchange', 'Price', 'Trading pair'],
                    [[$result['min']->exchange, '$' . $result['min']->price, $result['max']->symbol]]
                );
            }

            $otherPairs = [];
            foreach ($result['others'] as $pair) {
                $otherPairs[] = [$pair->exchange, '$'.$pair->price, $result['max']->symbol];
            }

            if( count($otherPairs) > 0) {
                $this->info("Other values:");
                $this->table(
                    ['Exchange', 'Price', 'Trading pair'],
                    $otherPairs
                );
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
