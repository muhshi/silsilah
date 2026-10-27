<?php

test('privacy policy page returns 200 and has correct heading', function () {
    $response = $this->get(route('legal.privacy'));

    $response->assertOk();
    $response->assertSee('Kebijakan Privasi');
});

test('terms of service page returns 200 and has correct heading', function () {
    $response = $this->get(route('legal.terms'));

    $response->assertOk();
    $response->assertSee('Syarat & Ketentuan', false);
});

test('refund policy page returns 200 and has correct heading', function () {
    $response = $this->get(route('legal.refund'));

    $response->assertOk();
    $response->assertSee('Kebijakan Pengembalian Dana');
});

test('welcome page contains pricing section and footer legal links', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('10.000');
    $response->assertSee(route('legal.privacy'));
    $response->assertSee(route('legal.terms'));
    $response->assertSee(route('legal.refund'));
});
