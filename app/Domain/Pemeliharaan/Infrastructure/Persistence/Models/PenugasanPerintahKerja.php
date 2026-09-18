<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenugasanPerintahKerja extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PenugasanPerintahKerja';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PerintahKerjaId',
        'PenggunaId',
        'PeranTugas',
        'DitugaskanOleh',
        'DitugaskanPada',
        'DiterimaPada',
        'SelesaiPada',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'DitugaskanPada' => 'immutable_datetime',
            'DiterimaPada' => 'immutable_datetime',
            'SelesaiPada' => 'immutable_datetime',
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

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'PenggunaId', 'Id');
    }

    public function ditugaskanOleh(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna::class, 'DitugaskanOleh', 'Id');
    }

}
