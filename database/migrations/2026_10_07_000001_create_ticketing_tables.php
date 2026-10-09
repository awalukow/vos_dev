<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->date('dob'); $t->string('email')->unique();
            $t->string('phone', 30); $t->string('password'); $t->boolean('is_active')->default(true);
            $t->timestamp('email_verified_at')->nullable(); $t->string('otp_hash')->nullable();
            $t->timestamp('otp_expires_at')->nullable(); $t->unsignedTinyInteger('otp_attempts')->default(0);
            $t->timestamp('otp_sent_at')->nullable(); $t->rememberToken(); $t->timestamps();
        });
        Schema::create('ticket_venues', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('address')->nullable(); $t->json('layout'); $t->timestamps();
        });
        Schema::create('ticket_events', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->text('description')->nullable();
            $t->timestamp('starts_at'); $t->string('location'); $t->string('thumbnail')->nullable();
            $t->string('seating_type', 20); $t->foreignId('ticket_venue_id')->nullable()->constrained();
            $t->boolean('limited_seating')->default(false); $t->boolean('published')->default(false); $t->timestamps();
        });
        Schema::create('ticket_classes', function (Blueprint $t) {
            $t->id(); $t->foreignId('ticket_event_id')->constrained()->cascadeOnDelete();
            $t->string('name'); $t->string('color', 7); $t->unsignedBigInteger('price');
            $t->unsignedInteger('capacity'); $t->timestamps(); $t->unique(['ticket_event_id', 'name']);
        });
        Schema::create('ticket_seats', function (Blueprint $t) {
            $t->id(); $t->foreignId('ticket_event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('ticket_class_id')->constrained(); $t->string('label', 40);
            $t->decimal('x', 6, 2); $t->unsignedInteger('y'); $t->timestamps();
            $t->unique(['ticket_event_id', 'label']);
        });
        Schema::create('ticket_payment_methods', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('type', 20); $t->text('instructions');
            $t->string('qr_image')->nullable(); $t->boolean('active')->default(false); $t->timestamps();
        });
        Schema::create('ticket_orders', function (Blueprint $t) {
            $t->id(); $t->uuid('reference')->unique(); $t->foreignId('customer_id')->constrained();
            $t->foreignId('ticket_event_id')->constrained(); $t->string('status', 30)->index();
            $t->unsignedBigInteger('total'); $t->timestamp('expires_at')->nullable()->index();
            $t->foreignId('ticket_payment_method_id')->nullable()->constrained();
            $t->json('payment_snapshot')->nullable(); $t->string('proof_path')->nullable();
            $t->timestamp('proof_uploaded_at')->nullable(); $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable(); $t->text('review_note')->nullable();
            $t->timestamp('tickets_emailed_at')->nullable(); $t->timestamps();
        });
        Schema::create('ticket_order_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('ticket_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('ticket_class_id')->constrained(); $t->foreignId('ticket_seat_id')->nullable()->constrained();
            $t->string('class_name'); $t->string('seat_label', 40)->nullable(); $t->unsignedBigInteger('price');
            $t->uuid('token')->unique(); $t->timestamps();
        });
        Schema::create('ticket_audit_logs', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('actor_id')->nullable(); $t->string('action');
            $t->string('subject'); $t->json('details')->nullable(); $t->timestamps();
        });
        DB::table('ticket_payment_methods')->insert([
            ['name'=>'Bank transfer', 'type'=>'transfer', 'instructions'=>'Configure your bank name, account number and account holder before enabling.', 'active'=>false, 'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'QRIS', 'type'=>'qris', 'instructions'=>'Upload your merchant QRIS image before enabling.', 'active'=>false, 'created_at'=>now(),'updated_at'=>now()],
        ]);
        if (Schema::hasTable('roles')) {
            DB::table('roles')->insertOrIgnore(['name'=>'ticket_operator', 'display_name'=>'Ticket Operator', 'level'=>30, 'description'=>'Review ticket payments', 'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void
    {
        foreach (['ticket_audit_logs','ticket_order_items','ticket_orders','ticket_payment_methods','ticket_seats','ticket_classes','ticket_events','ticket_venues','customers'] as $table) Schema::dropIfExists($table);
    }
};