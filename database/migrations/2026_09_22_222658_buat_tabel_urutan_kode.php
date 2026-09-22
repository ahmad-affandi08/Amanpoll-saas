<?php

declare(strict_types=1);

use App\Core\Penomoran\Services\LayananKodeOtomatis;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('UrutanKode', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            /*
             * Entitas milik platform tidak bertenant, jadi lingkupnya diisi penanda
             * dan bukan NULL: MySQL memperbolehkan NULL berulang pada indeks unik,
             * sehingga dua penghitung global bisa terbentuk dan menerbitkan kode kembar.
             * Karena itu pula kolom ini tidak diberi foreign key.
             */
            $table->char('OrganisasiId', 26)->default(LayananKodeOtomatis::PLATFORM);
            $table->string('Entitas', 80);
            $table->unsignedBigInteger('Terakhir')->default(0);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->dateTime('DiperbaruiPada', 6)->useCurrent()->useCurrentOnUpdate();
            $table->unique(['OrganisasiId', 'Entitas'], 'UqUrutanKode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('UrutanKode');
    }
};
