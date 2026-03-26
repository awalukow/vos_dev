<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('hash_code')->unique(); // For QR / verify URL
            $table->foreignId('uploaded_by')->constrained('portal_users');
            $table->enum('status', ['draft', 'pending', 'partially_signed', 'completed', 'rejected'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('portal_documents')->cascadeOnDelete();
            $table->foreignId('signer_id')->constrained('portal_users');
            $table->foreignId('assigned_by')->constrained('portal_users');
            $table->integer('sort_order')->default(0); // Signing order
            $table->enum('status', ['pending', 'signed', 'rejected'])->default('pending');
            $table->timestamp('signed_at')->nullable();
            $table->string('qr_page')->nullable();         // Which page the QR was placed on
            $table->float('qr_position_x')->nullable();   // X position (0-100 %)
            $table->float('qr_position_y')->nullable();   // Y position (0-100 %)
            $table->float('qr_size')->nullable();         // Size in points
            $table->string('signed_file_path')->nullable(); // PDF with QR embedded
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
        Schema::dropIfExists('portal_documents');
    }
};
