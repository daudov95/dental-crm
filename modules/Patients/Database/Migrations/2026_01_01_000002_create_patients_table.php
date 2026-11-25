<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name', 255)->index();
            $table->string('phone', 32)->unique();
            $table->date('birth_date')->nullable();
            $table->string('source', 32)->default('phone')->index();
            $table->text('notes')->nullable();
            $table->boolean('consent_given')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
