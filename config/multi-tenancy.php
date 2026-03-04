<?php

// config for Worldesports/MultiTenancy
return [
    'user_model' => \App\Models\User::class,
    'main_connection' => env('DB_CONNECTION', 'mysql'),
];
