<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('tree-settings')
        ->assertStatus(200);
});
