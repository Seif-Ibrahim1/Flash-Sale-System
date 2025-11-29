<?php

use App\Console\Commands\ReleaseHoldsCommand;
use Illuminate\Support\Facades\Schedule;

// Run every minute to clean up expired reservations
Schedule::command(ReleaseHoldsCommand::class)->everyMinute();
