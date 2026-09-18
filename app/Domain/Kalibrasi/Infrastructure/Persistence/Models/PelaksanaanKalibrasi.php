<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function rencanaKalibrasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi::class, 'RencanaKalibrasiId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

    public function jenisKalibrasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi::class, 'JenisKalibrasiId', 'Id');
    }

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia::class, 'PenyediaId', 'Id');
    }

    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    public function dilaksanakanOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DilaksanakanOleh', 'Id');
    }

    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DiverifikasiOleh', 'Id');
    }

}
