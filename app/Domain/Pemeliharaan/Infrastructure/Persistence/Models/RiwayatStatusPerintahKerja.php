<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RiwayatStatusPerintahKerja extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'RiwayatStatusPerintahKerja';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PerintahKerjaId',
        'StatusSebelum',
        'StatusSesudah',
        'Catatan',
        'DiubahOleh',
        'DiubahPada',
    ];

    protected function casts(): array
    {
        return [
            'DiubahPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DiubahOleh', 'Id');
    }
}
