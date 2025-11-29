<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Hold;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StressInventory extends Command
{
    protected $signature = 'stress:inventory {--processes=30 : Number of concurrent requests}';
    protected $description = 'Simulate high-concurrency attacks on stock to prove atomicity';

    public function handle(): int
    {
        $this->info("⚡ Starting Concurrency Stress Test...");

        // 1. Setup Data (Reset DB)
        $this->warn("Resetting database state...");

        // Disable Foreign Keys to allow fast truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Hold::truncate();
        Product::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $product = Product::create([
            'name' => 'RTX 5090',
            'price' => 1999.00,
            'total_stock' => 10,
            'available_stock' => 10
        ]);

        $processes = (int) $this->option('processes');
        $this->info("🚀 Launching {$processes} parallel processes against Stock: 10");

        // 2. Execution (Using proc_open for raw parallelization)
        $running = [];
        // Helper command that attempts to buy 1 item
        $cmd = PHP_BINARY . " artisan test:concurrency {$product->id} 1";
        $cwd = base_path();

        $bar = $this->output->createProgressBar($processes);
        $bar->start();

        for ($i = 0; $i < $processes; $i++) {
            $process = proc_open($cmd, [
                // We mute stdout/stderr to keep the progress bar clean
                1 => ['file', (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'), 'w'],
                2 => ['file', (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'), 'w'],
            ], $pipes, $cwd);

            if (is_resource($process)) {
                $running[] = $process;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("⏳ Waiting for DB locks to resolve...");

        // Wait for all to finish
        foreach ($running as $process) {
            $status = proc_get_status($process);
            while ($status['running']) {
                usleep(10000); // 10ms wait
                $status = proc_get_status($process);
            }
            proc_close($process);
        }

        // 3. Verification
        $finalStock = $product->refresh()->available_stock;
        $totalHolds = Hold::count();

        $this->table(
            ['Metric', 'Expected', 'Actual', 'Result'],
            [
                ['Available Stock', '0', $finalStock, $finalStock === 0 ? '✅' : '❌'],
                ['Total Holds', '10', $totalHolds, $totalHolds === 10 ? '✅' : '❌'],
                ['Rejected Requests', $processes - 10, $processes - $totalHolds, ($processes - $totalHolds) === ($processes - 10) ? '✅' : '❌'],
            ]
        );

        if ($finalStock === 0 && $totalHolds === 10) {
            $this->info("✅ PASSED: No overselling occurred.");
            return 0;
        } else {
            $this->error("❌ FAILED: Race condition detected.");
            return 1;
        }
    }
}

