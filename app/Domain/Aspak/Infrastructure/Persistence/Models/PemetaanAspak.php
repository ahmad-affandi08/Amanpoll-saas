<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jembatan antara taksonomi aset kita dan kode alkes ASPAK.
 *
 * Dipetakan pada model aset bila alatnya spesifik, atau pada kategori bila
 * seluruh kategori itu dilaporkan sebagai satu kode. Pemetaan model menang
 * atas pemetaan kategori.
 */
final class PemetaanAspak extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PemetaanAspak';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'AlkesAspakId',
        'KategoriAsetId',
        'ModelAsetId',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<AlkesAspak, $this> */
    public function alkes(): BelongsTo
    {
        return $this->belongsTo(AlkesAspak::class, 'AlkesAspakId', 'Id');
    }

    /** @return BelongsTo<KategoriAset, $this> */
    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(KategoriAset::class, 'KategoriAsetId', 'Id');
    }

    /** @return BelongsTo<ModelAset, $this> */
    public function modelAset(): BelongsTo
    {
        return $this->belongsTo(ModelAset::class, 'ModelAsetId', 'Id');
    }
}
