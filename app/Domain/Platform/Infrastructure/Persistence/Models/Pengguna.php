<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * Pengguna sengaja tidak memakai MilikOrganisasi (lihat ADR 0002) supaya
     * resolusi user oleh guard autentikasi tidak butuh konteks organisasi
     * yang belum ada. Tapi route model binding admin (mis. /pengguna/{pengguna})
     * tetap wajib tenant-aware, jadi discope eksplisit di sini -- method ini
     * tidak dipakai oleh EloquentUserProvider::retrieveById().
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return static::query()
            ->where('OrganisasiId', app(KonteksOrganisasi::class)->id())
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi::class, 'OrganisasiId', 'Id');
    }

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::class, 'UnitOrganisasiId', 'Id');
    }

}
