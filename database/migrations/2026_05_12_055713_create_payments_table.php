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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_applicant_id')->constrained()->onDelete('cascade');

            $table->enum('method', ['gcash', 'maya', 'bdo']);
            $table->decimal('amount', 10, 2)->default(4000.00);
            $table->string('receipt_url')->nullable();       // uploaded receipt path
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();             // admin notes

            $table->timestamp('paid_at')->nullable();        // when user submitted
            $table->timestamp('verified_at')->nullable();    // when admin verified
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
