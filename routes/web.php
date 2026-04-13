<?php

use App\Http\Controllers\AcceptInvitationController;
use App\Livewire\PublicFormPage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('forms/{team:slug}/{formSlug}', PublicFormPage::class)->name('forms.show');

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', AcceptInvitationController::class)->name('invitations.accept');
});
