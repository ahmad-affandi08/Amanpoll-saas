<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\ModelDasar;

/** Satu butir menu percakapan WhatsApp beserta balasannya (MARKETING.md 16). */
final class MenuWhatsAppPemasaran extends ModelDasar
{
    protected $table = 'MenuWhatsAppPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = ['Kunci', 'Urutan', 'Label', 'Balasan', 'Aktif'];

    protected function casts(): array
    {
        return [
            'Urutan' => 'integer',
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }
}
