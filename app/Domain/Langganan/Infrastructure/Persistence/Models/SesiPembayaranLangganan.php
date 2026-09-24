<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu percobaan bayar tagihan di payment gateway beserta order id-nya (PRD 8.23). */
final class SesiPembayaranLangganan extends ModelDasar
{
    use MilikOrganisasi;

    protected $table = 'SesiPembayaranLangganan';

    public $timestamps = false;

    protected $fillable = [
        'OrganisasiId',
        'TagihanLanggananId',
        'Penyedia',
        'IdPesananPenyedia',
        'ReferensiPenyedia',
        'UrlPembayaran',
        'Jumlah',
        'KedaluwarsaPada',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'Jumlah' => 'decimal:2',
            'KedaluwarsaPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<TagihanLangganan, $this> */
    public function tagihanLangganan(): BelongsTo
    {
        return $this->belongsTo(TagihanLangganan::class, 'TagihanLanggananId', 'Id');
    }
}
