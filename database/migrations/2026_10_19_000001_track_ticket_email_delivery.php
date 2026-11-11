<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::table('customers', function (Blueprint $t) {
            $t->timestamp('email_issue_at')->nullable();
            $t->string('pending_email',190)->nullable();
            $t->string('pending_email_otp_hash')->nullable();
            $t->timestamp('pending_email_otp_expires_at')->nullable();
            $t->timestamp('pending_email_otp_sent_at')->nullable();
            $t->unsignedTinyInteger('pending_email_otp_attempts')->default(0);
        });
        Schema::create('ticket_email_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained('customers');
            $t->foreignId('ticket_order_id')->nullable()->constrained('ticket_orders');
            $t->string('message_id')->unique();
            $t->string('recipient',190);
            $t->string('kind',30);
            $t->text('error_detail')->nullable();
            $t->timestamp('failed_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
            $t->index(['customer_id','failed_at']);
        });
    }
    public function down() {
        Schema::dropIfExists('ticket_email_deliveries');
        Schema::table('customers', fn(Blueprint $t)=>$t->dropColumn(['email_issue_at','pending_email','pending_email_otp_hash','pending_email_otp_expires_at','pending_email_otp_sent_at','pending_email_otp_attempts']));
    }
};
