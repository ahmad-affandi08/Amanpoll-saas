<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('KeputusanPersetujuan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('PermintaanPersetujuanId', 26);
            $table->char('TahapPersetujuanId', 26);
            $table->char('PenyetujuId', 26);
            $table->string('Keputusan', 40);
            $table->text('Catatan')->nullable();
            $table->dateTime('DiputuskanPada', 6)->useCurrent();
            $table->unique(['PermintaanPersetujuanId', 'TahapPersetujuanId', 'PenyetujuId'], 'UqKeputusanPersetujuan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('PermintaanPersetujuanId')->references('Id')->on('PermintaanPersetujuan');
            $table->foreign('TahapPersetujuanId')->references('Id')->on('TahapPersetujuan');
            $table->foreign('PenyetujuId')->references('Id')->on('Pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('KeputusanPersetujuan');
    }
};
