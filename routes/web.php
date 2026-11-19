<?php

use Illuminate\Support\Facades\Route;

// Tutte le pagine sono gestite dalla single page app Vue
Route::view('/{any?}', 'app')->where('any', '^(?!api|sanctum|up).*$');
