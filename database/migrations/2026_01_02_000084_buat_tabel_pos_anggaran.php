<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PosAnggaran', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('AnggaranId', 26);
            $table->char('IndukId', 26)->nullable();
            $table->string('Kode', 80);
            $table->string('Nama', 180);
            $table->decimal('Jumlah', 20, 2)->default(0);
            $table->decimal('Terpakai', 20, 2)->default(0);
            $table->decimal('Ditahan', 20, 2)->default(0);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['AnggaranId', 'Kode'], 'UqPosAnggaran');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('AnggaranId')->references('Id')->on('Anggaran');
            $table->foreign('IndukId')->references('Id')->on('PosAnggaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PosAnggaran');
    }
};
