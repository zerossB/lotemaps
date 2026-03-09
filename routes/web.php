<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('empreendimentos', 'pages::empreendimentos.index')->name('empreendimentos.index');
    Route::livewire('empreendimentos/{empreendimento}', 'pages::empreendimentos.show')->name('empreendimentos.show');

    Route::livewire('clients', 'pages::clients.index')->name('clients.index');
    Route::livewire('clients/{client}', 'pages::clients.show')->name('clients.show');

    Route::livewire('lots', 'pages::lots.index')->name('lots.index');
    Route::livewire('lots/{lot}', 'pages::lots.show')->name('lots.show');

    Route::livewire('proposals', 'pages::proposals.index')->name('proposals.index');
    Route::livewire('proposals/create', 'pages::proposals.create')->name('proposals.create');
    Route::livewire('proposals/{proposal}', 'pages::proposals.show')->name('proposals.show');
});

require __DIR__.'/settings.php';
