<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('encrypted_files', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Opsional jika ada user
            $table->string('original_filename');
            $table->string('storage_path')->unique();
            $table->string('file_hash')->nullable();
            $table->json('metadata'); // iv, salt, tag, wrapped_key, mask, dll.
            $table->unsignedBigInteger('original_size_bytes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encrypted_files');
    }
};
