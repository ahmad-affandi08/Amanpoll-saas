<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\EskalasiTingkatLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TingkatLayanan extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis;

    protected $table = 'TingkatLayanan';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    /** Senin sampai Jumat, dipakai bila daftar hari kerja kosong atau tak terbaca. */
    private const HARI_KERJA_BAWAAN = [1, 2, 3, 4, 5];

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Nama',
        'Deskripsi',
        'HariKerja',
        'JamKerjaMulai',
        'JamKerjaSelesai',
        'MemperhitungkanHariLibur',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'HariKerja' => 'array',
            'MemperhitungkanHariLibur' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'SLA';
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return HasMany<AturanTingkatLayanan, $this> */
    public function aturan(): HasMany
    {
        return $this->hasMany(AturanTingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

    /** @return HasMany<EskalasiTingkatLayanan, $this> */
    public function eskalasi(): HasMany
    {
        return $this->hasMany(EskalasiTingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

    /** @return HasMany<KategoriKeluhan, $this> */
    public function kategoriKeluhan(): HasMany
    {
        return $this->hasMany(KategoriKeluhan::class, 'TingkatLayananId', 'Id');
    }

    /**
     * Hari kerja sebagai bilangan 1 (Senin) sampai 7 (Minggu), tanpa duplikat dan terurut.
     *
     * Aturan `integer` menerima "1", sehingga hari yang masuk lewat API atau formulir biasa
     * dapat tersimpan sebagai teks. Perbandingan hari memakai `in_array(..., true)`, jadi
     * teks tidak pernah cocok dan perhitungan SLA berputar tanpa akhir mencari hari kerja.
     *
     * @return list<int>
     */
    public function hariKerjaTerbaca(): array
    {
        $hari = [];

        foreach ((array) $this->HariKerja as $satu) {
            $angka = match (true) {
                is_int($satu) => $satu,
                is_string($satu) && ctype_digit($satu) => (int) $satu,
                default => null,
            };

            if ($angka !== null && $angka >= 1 && $angka <= 7) {
                $hari[] = $angka;
            }
        }

        $hari = array_values(array_unique($hari));
        sort($hari);

        return $hari === [] ? self::HARI_KERJA_BAWAAN : $hari;
    }
}
