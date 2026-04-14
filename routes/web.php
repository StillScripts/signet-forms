<?php

use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\ExportDownloadController;
use App\Http\Controllers\QrCodeDownloadController;
use App\Livewire\PublicFormPage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('forms/{team:slug}/{formSlug}', PublicFormPage::class)->name('forms.show');

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', AcceptInvitationController::class)->name('invitations.accept');
    Route::get('forms/{form:id}/qr-code/{format}', QrCodeDownloadController::class)
        ->where('format', 'png|svg')
        ->name('forms.qr-code');

    Route::get('exports/{export}/download', ExportDownloadController::class)
        ->middleware('signed')
        ->name('exports.download');
});
