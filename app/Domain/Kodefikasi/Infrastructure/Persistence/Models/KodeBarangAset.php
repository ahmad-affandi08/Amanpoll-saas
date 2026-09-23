<?php

declare(strict_types=1);

namespace App\Domain\Kodefikasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kodefikasi\Domain\Enums\StandarKodefikasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penetapan kode barang pada satu aset, beserta NUP-nya.
 *
 * NUP (Nomor Urut Pendaftaran) berurut per kode barang, bukan per aset: dua
 * tempat tidur dengan kode barang yang sama bernomor 1 dan 2. Karena itu ia
 * dialokasikan saat penetapan, lalu tidak pernah berubah -- nomor yang sudah
 * dilaporkan tidak boleh bergeser.
 */
final class KodeBarangAset extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KodeBarangAset';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'AsetId',
        'KodeBarangId',
        'Standar',
        'Nup',
    ];

    protected function casts(): array
    {
        return [
            'Standar' => StandarKodefikasi::class,
            'Nup' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Aset, $this> */
    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'AsetId', 'Id');
    }

    /** @return BelongsTo<KodeBarang, $this> */
    public function kodeBarang(): BelongsTo
    {
        return $this->belongsTo(KodeBarang::class, 'KodeBarangId', 'Id');
    }
}
