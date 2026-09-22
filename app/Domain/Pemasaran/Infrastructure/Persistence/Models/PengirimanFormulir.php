<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\HanyaTambah;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu pengiriman formulir, apa adanya (MARKETING.md 10, 27). */
final class PengirimanFormulir extends ModelDasar
{
    use HanyaTambah;

    protected $table = 'PengirimanFormulir';

    public $timestamps = false;

    protected $fillable = [
        'FormulirPemasaranId',
        'ProspekId',
        'PengenalPengunjung',
        'Data',
        'Persetujuan',
        'AlamatIp',
        'AgenPengguna',
        'DikirimPada',
    ];

    protected function casts(): array
    {
        return [
            'Data' => 'array',
            'Persetujuan' => 'boolean',
            'DikirimPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<FormulirPemasaran, $this> */
    public function formulir(): BelongsTo
    {
        return $this->belongsTo(FormulirPemasaran::class, 'FormulirPemasaranId', 'Id');
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }
}
