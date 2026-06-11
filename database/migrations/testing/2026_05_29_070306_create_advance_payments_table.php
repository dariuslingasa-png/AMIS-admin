<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('advance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->unsignedBigInteger('family_application_id')->nullable()->index();
            $table->foreignId('source_payment_id')->nullable();
            $table->foreignId('source_invoice_id')->nullable();
            $table->string('or_number')->index();
            $table->decimal('initial_amount', 10, 2);
            $table->decimal('remaining_balance', 10, 2);
            $table->string('status')->default('available'); // 'available', 'partially_applied', 'fully_applied'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_payments');
    }
};
