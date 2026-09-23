<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Aset', function (Blueprint $table): void {
            // Nomenklatur standar Kemenkes untuk satu unit alat. Melengkapi
            // PemetaanAspak yang berlaku per kategori atau model: sebagian aset
            // menyimpang dari modelnya, dan sebagian tidak punya model sama sekali.
            $table->char('AlkesAspakId', 26)->nullable()->after('ModelAsetId');
            $table->index(['OrganisasiId', 'AlkesAspakId'], 'IdxAsetAlkesAspak');
            $table->foreign('AlkesAspakId')->references('Id')->on('AlkesAspak');
        });
    }

    public function down(): void
    {
        Schema::table('Aset', function (Blueprint $table): void {
            $table->dropForeign(['AlkesAspakId']);
            $table->dropIndex('IdxAsetAlkesAspak');
            $table->dropColumn('AlkesAspakId');
        });
    }
};
