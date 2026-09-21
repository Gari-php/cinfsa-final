<?php

define('MP_ACCESS_TOKEN', $_ENV['MP_ACCESS_TOKEN'] ?? '');
define('MP_PUBLIC_KEY', $_ENV['MP_PUBLIC_KEY'] ?? '');

define('MP_SUCCESS_URL', $_ENV['MP_SUCCESS_URL'] ?? '');
define('MP_FAILURE_URL', $_ENV['MP_FAILURE_URL'] ?? '');
define('MP_PENDING_URL', $_ENV['MP_PENDING_URL'] ?? '');
define('MP_NOTIFICATION_URL', $_ENV['MP_NOTIFICATION_URL'] ?? '');

return [
    'access_token' => MP_ACCESS_TOKEN,
    'public_key' => MP_PUBLIC_KEY,
    'success_url' => MP_SUCCESS_URL,
    'failure_url' => MP_FAILURE_URL,
    'pending_url' => MP_PENDING_URL,
    'notification_url' => MP_NOTIFICATION_URL
];
