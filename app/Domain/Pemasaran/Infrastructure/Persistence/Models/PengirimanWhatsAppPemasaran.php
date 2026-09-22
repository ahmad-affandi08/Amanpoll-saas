<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu pesan WhatsApp yang dijadwalkan atau sudah berangkat (MARKETING.md 16). */
final class PengirimanWhatsAppPemasaran extends ModelDasar
{
    protected $table = 'PengirimanWhatsAppPemasaran';

    public $timestamps = false;

    protected $fillable = [
        'ProspekId',
        'Nomor',
        'TemplateWhatsAppPemasaranId',
        'KunciIdempotensi',
        'Status',
        'IsiTeks',
        'IdPesanPenyedia',
        'Percobaan',
        'Galat',
        'JadwalPada',
        'DikirimPada',
        'DiperbaruiStatusPada',
    ];

    protected function casts(): array
    {
        return [
            'Status' => StatusPengirimanWhatsApp::class,
            'Percobaan' => 'integer',
            'JadwalPada' => 'immutable_datetime',
            'DikirimPada' => 'immutable_datetime',
            'DiperbaruiStatusPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Prospek, $this> */
    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class, 'ProspekId', 'Id');
    }

    /** @return BelongsTo<TemplateWhatsAppPemasaran, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateWhatsAppPemasaran::class, 'TemplateWhatsAppPemasaranId', 'Id');
    }
}
