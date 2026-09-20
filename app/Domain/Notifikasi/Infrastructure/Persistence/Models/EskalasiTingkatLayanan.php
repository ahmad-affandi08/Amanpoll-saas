<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EskalasiTingkatLayanan extends ModelDasar
{
    use MilikOrganisasi;

    public const PEMICU_MENJELANG = 'Menjelang';

    public const PEMICU_TERLEWATI = 'Terlewati';

    protected $table = 'EskalasiTingkatLayanan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'TingkatLayananId',
        'Tahap',
        'Pemicu',
        'SetelahMenit',
        'PeranId',
        'PenggunaId',
        'Kanal',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Tahap' => 'integer',
            'SetelahMenit' => 'integer',
            'Kanal' => 'array',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function tingkatLayanan(): BelongsTo
    {
        return $this->belongsTo(TingkatLayanan::class, 'TingkatLayananId', 'Id');
    }

    public function peran(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'PeranId', 'Id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'PenggunaId', 'Id');
    }
}
