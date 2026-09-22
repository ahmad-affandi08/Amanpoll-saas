<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Persistence\Models;

use App\Core\Penomoran\PunyaKodeOtomatis;
use App\Shared\Infrastructure\Persistence\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu otomasi pemasaran beserta versi aktifnya (MARKETING.md 17). */
final class OtomasiPemasaran extends ModelDasar
{
    use PunyaKodeOtomatis;

    protected $table = 'OtomasiPemasaran';

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = ['Kode', 'Nama', 'Keterangan', 'Pemicu', 'Aktif', 'VersiAktifId'];

    protected function casts(): array
    {
        return [
            'Aktif' => 'boolean',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
        ];
    }

    public function awalanKode(): string
    {
        return 'OTM';
    }

    public function getRouteKeyName(): string
    {
        return 'Kode';
    }

    /** @return BelongsTo<VersiOtomasiPemasaran, $this> */
    public function versiAktif(): BelongsTo
    {
        return $this->belongsTo(VersiOtomasiPemasaran::class, 'VersiAktifId', 'Id');
    }

    /** @return HasMany<VersiOtomasiPemasaran, $this> */
    public function versi(): HasMany
    {
        return $this->hasMany(VersiOtomasiPemasaran::class, 'OtomasiPemasaranId', 'Id')
            ->orderByDesc('Nomor');
    }

    /** @return HasMany<EksekusiOtomasiPemasaran, $this> */
    public function eksekusi(): HasMany
    {
        return $this->hasMany(EksekusiOtomasiPemasaran::class, 'OtomasiPemasaranId', 'Id');
    }

    /** Otomasi hanya berjalan bila menyala dan punya versi yang sudah diaktifkan. */
    public function siapJalan(): bool
    {
        return $this->Aktif && $this->VersiAktifId !== null;
    }
}
