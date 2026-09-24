<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Shared\Infrastructure\Persistence\ModelDasar;

/**
 * Setelan satu penyedia layanan luar di tingkat platform (PRD 8.23).
 *
 * `KredensialTerenkripsi` memakai cast `encrypted:array`: terenkripsi di basis data,
 * terbaca di memori. Kolom itu disembunyikan dari serialisasi supaya tidak pernah
 * ikut terkirim ke peramban lewat props Inertia.
 */
final class PenyediaLayananPlatform extends ModelDasar
{
    protected $table = 'PenyediaLayananPlatform';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $attributes = [
        'Aktif' => false,
        'Utama' => false,
        'ModeUji' => true,
    ];

    protected $fillable = [
        'Kategori',
        'Kode',
        'Aktif',
        'Utama',
        'ModeUji',
        'KredensialTerenkripsi',
        'SidikKredensial',
        'DiperbaruiOleh',
    ];

    protected $hidden = ['KredensialTerenkripsi'];

    protected function casts(): array
    {
        return [
            'Kategori' => KategoriPenyediaLayanan::class,
            'Aktif' => 'boolean',
            'Utama' => 'boolean',
            'ModeUji' => 'boolean',
            'KredensialTerenkripsi' => 'encrypted:array',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    /** @return array<string, string> */
    public function nilaiKredensial(): array
    {
        $nilai = [];

        foreach ((array) ($this->KredensialTerenkripsi ?? []) as $kunci => $isi) {
            if (is_string($kunci) && is_scalar($isi)) {
                $nilai[$kunci] = (string) $isi;
            }
        }

        return $nilai;
    }

    public function keKredensial(): KredensialPenyedia
    {
        return new KredensialPenyedia(
            $this->Kategori,
            (string) $this->Kode,
            (bool) $this->ModeUji,
            $this->nilaiKredensial(),
        );
    }
}
