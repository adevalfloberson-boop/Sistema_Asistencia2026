<?php

return [
    'api_token' => env('BIOMETRIC_API_TOKEN'),
    'device_offline_after_minutes' => (int) env('BIOMETRIC_OFFLINE_AFTER_MINUTES', 10),
    'device_delayed_after_minutes' => (int) env('BIOMETRIC_DELAYED_AFTER_MINUTES', 2),
    'default_school_code' => env('BIOMETRIC_DEFAULT_SCHOOL_CODE', 'INST001'),
    'default_school_name' => env('BIOMETRIC_DEFAULT_SCHOOL_NAME', 'Centro Educativo INST001'),
    'reader' => [
        'key' => env('BIOMETRIC_READER_KEY', 'lector-1'),
        'name' => env('BIOMETRIC_READER_NAME', 'Lector principal'),
        'mac' => env('BIOMETRIC_READER_MAC', '00:17:61:11:18:e3'),
        'network' => env('BIOMETRIC_READER_NETWORK', '192.168.100'),
        'ip' => env('BIOMETRIC_READER_IP'),
        'port' => (int) env('BIOMETRIC_READER_PORT', 4370),
        'password' => env('BIOMETRIC_READER_PASSWORD', '0'),
    ],
];
