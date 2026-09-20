<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PesananPembelian extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PesananPembelian';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

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

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class, 'PenyediaId', 'Id');
    }

    public function permintaanPembelian(): BelongsTo
    {
        return $this->belongsTo(PermintaanPembelian::class, 'PermintaanPembelianId', 'Id');
    }

    public function penawaranPenyedia(): BelongsTo
    {
        return $this->belongsTo(PenawaranPenyedia::class, 'PenawaranPenyediaId', 'Id');
    }

    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'PosAnggaranId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
    }
}
