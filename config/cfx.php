<?php

return [
    'app_name' => env('CFX_APP_NAME', env('APP_NAME')),
    'client_id' => env('CFX_CLIENT_ID', base64_encode(env('APP_NAME'))),
    'client_secret' => null, /* Unused, required by Socialite */
    'redirect' => env('CFX_REDIRECT_URL'),
];
