<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PenilaianPenyedia', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PenyediaId', 26);
            $table->date('PeriodeMulai');
            $table->date('PeriodeSelesai');
            $table->decimal('SkorKualitas', 5, 2)->nullable();
            $table->decimal('SkorKetepatanWaktu', 5, 2)->nullable();
            $table->decimal('SkorHarga', 5, 2)->nullable();
            $table->decimal('SkorLayanan', 5, 2)->nullable();
            $table->decimal('SkorTotal', 5, 2)->nullable();
            $table->text('Catatan')->nullable();
            $table->char('DinilaiOleh', 26)->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->index(['PenyediaId', 'PeriodeMulai', 'PeriodeSelesai'], 'IdxPenilaianPenyedia');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PenyediaId')->references('Id')->on('Penyedia');
            $table->foreign('DinilaiOleh')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PenilaianPenyedia');
    }
};
