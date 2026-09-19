<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\IntegrasiAudit\Http\Policies\CatatanAuditPolicy;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Kolaborasi\Http\Policies\BerkasPolicy;
use App\Domain\Kolaborasi\Http\Policies\TagPolicy;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;
use App\Domain\Notifikasi\Http\Policies\TemplatNotifikasiPolicy;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;
use App\Domain\Platform\Http\Policies\HariLiburPolicy;
use App\Domain\Platform\Http\Policies\KategoriLokasiPolicy;
use App\Domain\Platform\Http\Policies\KonfigurasiOrganisasiPolicy;
use App\Domain\Platform\Http\Policies\KunciApiPolicy;
use App\Domain\Platform\Http\Policies\LokasiPolicy;
use App\Domain\Platform\Http\Policies\NomorDokumenPolicy;
use App\Domain\Platform\Http\Policies\OrganisasiPolicy;
use App\Domain\Platform\Http\Policies\PeranPolicy;
use App\Domain\Platform\Http\Policies\PenggunaPolicy;
use App\Domain\Platform\Http\Policies\UnitOrganisasiPolicy;
use App\Domain\Persetujuan\Http\Policies\AlurPersetujuanPolicy;
use App\Domain\Persetujuan\Http\Policies\TahapPersetujuanPolicy;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
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
        Gate::policy(KunciApi::class, KunciApiPolicy::class);
        Gate::policy(Organisasi::class, OrganisasiPolicy::class);
        Gate::policy(UnitOrganisasi::class, UnitOrganisasiPolicy::class);
        Gate::policy(KategoriLokasi::class, KategoriLokasiPolicy::class);
        Gate::policy(Lokasi::class, LokasiPolicy::class);
        Gate::policy(KonfigurasiOrganisasi::class, KonfigurasiOrganisasiPolicy::class);
        Gate::policy(NomorDokumen::class, NomorDokumenPolicy::class);
        Gate::policy(HariLibur::class, HariLiburPolicy::class);
        Gate::policy(CatatanAudit::class, CatatanAuditPolicy::class);
        Gate::policy(Berkas::class, BerkasPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(AlurPersetujuan::class, AlurPersetujuanPolicy::class);
        Gate::policy(TahapPersetujuan::class, TahapPersetujuanPolicy::class);
        Gate::policy(TemplatNotifikasi::class, TemplatNotifikasiPolicy::class);
    }
}
