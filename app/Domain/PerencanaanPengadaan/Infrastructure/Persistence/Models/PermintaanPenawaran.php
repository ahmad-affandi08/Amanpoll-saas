<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PermintaanPenawaran extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PermintaanPenawaran';

    public $timestamps = false;

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

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function permintaanPembelian(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian::class, 'PermintaanPembelianId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DibuatOleh', 'Id');
    }

}
