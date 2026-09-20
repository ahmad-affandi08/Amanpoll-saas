<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TitikUkurKalibrasi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'TitikUkurKalibrasi';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'JenisKalibrasiId',
        'KategoriAsetId',
        'Nama',
        'Satuan',
        'NilaiReferensi',
        'ToleransiMinus',
        'ToleransiPlus',
        'Urutan',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'NilaiReferensi' => 'decimal:8',
            'ToleransiMinus' => 'decimal:8',
            'ToleransiPlus' => 'decimal:8',
            'Urutan' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function jenisKalibrasi(): BelongsTo
    {
        return $this->belongsTo(JenisKalibrasi::class, 'JenisKalibrasiId', 'Id');
    }

    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(KategoriAset::class, 'KategoriAsetId', 'Id');
    }
}
