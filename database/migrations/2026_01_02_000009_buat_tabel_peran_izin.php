<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PeranIzin', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('PeranId', 26);
            $table->char('IzinId', 26);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['PeranId', 'IzinId'], 'UqPeranIzin');
            $table->foreign('PeranId')->references('Id')->on('Peran');
            $table->foreign('IzinId')->references('Id')->on('Izin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PeranIzin');
    }
};
