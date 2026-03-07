<?php

use Worldesports\MultiTenancy\MultiTenancy;

it('can instantiate the MultiTenancy class', function () {
    $multiTenancy = new MultiTenancy;
    expect($multiTenancy)->toBeInstanceOf(MultiTenancy::class);
});

it('can echo a phrase', function () {
    $multiTenancy = new MultiTenancy;
    $result = $multiTenancy->echoPhrase('Hello, World!');
    expect($result)->toBe('Hello, World!');
});

it('returns false for hasTenant when no tenant is set', function () {
    $multiTenancy = new MultiTenancy;
    expect($multiTenancy->hasTenant())->toBeFalse();
});

it('returns null for getTenant when no tenant is set', function () {
    $multiTenancy = new MultiTenancy;
    expect($multiTenancy->getTenant())->toBeNull();
});

it('returns null for getTenantId when no tenant is set', function () {
    $multiTenancy = new MultiTenancy;
    expect($multiTenancy->getTenantId())->toBeNull();
});

it('returns empty array for getTenantDatabases when no tenant is set', function () {
    $multiTenancy = new MultiTenancy;
    expect($multiTenancy->getTenantDatabases())->toBe([]);
});

it('can reset tenant context', function () {
    $multiTenancy = new MultiTenancy;
    $multiTenancy->resetTenant();
    expect($multiTenancy->hasTenant())->toBeFalse();
});
