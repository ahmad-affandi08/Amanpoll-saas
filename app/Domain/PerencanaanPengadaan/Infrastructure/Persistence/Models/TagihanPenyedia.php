<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TagihanPenyedia extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'TagihanPenyedia';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'PenyediaId',
        'PesananPembelianId',
        'NomorTagihan',
        'TanggalTagihan',
        'JatuhTempo',
        'Subtotal',
        'Pajak',
        'Total',
        'Sisa',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'TanggalTagihan' => 'date',
            'JatuhTempo' => 'date',
            'Subtotal' => 'decimal:2',
            'Pajak' => 'decimal:2',
            'Total' => 'decimal:2',
            'Sisa' => 'decimal:2',
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

    public function pesananPembelian(): BelongsTo
    {
        return $this->belongsTo(PesananPembelian::class, 'PesananPembelianId', 'Id');
    }
}
