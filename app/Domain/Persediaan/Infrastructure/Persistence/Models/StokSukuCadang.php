<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StokSukuCadang extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'StokSukuCadang';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'GudangId',
        'LokasiGudangId',
        'SukuCadangId',
        'KelompokSukuCadangId',
        'JumlahTersedia',
        'JumlahDipesan',
        'JumlahDitahan',
        'Versi',
    ];

    protected function casts(): array
    {
        return [
            'JumlahTersedia' => 'decimal:4',
            'JumlahDipesan' => 'decimal:4',
            'JumlahDitahan' => 'decimal:4',
            'Versi' => 'integer',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang::class, 'GudangId', 'Id');
    }

    public function lokasiGudang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang::class, 'LokasiGudangId', 'Id');
    }

    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang::class, 'SukuCadangId', 'Id');
    }

    public function kelompokSukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang::class, 'KelompokSukuCadangId', 'Id');
    }

}
