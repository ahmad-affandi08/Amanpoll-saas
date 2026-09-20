<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TemplatNotifikasi extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'TemplatNotifikasi';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'Kode',
        'Kanal',
        'JudulTemplat',
        'IsiTemplat',
        'Variabel',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'Variabel' => 'array',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }
}
