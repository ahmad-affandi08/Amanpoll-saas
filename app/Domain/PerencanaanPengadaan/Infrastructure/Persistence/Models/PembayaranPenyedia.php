<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PembayaranPenyedia extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'PembayaranPenyedia';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'TagihanPenyediaId',
        'NomorPembayaran',
        'TanggalBayar',
        'Jumlah',
        'Metode',
        'Referensi',
        'DibuatOleh',
    ];

    protected function casts(): array
    {
        return [
            'TanggalBayar' => 'date',
            'Jumlah' => 'decimal:2',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function tagihanPenyedia(): BelongsTo
    {
        return $this->belongsTo(TagihanPenyedia::class, 'TagihanPenyediaId', 'Id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'DibuatOleh', 'Id');
    }
}
