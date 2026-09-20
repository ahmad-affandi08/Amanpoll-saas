<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

abstract class ModelDasar extends Model
{
    use HasUlids;

    protected $primaryKey = 'Id';

    protected $keyType = 'string';

    public $incrementing = false;

    public function getRouteKeyName(): string
    {
        return 'Id';
    }
}
