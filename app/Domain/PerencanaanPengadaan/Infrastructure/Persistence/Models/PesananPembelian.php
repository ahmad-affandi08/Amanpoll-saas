<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PesananPembelian extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PesananPembelian';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_MENUNGGU_PERSETUJUAN = 'MenungguPersetujuan';

    public const STATUS_DISETUJUI = 'Disetujui';

    public const STATUS_DITOLAK = 'Ditolak';

    public const STATUS_DIKIRIM = 'Dikirim';

    public const STATUS_DITERIMA_SEBAGIAN = 'DiterimaSebagian';

    public const STATUS_DITERIMA_PENUH = 'DiterimaPenuh';

    protected $fillable = [
        'OrganisasiId',
        'Nomor',
        'PenyediaId',
        'PermintaanPembelianId',
        'PenawaranPenyediaId',
        'PosAnggaranId',
        'TanggalPesanan',
        'TanggalKirimRencana',
        'MataUang',
        'Subtotal',
        'Pajak',
        'Diskon',
        'Total',
        'Status',
        'Catatan',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'TanggalPesanan' => 'date',
            'TanggalKirimRencana' => 'date',
            'Subtotal' => 'decimal:2',
            'Pajak' => 'decimal:2',
            'Diskon' => 'decimal:2',
            'Total' => 'decimal:2',
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
     * @return BelongsTo<Penyedia, $this>
     */
    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class, 'PenyediaId', 'Id');
    }

    /**
     * @return BelongsTo<PermintaanPembelian, $this>
     */
    public function permintaanPembelian(): BelongsTo
    {
        return $this->belongsTo(PermintaanPembelian::class, 'PermintaanPembelianId', 'Id');
    }

    /**
     * @return BelongsTo<PenawaranPenyedia, $this>
     */
    public function penawaranPenyedia(): BelongsTo
    {
        return $this->belongsTo(PenawaranPenyedia::class, 'PenawaranPenyediaId', 'Id');
    }

    /**
     * @return BelongsTo<PosAnggaran, $this>
     */
    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'PosAnggaranId', 'Id');
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
    }

    /**
     * @return HasMany<DetailPesananPembelian, $this>
     */
    public function detail(): HasMany
    {
        return $this->hasMany(DetailPesananPembelian::class, 'PesananPembelianId', 'Id');
    }

    /**
     * @return HasMany<PenerimaanPembelian, $this>
     */
    public function penerimaan(): HasMany
    {
        return $this->hasMany(PenerimaanPembelian::class, 'PesananPembelianId', 'Id');
    }

    /**
     * @return HasMany<TagihanPenyedia, $this>
     */
    public function tagihan(): HasMany
    {
        return $this->hasMany(TagihanPenyedia::class, 'PesananPembelianId', 'Id');
    }
}
