<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncryptedFile extends Model
{
    use HasFactory;
    protected $fillable = [
        'original_filename',
        'storage_path',
        'file_hash',
        'metadata',
        'original_size_bytes',
    ];
    protected $casts = [
        'metadata' => 'array',
        'original_size_bytes' => 'integer',
    ];
}