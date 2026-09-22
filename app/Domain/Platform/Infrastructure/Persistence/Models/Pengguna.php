<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatPenanggungJawabAset;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class Pengguna extends Authenticatable
{
    use HasUlids, Notifiable, SoftDeletes;

    protected $table = 'Pengguna';

    protected $primaryKey = 'Id';

    protected $keyType = 'string';

    public $incrementing = false;

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    public const DELETED_AT = 'DihapusPada';

    protected $fillable = [
        'OrganisasiId',
        'UnitOrganisasiId',
        'Nama',
        'Email',
        'Telepon',
        'KataSandi',
        'EmailTerverifikasiPada',
        'AvatarUrl',
        'NomorPegawai',
        'Jabatan',
        'JenisPengguna',
        'Status',
        'TerakhirMasukPada',
    ];

    protected function casts(): array
    {
        return [
            'EmailTerverifikasiPada' => 'immutable_datetime',
            'TerakhirMasukPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'DihapusPada' => 'immutable_datetime',
            'KataSandi' => 'hashed',
        ];
    }

    protected $hidden = ['KataSandi', 'TokenIngat'];

    public function getAuthPasswordName(): string
    {
        return 'KataSandi';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->KataSandi;
    }

    public function getRememberTokenName(): string
    {
        return 'TokenIngat';
    }

    public function routeNotificationForMail(): ?string
    {
        return $this->Email ?: null;
    }

    /** Pengguna sengaja tidak memakai MilikOrganisasi (lihat ADR 0002). */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return self::query()
            ->where('OrganisasiId', app(KonteksOrganisasi::class)->id())
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    /** @return BelongsTo<Organisasi, $this> */
    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'OrganisasiId', 'Id');
    }

    /** @return BelongsTo<UnitOrganisasi, $this> */
    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

    /**
     * @return HasMany<PenggunaPeran, $this>
     */
    public function penggunaPeran(): HasMany
    {
        return $this->hasMany(PenggunaPeran::class, 'PenggunaId', 'Id');
    }

    /**
     * @return HasMany<PerangkatPengguna, $this>
     */
    public function perangkat(): HasMany
    {
        return $this->hasMany(PerangkatPengguna::class, 'PenggunaId', 'Id');
    }

    /**
     * @return HasMany<PenugasanPerintahKerja, $this>
     */
    public function penugasanPerintahKerja(): HasMany
    {
        return $this->hasMany(PenugasanPerintahKerja::class, 'PenggunaId', 'Id')
            ->orderByDesc('DitugaskanPada');
    }

    /**
     * @return HasMany<WaktuKerja, $this>
     */
    public function waktuKerja(): HasMany
    {
        return $this->hasMany(WaktuKerja::class, 'PenggunaId', 'Id')->orderByDesc('MulaiPada');
    }

    /**
     * @return HasMany<RiwayatPenanggungJawabAset, $this>
     */
    public function riwayatPenanggungJawabAset(): HasMany
    {
        return $this->hasMany(RiwayatPenanggungJawabAset::class, 'PenggunaId', 'Id')
            ->orderByDesc('MulaiPada');
    }

    /**
     * @return HasMany<CatatanAkses, $this>
     */
    public function catatanAkses(): HasMany
    {
        return $this->hasMany(CatatanAkses::class, 'PenggunaId', 'Id')->orderByDesc('DibuatPada');
    }
}
