<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenawaranPenyedia extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PenawaranPenyedia';

    public $timestamps = false;

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

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function permintaanPenawaran(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran::class, 'PermintaanPenawaranId', 'Id');
    }

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia::class, 'PenyediaId', 'Id');
    }

}
