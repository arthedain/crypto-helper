<?php

namespace App\Console\Commands;

use App\Services\CryptoExchangesService;
use Exception;
use Illuminate\Console\Command;

class CalcProfitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calc-profit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate profit for all possible exchanges';

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

            $profitTable = $service->getPossibleProfit(trim($firstCurrency), trim($secondCurrency));

            if (!empty($profitTable)) {
                $this->table(
                    ['Buy Exchange', 'Sell Exchange', 'Buy Price', 'Sell Price', 'Profit %'],
                    $profitTable
                );
            } else {
                $this->info('No profitable opportunities found.');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
