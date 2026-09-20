<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AnalisisKegagalan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'AnalisisKegagalan';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'OrganisasiId',
        'PerintahKerjaId',
        'KodeMasalahId',
        'KodePenyebabId',
        'KodeTindakanId',
        'AkarMasalah',
        'TindakanKorektif',
        'TindakanPencegahan',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
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

    public function kodeMasalah(): BelongsTo
    {
        return $this->belongsTo(KodeKegagalan::class, 'KodeMasalahId', 'Id');
    }

    public function kodePenyebab(): BelongsTo
    {
        return $this->belongsTo(KodeKegagalan::class, 'KodePenyebabId', 'Id');
    }

    public function kodeTindakan(): BelongsTo
    {
        return $this->belongsTo(KodeKegagalan::class, 'KodeTindakanId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
    }
}
