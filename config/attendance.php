<?php

return [
    'api_token' => env('BIOMETRIC_API_TOKEN'),
    'device_offline_after_minutes' => (int) env('BIOMETRIC_OFFLINE_AFTER_MINUTES', 10),
    'device_delayed_after_minutes' => (int) env('BIOMETRIC_DELAYED_AFTER_MINUTES', 2),
];
