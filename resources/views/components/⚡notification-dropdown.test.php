<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('notification-dropdown')
        ->assertStatus(200);
});
