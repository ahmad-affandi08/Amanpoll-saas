<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Naskah WhatsApp beserta status persetujuan penyedianya (MARKETING.md 16). */
final class TemplateWhatsAppPemasaran extends ModelDasar
{
    use PunyaKodeOtomatis;

    protected $table = 'TemplateWhatsAppPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Kode',
        'Nama',
        'Bahasa',
        'Kategori',
        'IsiTeks',
        'StatusPersetujuan',
        'IdTemplatePenyedia',
        'AlasanPenolakan',
        'DiperiksaPada',
        'Aktif',
    ];

    protected function casts(): array
    {
        return [
            'StatusPersetujuan' => StatusPersetujuanTemplateWa::class,
            'Aktif' => 'boolean',
            'DiperiksaPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'TWA';
    }

    /** Dua syarat yang berbeda: penyedia menyetujuinya, dan kami belum menonaktifkannya. */
    public function siapKirim(): bool
    {
        return $this->Aktif && $this->StatusPersetujuan->bolehDikirim();
    }

    /** @return HasMany<PengirimanWhatsAppPemasaran, $this> */
    public function pengiriman(): HasMany
    {
        return $this->hasMany(PengirimanWhatsAppPemasaran::class, 'TemplateWhatsAppPemasaranId', 'Id');
    }
}
