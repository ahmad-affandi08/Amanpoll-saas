<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TagihanPenyedia extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'TagihanPenyedia';

    public $timestamps = false;

    public const STATUS_BELUM_DIBAYAR = 'BelumDibayar';

    public const STATUS_DIBAYAR_SEBAGIAN = 'DibayarSebagian';

    public const STATUS_DIBAYAR = 'Dibayar';

    protected $fillable = [
        'OrganisasiId',
        'PenyediaId',
        'PesananPembelianId',
        'NomorTagihan',
        'TanggalTagihan',
        'JatuhTempo',
        'Subtotal',
        'Pajak',
        'Total',
        'Sisa',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'TanggalTagihan' => 'date',
            'JatuhTempo' => 'date',
            'Subtotal' => 'decimal:2',
            'Pajak' => 'decimal:2',
            'Total' => 'decimal:2',
            'Sisa' => 'decimal:2',
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
     * @return BelongsTo<Penyedia, $this>
     */
    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class, 'PenyediaId', 'Id');
    }

    /**
     * @return BelongsTo<PesananPembelian, $this>
     */
    public function pesananPembelian(): BelongsTo
    {
        return $this->belongsTo(PesananPembelian::class, 'PesananPembelianId', 'Id');
    }

    /**
     * @return HasMany<PembayaranPenyedia, $this>
     */
    public function pembayaran(): HasMany
    {
        return $this->hasMany(PembayaranPenyedia::class, 'TagihanPenyediaId', 'Id');
    }
}
