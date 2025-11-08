<?php

namespace App\Services;

use App\Models\EncryptedFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class EncryptionService
{
    // Konstanta Kriptografi
    private const AES_METHOD = 'aes-256-gcm';
    private const PBKDF2_ALGO = 'sha256';
    private const PBKDF2_ITERATIONS = 500000; // Sesuaikan (lebih tinggi lebih aman)
    private const RSA_PADDING = OPENSSL_PKCS1_OAEP_PADDING;
    private const METADATA_SEPARATOR = "\n::METADATA_SEPARATOR::\n"; // Delimiter kita

    private $publicKey;
    private $privateKey;
    private $privateKeyPassphrase;

    public function __construct()
    {
        // Muat kunci saat service di-init
        $this->publicKey = Storage::disk('local')->get('keys/public.pem');
        $this->privateKey = Storage::disk('local')->get('keys/private.pem');
        // Ambil dari config/services.php
        $this->privateKeyPassphrase = config('services.rsa.private_key_passphrase');

        if (!$this->publicKey || !$this->privateKey) {
            throw new Exception("Kunci RSA publik/privat tidak ditemukan.");
        }
    }

    /**
     * Mengenkripsi file yang di-upload.
     * FUNGSI INI SENGAJA TIDAK DIUBAH
     * Menyimpan payload mentah ke storage, dan metadata ke DB.
     */
    public function encryptFile(UploadedFile $file, string $passphrase): EncryptedFile
    {
        // 1. Dapatkan konten file
        $payload = file_get_contents($file->getRealPath());
        $originalSize = $file->getSize();

        // 2. Generate Parameter Kriptografi
        $salt = random_bytes(16);
        $iv = random_bytes(openssl_cipher_iv_length(self::AES_METHOD));
        $mask = random_bytes(64); // Mask 64-byte untuk XOR

        // 3. Derivasi Kunci AES dari Passphrase (via PBKDF2)
        $aesKey = hash_pbkdf2(
            self::PBKDF2_ALGO,
            $passphrase,
            $salt,
            self::PBKDF2_ITERATIONS,
            32, // 32 bytes = 256 bits
            true // output raw bytes
        );

        // 4. Layer Enkripsi 1: Bit-level XOR
        $xorPayload = $this->performXor($payload, $mask);
        unset($payload);

        // 5. Layer Enkripsi 2: Symmetric (AES-256-GCM)
        $encryptedPayload = openssl_encrypt(
            $xorPayload,
            self::AES_METHOD,
            $aesKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        unset($xorPayload);

        if ($encryptedPayload === false) {
            throw new Exception("Enkripsi AES-GCM gagal.");
        }

        // 6. Layer Enkripsi 3: Asymmetric (RSA)
        $wrappedAesKey = null;
        if (!openssl_public_encrypt($aesKey, $wrappedAesKey, $this->publicKey, self::RSA_PADDING)) {
            throw new Exception("Gagal membungkus kunci AES dengan RSA: " . openssl_error_string());
        }

        // 7. Simpan file terenkripsi (HANYA PAYLOAD)
        $storagePath = 'encrypted_files/' . Str::uuid()->toString() . '.enc';
        Storage::disk('local')->put($storagePath, $encryptedPayload);

        // 8. Simpan metadata ke database
        $metadata = [
            'iv' => base64_encode($iv),
            'salt' => base64_encode($salt),
            'tag' => base64_encode($tag),
            'mask' => base64_encode($mask),
            'wrapped_key' => base64_encode($wrappedAesKey),
            'pbkdf2_iter' => self::PBKDF2_ITERATIONS,
            'aes_method' => self::AES_METHOD,
            // Kita tambahkan original_filename di sini agar bisa diambil nanti
            'original_filename' => $file->getClientOriginalName(), 
        ];

        return EncryptedFile::create([
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $storagePath,
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'metadata' => $metadata, // Metadata lengkap disimpan di DB
            'original_size_bytes' => $originalSize,
        ]);
    }

    /**
     * Mendekripsi file dari DB (fitur "List").
     * FUNGSI INI SENGAJA TIDAK DIUBAH
     */
    public function decryptFile(EncryptedFile $fileRecord, string $passphrase)
    {
        $metadata = $fileRecord->metadata;

        // 1. Muat data dari metadata
        try {
            $iv = base64_decode($metadata['iv']);
            $salt = base64_decode($metadata['salt']);
            $tag = base64_decode($metadata['tag']);
            $mask = base64_decode($metadata['mask']);
        } catch (Exception $e) {
            throw new Exception("Metadata korup atau tidak lengkap.");
        }

        // 2. Dapatkan Kunci AES (via PBKDF2)
        $aesKey = hash_pbkdf2(
            self::PBKDF2_ALGO,
            $passphrase,
            $salt,
            $metadata['pbkdf2_iter'],
            32,
            true
        );

        // 3. Muat file terenkripsi (Payload mentah)
        $encryptedPayload = Storage::disk('local')->get($fileRecord->storage_path);
        if ($encryptedPayload === null) {
            throw new Exception("File fisik tidak ditemukan di storage.");
        }

        // 4. Dekripsi Layer 2 (AES-GCM)
        $xorPayload = openssl_decrypt(
            $encryptedPayload,
            self::AES_METHOD,
            $aesKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        unset($encryptedPayload);

        if ($xorPayload === false) {
            throw new Exception("Dekripsi gagal. File korup atau passphrase salah (HMAC/Tag mismatch).");
        }

        // 5. Dekripsi Layer 1 (XOR)
        $originalPayload = $this->performXor($xorPayload, $mask);
        unset($xorPayload);

        return $originalPayload;
    }

    /**
     * Helper untuk operasi XOR.
     */
    private function performXor(string $data, string $mask): string
    {
        $dataLen = strlen($data);
        $maskLen = strlen($mask);
        if ($maskLen === 0) return $data;
        
        return $data ^ str_repeat($mask, (int)ceil($dataLen / $maskLen));
    }

    /**
     * Fungsi dekripsi menggunakan kunci privat RSA (untuk admin/escrow).
     */
    public function decryptFileWithPrivateKey(EncryptedFile $fileRecord): string
    {
        $metadata = $fileRecord->metadata;
        $iv = base64_decode($metadata['iv']);
        $tag = base64_decode($metadata['tag']);
        $mask = base64_decode($metadata['mask']);
        $wrappedKey = base64_decode($metadata['wrapped_key']);

        $aesKey = null;
        if (!openssl_private_decrypt(
            $wrappedKey, 
            $aesKey, 
            openssl_get_privatekey($this->privateKey, $this->privateKeyPassphrase), 
            self::RSA_PADDING
        )) {
            throw new Exception("Gagal mendekripsi Kunci AES. Passphrase kunci privat salah?");
        }

        $encryptedPayload = Storage::disk('local')->get($fileRecord->storage_path);

        $xorPayload = openssl_decrypt(
            $encryptedPayload,
            self::AES_METHOD,
            $aesKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($xorPayload === false) {
            throw new Exception("Dekripsi gagal (HMAC/Tag mismatch).");
        }

        return $this->performXor($xorPayload, $mask);
    }

    // -------------------------------------------------------------------
    // FUNGSI BARU UNTUK FITUR UPLOAD/DOWNLOAD "MANDIRI"
    // -------------------------------------------------------------------

    /**
     * FUNGSI BARU:
     * Menggabungkan metadata DB dan file payload mentah menjadi
     * satu string file yang "mandiri" (self-contained) untuk di-download.
     */
    public function getSelfContainedFileContent(EncryptedFile $fileRecord): string
    {
        // 1. Dapatkan metadata dari database
        $metadata = $fileRecord->metadata;
        if ($metadata === null) {
            throw new Exception("Metadata tidak ditemukan di database.");
        }
        
        // 2. Ubah metadata menjadi JSON header
        $jsonMetadata = json_encode($metadata);

        // 3. Muat file terenkripsi mentah (raw payload)
        $encryptedPayload = Storage::disk('local')->get($fileRecord->storage_path);
        if ($encryptedPayload === null) {
            throw new Exception("File fisik tidak ditemukan di storage.");
        }

        // 4. Gabungkan dan kembalikan sebagai satu string
        // [JSON_METADATA]\n::DELIMITER::\n[DATA_ENKRIPSI]
        return $jsonMetadata . self::METADATA_SEPARATOR . $encryptedPayload;
    }

    /**
     * FUNGSI BARU:
     * Mendekripsi file .enc yang di-upload langsung oleh user.
     * File ini harus berisi header metadata JSON.
     *
     * @param UploadedFile $file
     * @param string $passphrase
     * @return array ['payload' => string, 'filename' => string]
     */
    public function decryptUploadedFile(UploadedFile $file, string $passphrase): array
    {
        // 1. Baca konten file yang diupload
        $fullContent = file_get_contents($file->getRealPath());

        // 2. Pisahkan Metadata dan Payload
        $parts = explode(self::METADATA_SEPARATOR, $fullContent, 2);
        
        if (count($parts) !== 2) {
            throw new Exception("File format tidak valid atau korup. Tidak menemukan metadata header.");
        }

        $metadata = json_decode($parts[0], true);
        $encryptedPayload = $parts[1];

        if ($metadata === null) {
            throw new Exception("Metadata header korup (JSON tidak valid).");
        }

        // 3. Ekstrak data dari metadata
        $iv = base64_decode($metadata['iv']);
        $salt = base64_decode($metadata['salt']);
        $tag = base64_decode($metadata['tag']);
        $mask = base64_decode($metadata['mask']);
        $iterations = $metadata['pbkdf2_iter'];
        
        // Ambil nama file asli dari metadata header
        $originalFilename = $metadata['original_filename'] ?? 'decrypted_file.dat';

        // 4. Dapatkan Kunci AES (via PBKDF2)
        $aesKey = hash_pbkdf2(
            self::PBKDF2_ALGO,
            $passphrase,
            $salt,
            $iterations,
            32,
            true
        );

        // 5. Dekripsi Layer 2 (AES-GCM)
        $xorPayload = openssl_decrypt(
            $encryptedPayload,
            self::AES_METHOD,
            $aesKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($xorPayload === false) {
            throw new Exception("Dekripsi gagal. File korup atau passphrase salah (HMAC/Tag mismatch).");
        }

        // 6. Dekripsi Layer 1 (XOR)
        $originalPayload = $this->performXor($xorPayload, $mask);

        // Kembalikan payload DAN nama file aslinya
        return [
            'payload' => $originalPayload,
            'filename' => $originalFilename
        ];
    }
}
