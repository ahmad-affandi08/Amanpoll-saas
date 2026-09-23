<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Audit\KorelasiId;
use App\Core\Audit\LayananAudit;
use App\Core\Entitas\RegistriEntitas;
use App\Core\Kesehatan\PeriksaKesehatanSistem;
use App\Core\Organisasi\KalenderOrganisasi;
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
use App\Domain\Langganan\Domain\Contracts\PembacaPembayaranLangganan;
use App\Domain\Langganan\Domain\Contracts\PemberiImbalanLangganan;
use App\Domain\Langganan\Domain\Events\PeristiwaLangganan;
use App\Domain\Langganan\Infrastructure\Services\PembacaPembayaranLanggananBawaan;
use App\Domain\Langganan\Infrastructure\Services\PemberiImbalanLanggananBawaan;
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
use App\Domain\Pemasaran\Application\Services\RegistriDatasetDemo;
use App\Domain\Pemasaran\Application\Services\RegistriTindakanOtomasi;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaEmailPemasaran;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaSosial;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Listeners\CatatPeristiwaRevenue;
use App\Domain\Pemasaran\Infrastructure\Listeners\PemicuOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Listeners\PerekamAktivasiTrial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Services\DatasetDemoManufaktur;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaEmailLaravel;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaSosialLog;
use App\Domain\Pemasaran\Infrastructure\Services\PenyediaWhatsAppLog;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanDaftarkanSequence;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanHentikanSequence;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanHitungUlangSkor;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanKirimEmail;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanKirimWhatsApp;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanNotifikasiInternal;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanPerpanjangTrial;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanPindahTahap;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanTambahTag;
use App\Domain\Pemasaran\Infrastructure\Tindakan\TindakanWebhook;
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
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
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
use App\Shared\Infrastructure\Ekspor\PenulisEksporCsv;
use App\Shared\Infrastructure\Ekspor\PenulisEksporPdf;
use App\Shared\Infrastructure\Ekspor\PenulisEksporXlsx;
use App\Shared\Infrastructure\Persistence\KoneksiMariaDbUtc;
use App\Shared\Infrastructure\Persistence\KoneksiMySqlUtc;
use App\Shared\Infrastructure\Persistence\TransaksiDatabaseLaravel;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

final class AmanpollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(KonteksOrganisasi::class, fn () => new KonteksOrganisasi);
        // Scoped, bukan singleton: zona tiap organisasi diingat selama satu permintaan
        // atau satu pekerjaan antrean saja, sehingga perubahan zona langsung berlaku.
        $this->app->scoped(KalenderOrganisasi::class);
        $this->app->scoped(KorelasiId::class, fn () => new KorelasiId);
        // Objek waktu yang terikat ke kueri dikirim sebagai UTC, sama seperti
        // kolom waktu yang disimpan model. Didaftarkan di register() karena
        // resolver harus sudah terpasang sebelum koneksi pertama dibuat.
        Connection::resolverFor('mysql', fn ($pdo, string $database, string $prefix, array $config) => new KoneksiMySqlUtc($pdo, $database, $prefix, $config));
        Connection::resolverFor('mariadb', fn ($pdo, string $database, string $prefix, array $config) => new KoneksiMariaDbUtc($pdo, $database, $prefix, $config));
        $this->app->singleton(RegistriEntitas::class);
        $this->app->bind(
            TransaksiDatabase::class,
            TransaksiDatabaseLaravel::class,
        );

        // Jenis integrasi yang butuh protokol sendiri mendaftarkan adapternya ke registri ini.
        $this->app->bind(AdapterSinkronisasi::class, AdapterSinkronisasiRest::class);
        $this->app->singleton(RegistriAdapterSinkronisasi::class);

        // Daftar putih mutasi yang boleh masuk lewat antrean offline (FASE 20).
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

        // Setiap kelompok KPI (FASE 21.01) punya tepat satu penyedia.
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

        // Penyedia email pemasaran dapat diganti lewat konfigurasi tanpa menyentuh pemanggilnya.
        $this->app->bind(PenyediaEmailPemasaran::class, function ($app): PenyediaEmailPemasaran {
            $kode = (string) config('amanpoll.pemasaran.penyedia_email', 'Laravel');

            return match ($kode) {
                default => $app->make(PenyediaEmailLaravel::class),
            };
        });

        // Penyedia WhatsApp dipilih lewat konfigurasi; bawaannya hanya menulis ke log, bukan mengirim.
        $this->app->bind(PenyediaWhatsApp::class, function ($app): PenyediaWhatsApp {
            $kode = (string) config('amanpoll.pemasaran.penyedia_whatsapp', 'Log');

            return match ($kode) {
                default => $app->make(PenyediaWhatsAppLog::class),
            };
        });

        // Penyedia penjadwal sosial; bawaannya hanya menulis ke log sampai adapter nyatanya ada.
        $this->app->bind(PenyediaSosial::class, function ($app): PenyediaSosial {
            $kode = (string) config('amanpoll.pemasaran.penyedia_sosial', 'Log');

            return match ($kode) {
                default => $app->make(PenyediaSosialLog::class),
            };
        });

        // Imbalan referral hanya boleh lewat domain Langganan, tidak pernah dengan menulis Billing dari luar.
        $this->app->bind(PemberiImbalanLangganan::class, PemberiImbalanLanggananBawaan::class);

        // Komisi partner bertanya ke domain Langganan apakah pembayarannya sungguh terjadi.
        $this->app->bind(PembacaPembayaranLangganan::class, PembacaPembayaranLanggananBawaan::class);

        // Dataset demo yang boleh dibangun ulang; kode di luar daftar ini ditolak saat disimpan.
        $this->app->singleton(RegistriDatasetDemo::class, fn ($app): RegistriDatasetDemo => new RegistriDatasetDemo([
            $app->make(DatasetDemoManufaktur::class),
        ]));

        // Daftar aksi otomasi disusun sekali; mesinnya hanya mengenal apa yang terdaftar di sini.
        $this->app->singleton(RegistriTindakanOtomasi::class, fn ($app): RegistriTindakanOtomasi => new RegistriTindakanOtomasi([
            $app->make(TindakanKirimEmail::class),
            $app->make(TindakanTambahTag::class),
            $app->make(TindakanHitungUlangSkor::class),
            $app->make(TindakanPindahTahap::class),
            $app->make(TindakanDaftarkanSequence::class),
            $app->make(TindakanHentikanSequence::class),
            $app->make(TindakanNotifikasiInternal::class),
            $app->make(TindakanPerpanjangTrial::class),
            $app->make(TindakanKirimWhatsApp::class),
            $app->make(TindakanWebhook::class),
        ]));
    }

    public function boot(): void
    {
        // Lazy loading yang lolos ke produksi adalah N+1 yang tidak pernah terlihat di sini (FASE 25.01).
        Model::preventLazyLoading(! $this->app->isProduction());

        // Binding repository spesifik domain ditambahkan ketika use-case mulai diimplementasikan.

        // Entitas yang boleh dilampiri berkas/tag/kolom kustom/komentar (FASE 05).
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

        // Mesin Persetujuan (FASE 06) domain-agnostic.
        PermintaanPersetujuan::observe(SinkronkanStatusPersetujuanSiklusAset::class);
        PermintaanPersetujuan::observe(SinkronkanStatusPersetujuanPerencanaanPengadaan::class);

        // Activation checklist trial terisi dari pekerjaan nyata (MARKETING.md 12).
        foreach ([Lokasi::class, Aset::class, Pengguna::class, PerintahKerja::class, RencanaPemeliharaan::class] as $model) {
            $model::observe(PerekamAktivasiTrial::class);
        }

        Event::listen(PeristiwaLangganan::class, CatatPeristiwaRevenue::class);

        // Tanpa ini rute `/up` hanya membuktikan PHP hidup; ia tetap menjawab
        // 200 di atas basis data yang mati.
        Event::listen(DiagnosingHealth::class, PeriksaKesehatanSistem::class);

        // Otomasi menyala dari peristiwa yang ditulis, bukan dari pemanggil yang harus ingat memicunya.
        EventPemasaran::observe(PemicuOtomasiPemasaran::class);

        $this->catatPekerjaanGagal();
    }

    /**
     * Pekerjaan yang habis percobaannya meninggalkan jejak di log aplikasi.
     *
     * Tanpa ini kegagalan permanen hanya mengendap di tabel `PekerjaanGagal`
     * yang tidak dibaca siapa pun lalu dipangkas seminggu kemudian, sehingga
     * pekerjaan yang berhenti diam-diam terlihat persis seperti pekerjaan yang
     * memang tidak pernah diantrekan (FASE 25.02).
     */
    private function catatPekerjaanGagal(): void
    {
        Queue::failing(function (JobFailed $peristiwa): void {
            Log::error('Pekerjaan antrian gagal permanen.', [
                'Pekerjaan' => $peristiwa->job->resolveName(),
                'Koneksi' => $peristiwa->connectionName,
                'Antrian' => $peristiwa->job->getQueue(),
                'Percobaan' => $peristiwa->job->attempts(),
                'Pengecualian' => $peristiwa->exception::class,
                'Pesan' => mb_substr($peristiwa->exception->getMessage(), 0, 500),
            ]);
        });
    }
}
