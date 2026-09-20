<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MutasiStok extends ModelDasar
{
    use MilikOrganisasi;

    public const JENIS_PENERIMAAN = 'Penerimaan';

    public const JENIS_PENGELUARAN = 'Pengeluaran';

    public const JENIS_TRANSFER = 'Transfer';

    public const JENIS_ADJUSTMENT = 'Adjustment';

    public const JENIS_RETURN = 'Return';

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_DIPOSTING = 'Diposting';

    public const STATUS_DIBATALKAN = 'Dibatalkan';

    protected $table = 'MutasiStok';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'Jenis',
        'GudangAsalId',
        'GudangTujuanId',
        'ReferensiJenis',
        'ReferensiId',
        'Tanggal',
        'Status',
        'Catatan',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'Tanggal' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
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
     * @return BelongsTo<Gudang, $this>
     */
    public function gudangAsal(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'GudangAsalId', 'Id');
    }

    /**
     * @return BelongsTo<Gudang, $this>
     */
    public function gudangTujuan(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'GudangTujuanId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
    }

    /**
     * @return HasMany<DetailMutasiStok, $this>
     */
    public function detailMutasiStok(): HasMany
    {
        return $this->hasMany(DetailMutasiStok::class, 'MutasiStokId', 'Id');
    }
}
