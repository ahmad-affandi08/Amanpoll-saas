<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PelaksanaanKalibrasi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PelaksanaanKalibrasi';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'RencanaKalibrasiId',
        'AsetId',
        'JenisKalibrasiId',
        'PenyediaId',
        'PerintahKerjaId',
        'TanggalKalibrasi',
        'TanggalBerlakuSampai',
        'Hasil',
        'NomorSertifikat',
        'Laboratorium',
        'KondisiLingkungan',
        'Catatan',
        'DilaksanakanOleh',
        'DiverifikasiOleh',
        'DiverifikasiPada',
    ];

    protected function casts(): array
    {
        return [
            'TanggalKalibrasi' => 'date',
            'TanggalBerlakuSampai' => 'date',
            'KondisiLingkungan' => 'array',
            'DiverifikasiPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<RencanaKalibrasi, $this> */
    public function rencanaKalibrasi(): BelongsTo
    {
        return $this->belongsTo(RencanaKalibrasi::class, 'RencanaKalibrasiId', 'Id');
    }

    /** @return BelongsTo<Aset, $this> */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    /** @return BelongsTo<JenisKalibrasi, $this> */
    public function jenisKalibrasi(): BelongsTo
    {
        return $this->belongsTo(JenisKalibrasi::class, 'JenisKalibrasiId', 'Id');
    }

    /** @return BelongsTo<Penyedia, $this> */
    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class, 'PenyediaId', 'Id');
    }

    /** @return BelongsTo<PerintahKerja, $this> */
    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function dilaksanakanOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DilaksanakanOleh', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DiverifikasiOleh', 'Id');
    }

    /** @return HasMany<HasilTitikUkurKalibrasi, $this> */
    public function hasilTitikUkur(): HasMany
    {
        return $this->hasMany(HasilTitikUkurKalibrasi::class, 'PelaksanaanKalibrasiId', 'Id');
    }
}
