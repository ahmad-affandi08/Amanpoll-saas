<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KodeKegagalan extends ModelDasar
{
    use MilikOrganisasi, PunyaKodeOtomatis;

    protected $table = 'KodeKegagalan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'KategoriAsetId',
        'Kode',
        'Nama',
        'Jenis',
        'Keterangan',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'KGL';
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<KategoriAset, $this> */
    public function kategoriAset(): BelongsTo
    {
        return $this->belongsTo(KategoriAset::class, 'KategoriAsetId', 'Id');
    }
}
