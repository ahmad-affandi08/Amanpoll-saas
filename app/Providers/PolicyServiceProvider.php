<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Platform\Http\Policies\PeranPolicy;
use App\Domain\Platform\Http\Policies\PenggunaPolicy;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Model domain berada di luar namespace App\Models sehingga auto-discovery
 * policy Laravel tidak menemukannya; didaftarkan eksplisit di sini.
 */
final class PolicyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Peran::class, PeranPolicy::class);
        Gate::policy(Pengguna::class, PenggunaPolicy::class);
    }
}
