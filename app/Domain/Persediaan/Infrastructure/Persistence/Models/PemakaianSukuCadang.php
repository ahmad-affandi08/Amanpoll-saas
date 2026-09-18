<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PemakaianSukuCadang extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PemakaianSukuCadang';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PerintahKerjaId',
        'SukuCadangId',
        'GudangId',
        'KelompokSukuCadangId',
        'Jumlah',
        'HargaSatuan',
        'MutasiStokId',
        'DipakaiOleh',
        'DipakaiPada',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:4',
            'HargaSatuan' => 'decimal:2',
            'DipakaiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    public function sukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang::class, 'SukuCadangId', 'Id');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang::class, 'GudangId', 'Id');
    }

    public function kelompokSukuCadang(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang::class, 'KelompokSukuCadangId', 'Id');
    }

    public function mutasiStok(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok::class, 'MutasiStokId', 'Id');
    }

    public function dipakaiOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DipakaiOleh', 'Id');
    }

}
