<?php

use Illuminate\Support\Facades\Schedule;

// Reset diário da demo. Requer o scheduler rodando (cron: * * * * * php artisan schedule:run).
Schedule::command('migrate:fresh', ['--seed' => true, '--force' => true])->dailyAt('04:00');
