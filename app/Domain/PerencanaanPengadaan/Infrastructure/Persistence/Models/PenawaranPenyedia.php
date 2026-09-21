<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class PenawaranPenyedia extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PenawaranPenyedia';

    public $timestamps = false;

    public const STATUS_DIAJUKAN = 'Diajukan';

    public const STATUS_TERPILIH = 'Terpilih';

    public const STATUS_DITOLAK = 'Ditolak';

    protected $fillable = [
        'OrganisasiId',
        'PermintaanPenawaranId',
        'PenyediaId',
        'NomorPenawaran',
        'TanggalPenawaran',
        'BerlakuSampai',
        'MataUang',
        'Subtotal',
        'Pajak',
        'Diskon',
        'Total',
        'Status',
        'Catatan',
    ];

    protected function casts(): array
    {
        return [
            'TanggalPenawaran' => 'date',
            'BerlakuSampai' => 'date',
            'Subtotal' => 'decimal:2',
            'Pajak' => 'decimal:2',
            'Diskon' => 'decimal:2',
            'Total' => 'decimal:2',
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

    /**
     * @return HasMany<DetailPenawaranPenyedia, $this>
     */
    public function detail(): HasMany
    {
        return $this->hasMany(DetailPenawaranPenyedia::class, 'PenawaranPenyediaId', 'Id');
    }

    /**
     * @return HasOne<PesananPembelian, $this>
     */
    public function pesananPembelian(): HasOne
    {
        return $this->hasOne(PesananPembelian::class, 'PenawaranPenyediaId', 'Id');
    }
}
