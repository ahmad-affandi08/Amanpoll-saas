<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PaketLangganan', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->string('Kode', 80);
            $table->string('Nama', 160);
            $table->text('Deskripsi')->nullable();
            $table->decimal('HargaBulanan', 20, 2)->default(0);
            $table->decimal('HargaTahunan', 20, 2)->default(0);
            $table->char('MataUang', 3)->default('IDR');
            $table->boolean('Aktif')->default(1);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['Kode'], 'UqPaketLanggananKode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PaketLangganan');
    }
};
