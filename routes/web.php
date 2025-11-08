<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileEncryptionController; // Import Controller kita

// Jika orang buka halaman utama ('/'), arahkan (redirect) ke daftar file
Route::get('/', function () {
    return redirect()->route('files.index');
});

// --- Rute Asli (dari daftar DB) ---
Route::get('files', [FileEncryptionController::class, 'index'])->name('files.index');
Route::get('files/upload', [FileEncryptionController::class, 'create'])->name('files.create');
Route::post('files/upload', [FileEncryptionController::class, 'store'])->name('files.store');
Route::get('files/{file}/decrypt', [FileEncryptionController::class, 'showDecryptForm'])->name('files.decrypt.form');
Route::post('files/{file}/decrypt', [FileEncryptionController::class, 'decryptAndDownload'])->name('files.decrypt.download');

// --- Rute untuk File .ENC Mandiri (Fitur Baru) ---

// 1. Download file .enc (gabungan metadata + payload)
Route::get('files/{file}/download-encrypted', [FileEncryptionController::class, 'downloadEncrypted'])->name('files.download.encrypted');

// 2. Tampilkan form untuk upload file .enc
Route::get('decrypt/upload', [FileEncryptionController::class, 'showDecryptUploadForm'])->name('files.decrypt.upload_form');

// 3. Proses file .enc yang di-upload
Route::post('decrypt/upload', [FileEncryptionController::class, 'handleDecryptUpload'])->name('files.decrypt.handle_upload');
