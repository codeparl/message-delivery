<?php

declare(strict_types=1);

use SchoolPalm\MessageDelivery\Providers\InApp\Database\DatabaseNotificationProvider;

/*
|--------------------------------------------------------------------------
| Configuration Validation
|--------------------------------------------------------------------------
*/

it('database notification is configured by default', function (): void {
    $provider = new DatabaseNotificationProvider([]);
    expect($provider->configured())->toBeTrue();
});

it('database notification is configured with custom options', function (): void {
    $provider = new DatabaseNotificationProvider([
        'default_notifiable' => 'App\Models\Student',
    ]);
    expect($provider->configured())->toBeTrue();
});

it('database notification definition returns configuration fields', function (): void {
    $definition = \SchoolPalm\MessageDelivery\Providers\InApp\Database\DatabaseNotificationDefinition::make();

    $fields = $definition->configurationFields();

    expect($fields)->toBeArray();

    $fieldNames = array_map(fn($f) => $f->name(), $fields);

    expect($fieldNames)->toContain('default_notifiable');
});
