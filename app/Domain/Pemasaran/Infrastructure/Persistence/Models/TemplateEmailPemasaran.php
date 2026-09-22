<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Domain\Pemasaran\Domain\Enums\JenisTemplateEmail;
use App\Shared\Infrastructure\Persistence\ModelDasar;

/** Template email pemasaran (MARKETING.md 15). */
final class TemplateEmailPemasaran extends ModelDasar
{
    use PunyaKodeOtomatis;

    protected $table = 'TemplateEmailPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = ['Kode', 'Nama', 'Jenis', 'Subjek', 'IsiHtml', 'IsiTeks', 'Aktif'];

    protected function casts(): array
    {
        return [
            'Jenis' => JenisTemplateEmail::class,
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'TEM';
    }

    public function getRouteKeyName(): string
    {
        return 'Kode';
    }
}
