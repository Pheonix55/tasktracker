<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::dashboard')->name('dashboard');
Route::livewire('/projects', 'pages::projects.index')->name('projects.index');
Route::livewire('/projects/{project}', 'pages::projects.show')->name('projects.show');
Route::livewire('/tags', 'pages::tags.index')->name('tags.index');
Route::livewire('/trash', 'pages::trash')->name('trash');
