<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->string('status', 32)->default('new')->index();
            $table->string('loss_reason', 64)->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->boolean('is_installment')->default(false);
            $table->date('next_payment_date')->nullable()->index();
            $table->boolean('is_overdue')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
