<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('FiturPaket', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 100);
            $table->string('Nama', 160);
            $table->text('Deskripsi')->nullable();
            $table->string('TipeBatas', 40)->default('Boolean');
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['Kode'], 'UqFiturPaketKode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('FiturPaket');
    }
};
