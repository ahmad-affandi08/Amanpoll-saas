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

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<PerintahKerja, $this> */
    public function perintahKerja(): BelongsTo
    {
        return $this->belongsTo(PerintahKerja::class, 'PerintahKerjaId', 'Id');
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DiubahOleh', 'Id');
    }
}
