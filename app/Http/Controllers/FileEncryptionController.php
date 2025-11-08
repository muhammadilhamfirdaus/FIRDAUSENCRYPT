<?php

namespace App\Http\Controllers;

use App\Models\EncryptedFile;
use App\Services\EncryptionService; // <-- Import Service kita
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage; // <-- Import Storage

class FileEncryptionController extends Controller
{
    protected $encryptionService;

    // Kita 'inject' service kita di sini
    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
        // $this->middleware('auth'); // Nanti bisa ditambahkan jika sudah ada login
    }

    /**
     * Menampilkan form upload (UI)
     */
    public function create()
    {
        return view('files.create');
    }

    /**
     * Menyimpan dan mengenkripsi file (UI & API)
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:512000', // 500MB, sesuaikan PHP.ini (upload_max_filesize)
            'passphrase' => 'required|string|min:12', // Minimal 12 karakter
        ]);

        try {
            $fileRecord = $this->encryptionService->encryptFile(
                $request->file('file'),
                $request->input('passphrase')
            );

            // Logging Minimal
            Log::info('File Encrypted', [
                'file_id' => $fileRecord->id, 
                // 'user_id' => auth()->id()
            ]);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'File encrypted successfully',
                    'file' => $fileRecord,
                ], 201);
            }

            return redirect()->route('files.index')
                ->with('success', 'File encrypted successfully: ' . $fileRecord->original_filename);

        } catch (\Exception $e) {
            Log::error('Encryption Failed', ['errors' => $e->getMessage()]);
            throw ValidationException::withMessages([
                'file' => 'Encryption failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Menampilkan daftar file terenkripsi (UI)
     */
    public function index()
    {
        $files = EncryptedFile::latest()->paginate(20); // Ambil semua file
        return view('files.index', compact('files'));
    }
    
    /**
     * Form untuk dekripsi (UI)
     */
    public function showDecryptForm(EncryptedFile $file)
    {
        return view('files.decrypt', compact('file'));
    }

    /**
     * Mendekripsi dan men-download file (UI & API)
     */
    public function decryptAndDownload(Request $request, EncryptedFile $file)
    {
        $request->validate(['passphrase' => 'required|string']);

        try {
            $decryptedPayload = $this->encryptionService->decryptFile(
                $file,
                $request->input('passphrase')
            );

            // Logging Minimal
            Log::info('File Decrypted', [
                'file_id' => $file->id, 
                // 'user_id' => auth()->id(),
            ]);

            // Kirim file sebagai download
            return response()->streamDownload(function () use ($decryptedPayload) {
                echo $decryptedPayload;
            }, $file->original_filename);

        } catch (\Exception $e) {
            Log::warning('Decryption Failed', [
                'file_id' => $file->id, 
                // 'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            
            return back()->withErrors(['passphrase' => $e->getMessage()]);
        }
    }

    /**
     * (REVISI) Download file terenkripsi "mandiri" (self-contained)
     * Ini memanggil fungsi baru di service Anda.
     */
    public function downloadEncrypted(EncryptedFile $file)
    {
        try {
            // Panggil service baru untuk menggabungkan metadata + payload
            $fileContent = $this->encryptionService->getSelfContainedFileContent($file);

            $fileName = $file->original_filename . '.enc';

            // Kirim sebagai download
            return response()->streamDownload(function () use ($fileContent) {
                echo $fileContent;
            }, $fileName);

        } catch (\Exception $e) {
            Log::error('Failed to create self-contained file', ['error' => $e->getMessage()]);
            return back()->withErrors(['file' => 'Gagal membuat file download: ' . $e->getMessage()]);
        }
    }

    // --- FUNGSI-FUNGSI BARU UNTUK FITUR "UPLOAD & DECRYPT" ---

    /**
     * FUNGSI BARU:
     * Menampilkan form untuk upload file .enc yang akan didekripsi
     */
    public function showDecryptUploadForm()
    {
        return view('files.decrypt_upload'); // Nanti kita buat view ini
    }

    /**
     * FUNGSI BARU:
     * Menangani upload file .enc dan passphrase untuk dekripsi
     */
    public function handleDecryptUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file', // File .enc
            'passphrase' => 'required|string',
        ]);

        try {
            // Panggil service baru. Ini akan mengembalikan array
            $result = $this->encryptionService->decryptUploadedFile(
                $request->file('file'),
                $request->input('passphrase')
            );

            $decryptedPayload = $result['payload'];
            $originalFilename = $result['filename']; // Ambil nama file asli dari metadata

            return response()->streamDownload(function () use ($decryptedPayload) {
                echo $decryptedPayload;
            }, $originalFilename); // Gunakan nama file asli

        } catch (\Exception $e) {
            Log::warning('Decryption Upload Failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }
}

