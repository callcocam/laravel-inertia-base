<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('application locale is pt_BR with en fallback', function () {
    expect(app()->getLocale())->toBe('pt_BR')
        ->and(config('app.fallback_locale'))->toBe('en');
});

test('app group translations are merged from the lang/pt_BR/app directory', function () {
    expect(__('app.settings.profile.updated'))
        ->not->toBe('app.settings.profile.updated')
        ->and(trans('app'))->toBeArray()->not->toBeEmpty();
});

test('translations and locale are shared with inertia pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', 'pt_BR')
            ->has('translations.app')
            ->has('translations.validation')
        );
});

test('user primary keys are ulids', function () {
    $user = User::factory()->create();

    expect($user->id)->toBeString()->toHaveLength(26);
});
