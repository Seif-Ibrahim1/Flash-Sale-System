<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Inventory\CreateHoldAction;
use Illuminate\Console\Command;
use Exception;

class TestConcurrency extends Command
{
    // The command we will run 30 times
    protected $signature = 'test:concurrency {product_id} {qty}';
    protected $description = 'Helper command for concurrency testing';

    public function handle(CreateHoldAction $action): int
    {
        $productId = $this->argument('product_id');
        $qty = (int) $this->argument('qty');

        try {
            $action->handle($productId, $qty);
            $this->info('SUCCESS');
            return 0;
        } catch (Exception $e) {
            // We print the actual error so we can debug if it fails
            $this->error('FAIL: ' . $e->getMessage());
            return 1;
        }
    }
}
