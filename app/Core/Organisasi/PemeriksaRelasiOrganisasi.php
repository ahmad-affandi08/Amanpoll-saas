<?php

declare(strict_types=1);

namespace App\Core\Organisasi;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menolak foreign key yang menunjuk ke baris milik organisasi lain (mis.
 * UnitOrganisasiId pada Lokasi menunjuk ke unit organisasi lain). Berbasis
 * metadata foreign key sungguhan di database, bukan relasi Eloquent, supaya
 * berlaku untuk seluruh kolom FK walau belum ada method relasi untuknya.
 */
final class PemeriksaRelasiOrganisasi
{
    /**
     * @var array<string, list<array{columns: list<string>, foreign_table: string, foreign_columns: list<string>}>>
     */
    private static array $cacheForeignKey = [];

    public static function pastikanSeorganisasi(Model $model): void
    {
        // Baca atribut mentah (bukan getAttribute()) karena kolom FK seperti
        // "DibuatOleh" ber-camelCase sama dengan method relasi "dibuatOleh()",
        // sehingga getAttribute() bisa salah resolve ke relasi, bukan kolom mentah.
        $atribut = $model->getAttributes();
        $organisasiId = $atribut['OrganisasiId'] ?? null;
        if (empty($organisasiId)) {
            return;
        }

        foreach (self::foreignKeys($model->getTable()) as $fk) {
            if (count($fk['columns']) !== 1) {
                continue;
            }

            $kolom = $fk['columns'][0];
            $nilai = $atribut[$kolom] ?? null;
            if ($nilai === null) {
                continue;
            }

            $tabelTujuan = $fk['foreign_table'];
            if (!Schema::hasColumn($tabelTujuan, 'OrganisasiId')) {
                continue;
            }

            $kolomTujuan = $fk['foreign_columns'][0] ?? 'Id';
            $baris = DB::table($tabelTujuan)->where($kolomTujuan, $nilai)->first(['OrganisasiId']);

            if ($baris && (string) $baris->OrganisasiId !== (string) $organisasiId) {
                throw new AturanBisnisDilanggar(
                    "Kolom {$kolom} tidak boleh menunjuk ke data milik organisasi lain.",
                );
            }
        }
    }

    /**
     * @return list<array{columns: list<string>, foreign_table: string, foreign_columns: list<string>}>
     */
    private static function foreignKeys(string $table): array
    {
        return self::$cacheForeignKey[$table] ??= array_values(Schema::getForeignKeys($table));
    }
}
