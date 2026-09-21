<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PermintaanPenawaran extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PermintaanPenawaran';

    public $timestamps = false;

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_DIBUKA = 'Dibuka';

    public const STATUS_DITUTUP = 'Ditutup';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'PermintaanPembelianId',
        'TanggalDibuka',
        'BatasPenawaran',
        'Status',
        'Catatan',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'TanggalDibuka' => 'immutable_datetime',
            'BatasPenawaran' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
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
     * @return BelongsTo<PermintaanPembelian, $this>
     */
    public function permintaanPembelian(): BelongsTo
    {
        return $this->belongsTo(PermintaanPembelian::class, 'PermintaanPembelianId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
    }

    /**
     * @return HasMany<PenyediaPermintaanPenawaran, $this>
     */
    public function penyediaDiundang(): HasMany
    {
        return $this->hasMany(PenyediaPermintaanPenawaran::class, 'PermintaanPenawaranId', 'Id');
    }

    /**
     * @return HasMany<PenawaranPenyedia, $this>
     */
    public function penawaran(): HasMany
    {
        return $this->hasMany(PenawaranPenyedia::class, 'PermintaanPenawaranId', 'Id');
    }
}
