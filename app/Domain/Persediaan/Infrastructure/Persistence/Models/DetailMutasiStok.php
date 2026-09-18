<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DetailMutasiStok extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'DetailMutasiStok';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'MutasiStokId',
        'SukuCadangId',
        'KelompokSukuCadangId',
        'Jumlah',
        'HargaSatuan',
        'LokasiGudangAsalId',
        'LokasiGudangTujuanId',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'HargaSatuan' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function mutasiStok(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok::class, 'MutasiStokId', 'Id');
    }

    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang::class, 'SukuCadangId', 'Id');
    }

    public function kelompokSukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang::class, 'KelompokSukuCadangId', 'Id');
    }

    public function lokasiGudangAsal(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang::class, 'LokasiGudangAsalId', 'Id');
    }

    public function lokasiGudangTujuan(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang::class, 'LokasiGudangTujuanId', 'Id');
    }

}
