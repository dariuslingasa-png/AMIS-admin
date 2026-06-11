<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('advance_payment_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advance_payment_id')->nullable();
            $table->foreignId('target_invoice_id')->nullable();
            $table->decimal('amount_applied', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_payment_applications');
    }
};
