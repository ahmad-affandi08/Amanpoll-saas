<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Keluhan extends ModelDasar
{
    use SoftDeletes, MilikOrganisasi;

    protected $table = 'Keluhan';

    public const CREATED_AT = 'DibuatPada';
    public const UPDATED_AT = 'DiperbaruiPada';
    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'KategoriKeluhanId',
        'AsetId',
        'LokasiId',
        'Judul',
        'Deskripsi',
        'Prioritas',
        'Status',
        'Sumber',
        'PelaporId',
        'NamaPelaporEksternal',
        'KontakPelaporEksternal',
        'DilaporkanPada',
        'DiresponsPada',
        'DitutupPada',
        'Rating',
        'Ulasan',
        'Versi',
    ];

    protected function casts(): array
    {
        return [
            'DilaporkanPada' => 'immutable_datetime',
            'DiresponsPada' => 'immutable_datetime',
            'DitutupPada' => 'immutable_datetime',
            'Versi' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function kategoriKeluhan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan::class, 'KategoriKeluhanId', 'Id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Aset\Infrastructure\Persistence\Models\Aset::class, 'AsetId', 'Id');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi::class, 'LokasiId', 'Id');
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'PelaporId', 'Id');
    }

}
