<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'role:superadmin'])
    ->name('dashboard');

Route::view('voters/dashboard', 'voters.dashboard')
    ->middleware(['auth', 'role:voters'])
    ->name('voters.dashboard');

Route::view('inec/dashboard', 'inec.dashboard')
    ->middleware(['auth', 'role:inecofficer'])
    ->name('inec.dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    
        
    Volt::route('superadmin/inec-officers/create', 'superadmin.create-inec-officer')
        ->middleware(['auth', 'permission:manage.inec.officers'])
        ->name('superadmin.inec.create');

    Volt::route('superadmin/roles-permissions', 'superadmin.roles-permissions')
        ->middleware(['auth', 'permission:manage.roles|manage.permissions|assign.permissions'])
        ->name('superadmin.roles.permissions');
});
