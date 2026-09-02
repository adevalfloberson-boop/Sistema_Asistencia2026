<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

test('asset urls use https behind a trusted proxy', function () {
    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'attendance.example',
            'HTTP_X_FORWARDED_HOST' => 'attendance.example',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])
        ->get('/login');

    $response
        ->assertOk()
        ->assertSee('https://attendance.example/build/assets/', escape: false);
});
