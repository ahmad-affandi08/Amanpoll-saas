<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Aset\Http\Policies\AsetPolicy;
use App\Domain\Aset\Http\Policies\KategoriAsetPolicy;
use App\Domain\Aset\Http\Policies\MerekPolicy;
use App\Domain\Aset\Http\Policies\ModelAsetPolicy;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\IntegrasiAudit\Http\Policies\CatatanAuditPolicy;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Kolaborasi\Http\Policies\BerkasPolicy;
use App\Domain\Kolaborasi\Http\Policies\TagPolicy;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;
use App\Domain\Notifikasi\Http\Policies\TemplatNotifikasiPolicy;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;
use App\Domain\Pemeliharaan\Http\Policies\KategoriKeluhanPolicy;
use App\Domain\Pemeliharaan\Http\Policies\KeluhanPolicy;
use App\Domain\Pemeliharaan\Http\Policies\PerintahKerjaPolicy;
use App\Domain\Pemeliharaan\Http\Policies\TingkatLayananPolicy;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Penyedia\Http\Policies\KategoriPenyediaPolicy;
use App\Domain\Penyedia\Http\Policies\KontakPenyediaPolicy;
use App\Domain\Penyedia\Http\Policies\PenilaianPenyediaPolicy;
use App\Domain\Penyedia\Http\Policies\PenyediaPolicy;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenilaianPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Persediaan\Http\Policies\GudangPolicy;
use App\Domain\Persediaan\Http\Policies\KategoriSukuCadangPolicy;
use App\Domain\Persediaan\Http\Policies\KelompokSukuCadangPolicy;
use App\Domain\Persediaan\Http\Policies\KompatibilitasSukuCadangPolicy;
use App\Domain\Persediaan\Http\Policies\MutasiStokPolicy;
use App\Domain\Persediaan\Http\Policies\ReservasiSukuCadangPolicy;
use App\Domain\Persediaan\Http\Policies\StokSukuCadangPolicy;
use App\Domain\Persediaan\Http\Policies\SukuCadangPolicy;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KompatibilitasSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Persetujuan\Http\Policies\AlurPersetujuanPolicy;
use App\Domain\Persetujuan\Http\Policies\TahapPersetujuanPolicy;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Http\Policies\HariLiburPolicy;
use App\Domain\Platform\Http\Policies\KategoriLokasiPolicy;
use App\Domain\Platform\Http\Policies\KonfigurasiOrganisasiPolicy;
use App\Domain\Platform\Http\Policies\KunciApiPolicy;
use App\Domain\Platform\Http\Policies\LokasiPolicy;
use App\Domain\Platform\Http\Policies\NomorDokumenPolicy;
use App\Domain\Platform\Http\Policies\OrganisasiPolicy;
use App\Domain\Platform\Http\Policies\PenggunaPolicy;
use App\Domain\Platform\Http\Policies\PeranPolicy;
use App\Domain\Platform\Http\Policies\UnitOrganisasiPolicy;
use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\SiklusAset\Http\Policies\PengajuanPenghapusanAsetPolicy;
use App\Domain\SiklusAset\Http\Policies\PermintaanMutasiAsetPolicy;
use App\Domain\SiklusAset\Http\Policies\SerahTerimaAsetPolicy;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
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
        Gate::policy(KategoriPenyedia::class, KategoriPenyediaPolicy::class);
        Gate::policy(Penyedia::class, PenyediaPolicy::class);
        Gate::policy(KontakPenyedia::class, KontakPenyediaPolicy::class);
        Gate::policy(PenilaianPenyedia::class, PenilaianPenyediaPolicy::class);
        Gate::policy(KategoriAset::class, KategoriAsetPolicy::class);
        Gate::policy(Merek::class, MerekPolicy::class);
        Gate::policy(ModelAset::class, ModelAsetPolicy::class);
        Gate::policy(Aset::class, AsetPolicy::class);
        Gate::policy(PermintaanMutasiAset::class, PermintaanMutasiAsetPolicy::class);
        Gate::policy(SerahTerimaAset::class, SerahTerimaAsetPolicy::class);
        Gate::policy(PengajuanPenghapusanAset::class, PengajuanPenghapusanAsetPolicy::class);
        Gate::policy(Gudang::class, GudangPolicy::class);
        Gate::policy(KategoriSukuCadang::class, KategoriSukuCadangPolicy::class);
        Gate::policy(SukuCadang::class, SukuCadangPolicy::class);
        Gate::policy(KelompokSukuCadang::class, KelompokSukuCadangPolicy::class);
        Gate::policy(KompatibilitasSukuCadang::class, KompatibilitasSukuCadangPolicy::class);
        Gate::policy(StokSukuCadang::class, StokSukuCadangPolicy::class);
        Gate::policy(MutasiStok::class, MutasiStokPolicy::class);
        Gate::policy(ReservasiSukuCadang::class, ReservasiSukuCadangPolicy::class);
        Gate::policy(TingkatLayanan::class, TingkatLayananPolicy::class);
        Gate::policy(KategoriKeluhan::class, KategoriKeluhanPolicy::class);
        Gate::policy(Keluhan::class, KeluhanPolicy::class);
        Gate::policy(PerintahKerja::class, PerintahKerjaPolicy::class);
    }
}
