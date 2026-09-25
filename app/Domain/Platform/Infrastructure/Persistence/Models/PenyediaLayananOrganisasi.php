<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\MilikOrganisasi;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Infrastructure\Persistence\ModelDasar;

/**
 * Email atau WhatsApp milik organisasi untuk notifikasi stafnya (PRD 8.23).
 *
 * Satu organisasi hanya punya satu penyedia aktif per kategori. Kredensialnya
 * terenkripsi dan disembunyikan dari serialisasi, sama seperti milik platform.
 */
final class PenyediaLayananOrganisasi extends ModelDasar
{
    use MilikOrganisasi;
    use PunyaKredensialPenyedia;

    protected $table = 'PenyediaLayananOrganisasi';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $attributes = [
        'Aktif' => false,
        'ModeUji' => false,
    ];

    protected $fillable = [
        'OrganisasiId',
        'Kategori',
        'Kode',
        'Aktif',
        'ModeUji',
        'KredensialTerenkripsi',
        'SidikKredensial',
        'TerakhirBerhasilPada',
        'TerakhirGagalPada',
        'GalatTerakhir',
        'DiperbaruiOleh',
    ];

    protected $hidden = ['KredensialTerenkripsi'];

    protected function casts(): array
    {
        return [
            'Kategori' => KategoriPenyediaLayanan::class,
            'Aktif' => 'boolean',
            'ModeUji' => 'boolean',
            'KredensialTerenkripsi' => 'encrypted:array',
            'TerakhirBerhasilPada' => 'immutable_datetime',
            'TerakhirGagalPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** Kegagalan terakhir lebih baru daripada keberhasilan terakhir: kiriman berikutnya kemungkinan gagal juga. */
    public function sedangBermasalah(): bool
    {
        return $this->TerakhirGagalPada !== null
            && ($this->TerakhirBerhasilPada === null || $this->TerakhirGagalPada->greaterThan($this->TerakhirBerhasilPada));
    }

    protected function tempatPengaturan(): string
    {
        return KredensialPenyedia::TEMPAT_ORGANISASI;
    }
}
