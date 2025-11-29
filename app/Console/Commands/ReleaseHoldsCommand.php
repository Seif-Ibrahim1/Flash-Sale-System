<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Inventory\ReleaseExpiredHoldsAction;
use Illuminate\Console\Command;

class ReleaseHoldsCommand extends Command
{
    protected $signature = 'holds:release';
    protected $description = 'Release stock from expired holds';

    public function handle(ReleaseExpiredHoldsAction $action): void
    {
        $count = $action->handle();

        if ($count > 0) {
            $this->info("Released {$count} expired holds.");
        } else {
            $this->info("No expired holds found.");
        }
    }
}
