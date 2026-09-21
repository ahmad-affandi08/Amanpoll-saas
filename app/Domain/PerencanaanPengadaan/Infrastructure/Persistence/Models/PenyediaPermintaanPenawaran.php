<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenyediaPermintaanPenawaran extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PenyediaPermintaanPenawaran';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PermintaanPenawaranId',
        'PenyediaId',
        'DikirimPada',
        'DilihatPada',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'DikirimPada' => 'immutable_datetime',
            'DilihatPada' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Organisasi, $this>
     */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /**
     * @return BelongsTo<PermintaanPenawaran, $this>
     */
    public function permintaanPenawaran(): BelongsTo
    {
        return $this->belongsTo(PermintaanPenawaran::class, 'PermintaanPenawaranId', 'Id');
    }

    /**
     * @return BelongsTo<Penyedia, $this>
     */
    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class, 'PenyediaId', 'Id');
    }
}
