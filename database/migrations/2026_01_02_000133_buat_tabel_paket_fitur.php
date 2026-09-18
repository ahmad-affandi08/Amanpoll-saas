<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PaketFitur', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('PaketLanggananId', 26);
            $table->char('FiturPaketId', 26);
            $table->boolean('Diizinkan')->default(1);
            $table->decimal('BatasNilai', 20, 4)->nullable();
            $table->json('NilaiJson')->nullable();
            $table->unique(['PaketLanggananId', 'FiturPaketId'], 'UqPaketFitur');
            $table->foreign('PaketLanggananId')->references('Id')->on('PaketLangganan');
            $table->foreign('FiturPaketId')->references('Id')->on('FiturPaket');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PaketFitur');
    }
};
