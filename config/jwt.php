<?php

return [
    'access_token_ttl' => env('JWT_ACCESS_TOKEN_TTL', 3600),
    'refresh_token_ttl' => env('JWT_REFRESH_TOKEN_TTL', 604800),
];
