<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Administrator platform Amanpoll (22.02).
 *
 * Sengaja tidak memakai trait MilikOrganisasi dan tidak punya OrganisasiId:
 * katalog paket berlaku lintas tenant, sehingga menaruhnya di bawah scope
 * tenant mana pun akan salah. Karena identitasnya terpisah, admin platform juga
 * tidak dapat dipakai untuk masuk ke aplikasi tenant, dan sebaliknya.
 */
final class AdminPlatform extends Authenticatable
{
    use HasUlids;

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
        'TerakhirMasukPada',
    ];

    protected $hidden = ['KataSandi', 'TokenIngat'];

    protected function casts(): array
    {
        return [
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
