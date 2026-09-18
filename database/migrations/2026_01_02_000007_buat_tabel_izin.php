<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Izin', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 120);
            $table->string('Nama', 160);
            $table->string('Modul', 100);
            $table->text('Keterangan')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['Kode'], 'UqIzinKode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Izin');
    }
};
