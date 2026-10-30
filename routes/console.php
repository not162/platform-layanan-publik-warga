<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Iuran warga: tandai tagihan yang lewat jatuh tempo sebagai OVERDUE setiap hari (setelah tengah malam WIB).
Schedule::command('app:mark-overdue-dues')->dailyAt('00:10')->withoutOverlapping();
