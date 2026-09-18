<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenyediaKategori', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('PenyediaId', 26);
            $table->char('KategoriPenyediaId', 26);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['PenyediaId', 'KategoriPenyediaId'], 'UqPenyediaKategori');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
            $table->foreign('KategoriPenyediaId')->references('Id')->on('KategoriPenyedia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenyediaKategori');
    }
};
