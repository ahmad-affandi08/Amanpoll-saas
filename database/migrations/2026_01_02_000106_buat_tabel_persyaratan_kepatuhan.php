<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PersyaratanKepatuhan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26)->nullable();
            $table->char('StandarKepatuhanId', 26);
            $table->string('Kode', 100);
            $table->string('Nama', 220);
            $table->text('Deskripsi')->nullable();
            $table->text('BuktiYangDiperlukan')->nullable();
            $table->unsignedInteger('IntervalHari')->nullable();
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['StandarKepatuhanId', 'Kode'], 'UqPersyaratanKepatuhan');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('StandarKepatuhanId')->references('Id')->on('StandarKepatuhan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PersyaratanKepatuhan');
    }
};
