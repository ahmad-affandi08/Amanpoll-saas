<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Audit\KorelasiId;
use App\Core\Audit\LayananAudit;
use App\Core\Entitas\RegistriEntitas;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Kepatuhan\Application\Services\RegistriAdapterSinkronisasi;
use App\Domain\Kepatuhan\Domain\Contracts\AdapterSinkronisasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use App\Domain\Kepatuhan\Infrastructure\Services\AdapterSinkronisasiRest;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Langganan\Application\Services\RegistriPenyediaPembayaran;
use App\Domain\Langganan\Infrastructure\Services\PenyediaPembayaranTransferManual;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Pelaporan\Application\Queries\QueryAnggaran;
use App\Domain\Pelaporan\Application\Queries\QueryAset;
use App\Domain\Pelaporan\Application\Queries\QueryBiaya;
use App\Domain\Pelaporan\Application\Queries\QueryKalibrasi;
use App\Domain\Pelaporan\Application\Queries\QueryKeandalan;
use App\Domain\Pelaporan\Application\Queries\QueryKeluhan;
use App\Domain\Pelaporan\Application\Queries\QueryKepatuhan;
use App\Domain\Pelaporan\Application\Queries\QueryKontrak;
use App\Domain\Pelaporan\Application\Queries\QueryPengadaan;
use App\Domain\Pelaporan\Application\Queries\QueryPerintahKerja;
use App\Domain\Pelaporan\Application\Queries\QueryPreventif;
use App\Domain\Pelaporan\Application\Queries\QueryStok;
use App\Domain\Pelaporan\Application\Queries\QueryTingkatLayanan;
use App\Domain\Pelaporan\Application\Services\LayananEksporLaporan;
use App\Domain\Pelaporan\Application\Services\PenyusunBarisLaporan;
use App\Domain\Pelaporan\Application\Services\RegistriKpi;
use App\Domain\Pelaporan\Infrastructure\Services\PenulisEksporCsv;
use App\Domain\Pelaporan\Infrastructure\Services\PenulisEksporPdf;
use App\Domain\Pelaporan\Infrastructure\Services\PenulisEksporXlsx;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Listeners\SinkronkanStatusPersetujuanPerencanaanPengadaan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\SiklusAset\Infrastructure\Listeners\SinkronkanStatusPersetujuanSiklusAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
use App\Domain\Sinkronisasi\Application\Services\RegistriOperasiSinkronisasi;
use App\Domain\Sinkronisasi\Infrastructure\Services\PenanganCatatWaktuKerja;
use App\Domain\Sinkronisasi\Infrastructure\Services\PenanganFinalisasiDaftarPeriksa;
use App\Domain\Sinkronisasi\Infrastructure\Services\PenanganResponsPenugasan;
use App\Domain\Sinkronisasi\Infrastructure\Services\PenanganSimpanJawabanDaftarPeriksa;
use App\Domain\Sinkronisasi\Infrastructure\Services\PenanganTambahCatatanPerintahKerja;
use App\Domain\Sinkronisasi\Infrastructure\Services\PenanganUbahStatusPerintahKerja;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Infrastructure\Persistence\TransaksiDatabaseLaravel;
use Illuminate\Support\ServiceProvider;

final class AmanpollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(KonteksOrganisasi::class, fn () => new KonteksOrganisasi);
        $this->app->scoped(KorelasiId::class, fn () => new KorelasiId);
        $this->app->singleton(RegistriEntitas::class);
        $this->app->bind(
            TransaksiDatabase::class,
            TransaksiDatabaseLaravel::class,
        );

        // Jenis integrasi yang butuh protokol sendiri mendaftarkan adapternya
        // ke registri ini; sisanya memakai adapter REST bawaan.
        $this->app->bind(AdapterSinkronisasi::class, AdapterSinkronisasiRest::class);
        $this->app->singleton(RegistriAdapterSinkronisasi::class);

        // Daftar putih mutasi yang boleh masuk lewat antrean offline (FASE 20).
        // Operasi yang tidak terdaftar di sini ditolak server, sehingga antrean
        // tidak dapat dipakai sebagai jalur pintas ke use-case sembarang.
        $this->app->singleton(RegistriOperasiSinkronisasi::class, function ($app): RegistriOperasiSinkronisasi {
            $registri = new RegistriOperasiSinkronisasi;
            foreach ([
                PenanganResponsPenugasan::class,
                PenanganUbahStatusPerintahKerja::class,
                PenanganCatatWaktuKerja::class,
                PenanganTambahCatatanPerintahKerja::class,
                PenanganSimpanJawabanDaftarPeriksa::class,
                PenanganFinalisasiDaftarPeriksa::class,
            ] as $penangan) {
                $registri->daftarkan($app->make($penangan));
            }

            return $registri;
        });

        // Setiap kelompok KPI (FASE 21.01) punya tepat satu penyedia; registri
        // menolak pendaftaran ganda supaya satu angka tidak punya dua rumus.
        $this->app->singleton(RegistriKpi::class, function ($app): RegistriKpi {
            $registri = new RegistriKpi;
            foreach ([
                QueryAset::class,
                QueryKeluhan::class,
                QueryPerintahKerja::class,
                QueryTingkatLayanan::class,
                QueryKeandalan::class,
                QueryBiaya::class,
                QueryStok::class,
                QueryKalibrasi::class,
                QueryPreventif::class,
                QueryPengadaan::class,
                QueryAnggaran::class,
                QueryKontrak::class,
                QueryKepatuhan::class,
            ] as $penyedia) {
                $registri->daftarkan($app->make($penyedia));
            }

            return $registri;
        });

        $this->app->singleton(LayananEksporLaporan::class, function ($app): LayananEksporLaporan {
            $layanan = new LayananEksporLaporan(
                $app->make(PenyusunBarisLaporan::class),
                $app->make(LayananNotifikasi::class),
                $app->make(LayananAudit::class),
            );
            foreach ([PenulisEksporCsv::class, PenulisEksporXlsx::class, PenulisEksporPdf::class] as $penulis) {
                $layanan->daftarkanPenulis($app->make($penulis));
            }

            return $layanan;
        });

        $this->app->singleton(
            RegistriPenyediaPembayaran::class,
            function ($app): RegistriPenyediaPembayaran {
                $registri = new RegistriPenyediaPembayaran;
                foreach ([PenyediaPembayaranTransferManual::class] as $penyedia) {
                    $registri->daftarkan($app->make($penyedia));
                }

                return $registri;
            },
        );
    }

    public function boot(): void
    {
        $zonaWaktu = (string) config('amanpoll.zona_waktu_default', 'Asia/Jakarta');
        config(['app.timezone' => $zonaWaktu]);
        date_default_timezone_set($zonaWaktu);

        // Binding repository spesifik domain ditambahkan ketika use-case mulai diimplementasikan.

        // Entitas yang boleh dilampiri berkas/tag/kolom kustom/komentar (FASE 05).
        // Modul domain baru mendaftarkan entitasnya sendiri di sini saat dibangun.
        $registri = $this->app->make(RegistriEntitas::class);
        $registri->daftarkan('UnitOrganisasi', UnitOrganisasi::class, 'Pengaturan.Kelola');
        $registri->daftarkan('Lokasi', Lokasi::class, 'Pengaturan.Kelola');
        $registri->daftarkan('Penyedia', Penyedia::class, 'Penyedia.Kelola');
        $registri->daftarkan('Aset', Aset::class, 'Aset.Ubah');
        $registri->daftarkan('GaransiAset', GaransiAset::class, 'Aset.Ubah');
        $registri->daftarkan('PermintaanMutasiAset', PermintaanMutasiAset::class, 'Aset.Ubah');
        $registri->daftarkan('SerahTerimaAset', SerahTerimaAset::class, 'Aset.Ubah');
        $registri->daftarkan('PengajuanPenghapusanAset', PengajuanPenghapusanAset::class, 'Aset.Hapus');
        $registri->daftarkan('Keluhan', Keluhan::class, 'Keluhan.Kelola');
        $registri->daftarkan('PerintahKerja', PerintahKerja::class, 'PerintahKerja.Kelola');
        $registri->daftarkan('JenisKalibrasi', JenisKalibrasi::class, 'Kalibrasi.Kelola');
        $registri->daftarkan('RencanaKalibrasi', RencanaKalibrasi::class, 'Kalibrasi.Kelola');
        $registri->daftarkan('PelaksanaanKalibrasi', PelaksanaanKalibrasi::class, 'Kalibrasi.Kelola');
        $registri->daftarkan('Kontrak', Kontrak::class, 'Kontrak.Kelola');
        $registri->daftarkan('SertifikasiAset', SertifikasiAset::class, 'Kepatuhan.Kelola');
        $registri->daftarkan('Anggaran', Anggaran::class, 'Pengadaan.Kelola');
        $registri->daftarkan('UsulanAset', UsulanAset::class, 'Pengadaan.Kelola');
        $registri->daftarkan('RencanaPengadaan', RencanaPengadaan::class, 'Pengadaan.Kelola');
        $registri->daftarkan('PermintaanPembelian', PermintaanPembelian::class, 'Pengadaan.Kelola');
        $registri->daftarkan('PermintaanPenawaran', PermintaanPenawaran::class, 'Pengadaan.Kelola');
        $registri->daftarkan('PenawaranPenyedia', PenawaranPenyedia::class, 'Pengadaan.Kelola');
        $registri->daftarkan('PesananPembelian', PesananPembelian::class, 'Pengadaan.Kelola');
        $registri->daftarkan('PenerimaanPembelian', PenerimaanPembelian::class, 'Pengadaan.Kelola');
        $registri->daftarkan('TagihanPenyedia', TagihanPenyedia::class, 'Pengadaan.Kelola');

        // Mesin Persetujuan (FASE 06) domain-agnostic; SiklusAset menyalin
        // balik hasil keputusan ke status entitasnya sendiri lewat observer.
        PermintaanPersetujuan::observe(SinkronkanStatusPersetujuanSiklusAset::class);
        PermintaanPersetujuan::observe(SinkronkanStatusPersetujuanPerencanaanPengadaan::class);
    }
}
