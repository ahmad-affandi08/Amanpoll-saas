<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenilaianPenyedia extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PenilaianPenyedia';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PenyediaId',
        'PeriodeMulai',
        'PeriodeSelesai',
        'SkorKualitas',
        'SkorKetepatanWaktu',
        'SkorHarga',
        'SkorLayanan',
        'SkorTotal',
        'Catatan',
        'DinilaiOleh',
    ];

    protected function casts(): array
    {
        return [
            'PeriodeMulai' => 'date',
            'PeriodeSelesai' => 'date',
            'SkorKualitas' => 'decimal:2',
            'SkorKetepatanWaktu' => 'decimal:2',
            'SkorHarga' => 'decimal:2',
            'SkorLayanan' => 'decimal:2',
            'SkorTotal' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function penyedia(): BelongsTo
    {
        return $this->belongsTo(Penyedia::class, 'PenyediaId', 'Id');
    }

    public function dinilaiOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DinilaiOleh', 'Id');
    }
}
