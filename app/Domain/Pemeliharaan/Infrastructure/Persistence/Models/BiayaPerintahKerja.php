<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BiayaPerintahKerja extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'BiayaPerintahKerja';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PerintahKerjaId',
        'JenisBiaya',
        'Deskripsi',
        'Jumlah',
        'MataUang',
        'PenyediaId',
        'TanggalBiaya',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:2',
            'TanggalBiaya' => 'date',
            'DibuatPada' => 'immutable_datetime',
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

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia::class, 'PenyediaId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DibuatOleh', 'Id');
    }

}
