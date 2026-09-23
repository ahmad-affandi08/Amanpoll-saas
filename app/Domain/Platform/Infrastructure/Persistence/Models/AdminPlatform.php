<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Shared\Infrastructure\Persistence\MenyimpanWaktuDalamUtc;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as Authenticatable;

/** Administrator platform Amanpoll (22.02). */
final class AdminPlatform extends Authenticatable
{
    use HasUlids, MenyimpanWaktuDalamUtc;

    protected $table = 'AdminPlatform';

    protected $primaryKey = 'Id';

    protected $keyType = 'string';

    public $incrementing = false;

    public const CREATED_AT = 'DibuatPada';

    public const UPDATED_AT = 'DiperbaruiPada';

    protected $fillable = [
        'Nama',
        'Email',
        'KataSandi',
        'Status',
        'Izin',
        'SuperAdmin',
        'TerakhirMasukPada',
    ];

    protected $hidden = ['KataSandi', 'TokenIngat'];

    protected function casts(): array
    {
        return [
            'Izin' => 'array',
            'SuperAdmin' => 'boolean',
            'TerakhirMasukPada' => 'immutable_datetime',
            'DibuatPada' => 'immutable_datetime',
            'DiperbaruiPada' => 'immutable_datetime',
            'KataSandi' => 'hashed',
        ];
    }

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

    public function getRouteKeyName(): string
    {
        return 'Id';
    }

    public function aktif(): bool
    {
        return $this->Status === 'Aktif';
    }
}
