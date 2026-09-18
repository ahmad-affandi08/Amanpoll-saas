<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('EntitasTag', function (Blueprint $table): void {
            $table->char('Id', 26)->primary();
            $table->char('OrganisasiId', 26);
            $table->char('TagId', 26);
            $table->string('JenisEntitas', 80);
            $table->char('EntitasId', 26);
            $table->dateTime('DibuatPada', 6)->useCurrent();
            $table->unique(['TagId', 'JenisEntitas', 'EntitasId'], 'UqEntitasTag');
            $table->index(['OrganisasiId', 'JenisEntitas', 'EntitasId'], 'IdxEntitasTagEntitas');
            $table->foreign('OrganisasiId')->references('Id')->on('Organisasi');
            $table->foreign('TagId')->references('Id')->on('Tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('EntitasTag');
    }
};
