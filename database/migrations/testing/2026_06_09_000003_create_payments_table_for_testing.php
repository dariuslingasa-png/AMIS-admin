<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('enrollment_applicant_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('method')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('or_number')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('receipt_url')->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
