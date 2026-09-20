<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class KomponenDasbor extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'KomponenDasbor';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'DasborTersimpanId',
        'JenisKomponen',
        'Judul',
        'Konfigurasi',
        'PosisiX',
        'PosisiY',
        'Lebar',
        'Tinggi',
        'Urutan',
    ];

    protected function casts(): array
    {
        return [
            'Konfigurasi' => 'array',
            'PosisiX' => 'integer',
            'PosisiY' => 'integer',
            'Lebar' => 'integer',
            'Tinggi' => 'integer',
            'Urutan' => 'integer',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function dasborTersimpan(): BelongsTo
    {
        return $this->belongsTo(DasborTersimpan::class, 'DasborTersimpanId', 'Id');
    }
}
