<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaketFitur extends ModelDasar
{
    protected $table = 'PaketFitur';

    public $timestamps = false;

    protected $fillable = [
        'PaketLanggananId',
        'FiturPaketId',
        'Diizinkan',
        'BatasNilai',
        'NilaiJson',
    ];

    protected function casts(): array
    {
        return [
            'Diizinkan' => 'boolean',
            'BatasNilai' => 'decimal:4',
            'NilaiJson' => 'array',
        ];
    }

    public function paketLangganan(): BelongsTo
    {
        return $this->belongsTo(PaketLangganan::class, 'PaketLanggananId', 'Id');
    }

    public function fiturPaket(): BelongsTo
    {
        return $this->belongsTo(FiturPaket::class, 'FiturPaketId', 'Id');
    }
}
