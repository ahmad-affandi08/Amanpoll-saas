<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Aset\Domain\Repositories\AsetRepository;
use App\Domain\Aset\Domain\Repositories\GaransiAsetRepository;
use App\Domain\Aset\Domain\Repositories\KategoriAsetRepository;
use App\Domain\Aset\Domain\Repositories\MerekRepository;
use App\Domain\Aset\Domain\Repositories\MeterAsetRepository;
use App\Domain\Aset\Domain\Repositories\ModelAsetRepository;
use App\Domain\Aset\Domain\Repositories\NilaiAsetRepository;
use App\Domain\Aset\Domain\Repositories\PembacaanMeterAsetRepository;
use App\Domain\Aset\Domain\Repositories\RelasiAsetRepository;
use App\Domain\Aset\Domain\Repositories\RiwayatLokasiAsetRepository;
use App\Domain\Aset\Domain\Repositories\RiwayatPenanggungJawabAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentGaransiAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentKategoriAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentMerekRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentMeterAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentModelAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentNilaiAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentPembacaanMeterAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentRelasiAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentRiwayatLokasiAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Repositories\EloquentRiwayatPenanggungJawabAsetRepository;
use App\Domain\IntegrasiAudit\Domain\Repositories\CatatanAksesRepository;
use App\Domain\IntegrasiAudit\Domain\Repositories\CatatanAuditRepository;
use App\Domain\IntegrasiAudit\Domain\Repositories\KotakKeluarPeristiwaRepository;
use App\Domain\IntegrasiAudit\Domain\Repositories\KunciIdempotensiRepository;
use App\Domain\IntegrasiAudit\Domain\Repositories\PanggilanBalikWebRepository;
use App\Domain\IntegrasiAudit\Domain\Repositories\PengirimanPanggilanBalikWebRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories\EloquentCatatanAksesRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories\EloquentCatatanAuditRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories\EloquentKotakKeluarPeristiwaRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories\EloquentKunciIdempotensiRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories\EloquentPanggilanBalikWebRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories\EloquentPengirimanPanggilanBalikWebRepository;
use App\Domain\Kalibrasi\Domain\Repositories\HasilTitikUkurKalibrasiRepository;
use App\Domain\Kalibrasi\Domain\Repositories\JenisKalibrasiRepository;
use App\Domain\Kalibrasi\Domain\Repositories\PelaksanaanKalibrasiRepository;
use App\Domain\Kalibrasi\Domain\Repositories\RencanaKalibrasiRepository;
use App\Domain\Kalibrasi\Domain\Repositories\TitikUkurKalibrasiRepository;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Repositories\EloquentHasilTitikUkurKalibrasiRepository;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Repositories\EloquentJenisKalibrasiRepository;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Repositories\EloquentPelaksanaanKalibrasiRepository;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Repositories\EloquentRencanaKalibrasiRepository;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Repositories\EloquentTitikUkurKalibrasiRepository;
use App\Domain\Kepatuhan\Domain\Repositories\IntegrasiEksternalRepository;
use App\Domain\Kepatuhan\Domain\Repositories\KepatuhanAsetRepository;
use App\Domain\Kepatuhan\Domain\Repositories\PemetaanDataEksternalRepository;
use App\Domain\Kepatuhan\Domain\Repositories\PersyaratanKepatuhanRepository;
use App\Domain\Kepatuhan\Domain\Repositories\SertifikasiAsetRepository;
use App\Domain\Kepatuhan\Domain\Repositories\SinkronisasiEksternalRepository;
use App\Domain\Kepatuhan\Domain\Repositories\StandarKepatuhanRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories\EloquentIntegrasiEksternalRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories\EloquentKepatuhanAsetRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories\EloquentPemetaanDataEksternalRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories\EloquentPersyaratanKepatuhanRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories\EloquentSertifikasiAsetRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories\EloquentSinkronisasiEksternalRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories\EloquentStandarKepatuhanRepository;
use App\Domain\Kolaborasi\Domain\Repositories\BerkasRepository;
use App\Domain\Kolaborasi\Domain\Repositories\DefinisiKolomKustomRepository;
use App\Domain\Kolaborasi\Domain\Repositories\EntitasTagRepository;
use App\Domain\Kolaborasi\Domain\Repositories\KomentarEntitasRepository;
use App\Domain\Kolaborasi\Domain\Repositories\LampiranEntitasRepository;
use App\Domain\Kolaborasi\Domain\Repositories\NilaiKolomKustomRepository;
use App\Domain\Kolaborasi\Domain\Repositories\TagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentBerkasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentDefinisiKolomKustomRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentEntitasTagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentKomentarEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentLampiranEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentNilaiKolomKustomRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories\EloquentTagRepository;
use App\Domain\Kontrak\Domain\Repositories\KontrakAsetRepository;
use App\Domain\Kontrak\Domain\Repositories\KontrakRepository;
use App\Domain\Kontrak\Domain\Repositories\LayananKontrakRepository;
use App\Domain\Kontrak\Infrastructure\Persistence\Repositories\EloquentKontrakAsetRepository;
use App\Domain\Kontrak\Infrastructure\Persistence\Repositories\EloquentKontrakRepository;
use App\Domain\Kontrak\Infrastructure\Persistence\Repositories\EloquentLayananKontrakRepository;
use App\Domain\Langganan\Domain\Repositories\FiturPaketRepository;
use App\Domain\Langganan\Domain\Repositories\LanggananRepository;
use App\Domain\Langganan\Domain\Repositories\PaketFiturRepository;
use App\Domain\Langganan\Domain\Repositories\PaketLanggananRepository;
use App\Domain\Langganan\Domain\Repositories\PembayaranLanggananRepository;
use App\Domain\Langganan\Domain\Repositories\TagihanLanggananRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Repositories\EloquentFiturPaketRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Repositories\EloquentLanggananRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Repositories\EloquentPaketFiturRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Repositories\EloquentPaketLanggananRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Repositories\EloquentPembayaranLanggananRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Repositories\EloquentTagihanLanggananRepository;
use App\Domain\Notifikasi\Domain\Repositories\EskalasiTingkatLayananRepository;
use App\Domain\Notifikasi\Domain\Repositories\NotifikasiRepository;
use App\Domain\Notifikasi\Domain\Repositories\PreferensiNotifikasiRepository;
use App\Domain\Notifikasi\Domain\Repositories\TemplatNotifikasiRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Repositories\EloquentEskalasiTingkatLayananRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Repositories\EloquentNotifikasiRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Repositories\EloquentPreferensiNotifikasiRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Repositories\EloquentTemplatNotifikasiRepository;
use App\Domain\Pelaporan\Domain\Repositories\DasborTersimpanRepository;
use App\Domain\Pelaporan\Domain\Repositories\KomponenDasborRepository;
use App\Domain\Pelaporan\Domain\Repositories\LaporanTersimpanRepository;
use App\Domain\Pelaporan\Infrastructure\Persistence\Repositories\EloquentDasborTersimpanRepository;
use App\Domain\Pelaporan\Infrastructure\Persistence\Repositories\EloquentKomponenDasborRepository;
use App\Domain\Pelaporan\Infrastructure\Persistence\Repositories\EloquentLaporanTersimpanRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\AnalisisKegagalanRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\AturanTingkatLayananRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\BiayaPerintahKerjaRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\KategoriKeluhanRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\KeluhanRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\KodeKegagalanRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\PenugasanPerintahKerjaRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\PerintahKerjaAsetRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\PerintahKerjaRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\RiwayatStatusKeluhanRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\RiwayatStatusPerintahKerjaRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\TingkatLayananRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\WaktuHentiAsetRepository;
use App\Domain\Pemeliharaan\Domain\Repositories\WaktuKerjaRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentAnalisisKegagalanRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentAturanTingkatLayananRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentBiayaPerintahKerjaRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentKategoriKeluhanRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentKeluhanRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentKodeKegagalanRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentPenugasanPerintahKerjaRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentPerintahKerjaAsetRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentPerintahKerjaRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentRiwayatStatusKeluhanRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentRiwayatStatusPerintahKerjaRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentTingkatLayananRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentWaktuHentiAsetRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories\EloquentWaktuKerjaRepository;
use App\Domain\Penyedia\Domain\Repositories\KategoriPenyediaRepository;
use App\Domain\Penyedia\Domain\Repositories\KontakPenyediaRepository;
use App\Domain\Penyedia\Domain\Repositories\PenilaianPenyediaRepository;
use App\Domain\Penyedia\Domain\Repositories\PenyediaKategoriRepository;
use App\Domain\Penyedia\Domain\Repositories\PenyediaRepository;
use App\Domain\Penyedia\Infrastructure\Persistence\Repositories\EloquentKategoriPenyediaRepository;
use App\Domain\Penyedia\Infrastructure\Persistence\Repositories\EloquentKontakPenyediaRepository;
use App\Domain\Penyedia\Infrastructure\Persistence\Repositories\EloquentPenilaianPenyediaRepository;
use App\Domain\Penyedia\Infrastructure\Persistence\Repositories\EloquentPenyediaKategoriRepository;
use App\Domain\Penyedia\Infrastructure\Persistence\Repositories\EloquentPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\AnggaranRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\DetailPenawaranPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\DetailPenerimaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\DetailPermintaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\DetailPesananPembelianRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\DetailRencanaPengadaanRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\PembayaranPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\PenawaranPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\PenerimaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\PenilaianUsulanAsetRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\PenyediaPermintaanPenawaranRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\PermintaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\PermintaanPenawaranRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\PesananPembelianRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\PosAnggaranRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\RencanaPengadaanRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\TagihanPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\TransaksiAnggaranRepository;
use App\Domain\PerencanaanPengadaan\Domain\Repositories\UsulanAsetRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentAnggaranRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentDetailPenawaranPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentDetailPenerimaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentDetailPermintaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentDetailPesananPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentDetailRencanaPengadaanRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentPembayaranPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentPenawaranPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentPenerimaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentPenilaianUsulanAsetRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentPenyediaPermintaanPenawaranRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentPermintaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentPermintaanPenawaranRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentPesananPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentPosAnggaranRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentRencanaPengadaanRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentTagihanPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentTransaksiAnggaranRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories\EloquentUsulanAsetRepository;
use App\Domain\Persediaan\Domain\Repositories\DetailMutasiStokRepository;
use App\Domain\Persediaan\Domain\Repositories\GudangRepository;
use App\Domain\Persediaan\Domain\Repositories\KategoriSukuCadangRepository;
use App\Domain\Persediaan\Domain\Repositories\KelompokSukuCadangRepository;
use App\Domain\Persediaan\Domain\Repositories\KompatibilitasSukuCadangRepository;
use App\Domain\Persediaan\Domain\Repositories\LokasiGudangRepository;
use App\Domain\Persediaan\Domain\Repositories\MutasiStokRepository;
use App\Domain\Persediaan\Domain\Repositories\PemakaianSukuCadangRepository;
use App\Domain\Persediaan\Domain\Repositories\ReservasiSukuCadangRepository;
use App\Domain\Persediaan\Domain\Repositories\StokSukuCadangRepository;
use App\Domain\Persediaan\Domain\Repositories\SukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentDetailMutasiStokRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentGudangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentKategoriSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentKelompokSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentKompatibilitasSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentLokasiGudangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentMutasiStokRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentPemakaianSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentReservasiSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentStokSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Repositories\EloquentSukuCadangRepository;
use App\Domain\Persetujuan\Domain\Repositories\AlurPersetujuanRepository;
use App\Domain\Persetujuan\Domain\Repositories\KeputusanPersetujuanRepository;
use App\Domain\Persetujuan\Domain\Repositories\PermintaanPersetujuanRepository;
use App\Domain\Persetujuan\Domain\Repositories\TahapPersetujuanRepository;
use App\Domain\Persetujuan\Infrastructure\Persistence\Repositories\EloquentAlurPersetujuanRepository;
use App\Domain\Persetujuan\Infrastructure\Persistence\Repositories\EloquentKeputusanPersetujuanRepository;
use App\Domain\Persetujuan\Infrastructure\Persistence\Repositories\EloquentPermintaanPersetujuanRepository;
use App\Domain\Persetujuan\Infrastructure\Persistence\Repositories\EloquentTahapPersetujuanRepository;
use App\Domain\Platform\Domain\Repositories\HariLiburRepository;
use App\Domain\Platform\Domain\Repositories\IzinRepository;
use App\Domain\Platform\Domain\Repositories\KategoriLokasiRepository;
use App\Domain\Platform\Domain\Repositories\KonfigurasiOrganisasiRepository;
use App\Domain\Platform\Domain\Repositories\KunciApiRepository;
use App\Domain\Platform\Domain\Repositories\LokasiRepository;
use App\Domain\Platform\Domain\Repositories\NomorDokumenRepository;
use App\Domain\Platform\Domain\Repositories\OrganisasiRepository;
use App\Domain\Platform\Domain\Repositories\PenggunaPeranRepository;
use App\Domain\Platform\Domain\Repositories\PenggunaRepository;
use App\Domain\Platform\Domain\Repositories\PerangkatPenggunaRepository;
use App\Domain\Platform\Domain\Repositories\PeranIzinRepository;
use App\Domain\Platform\Domain\Repositories\PeranRepository;
use App\Domain\Platform\Domain\Repositories\UnitOrganisasiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentHariLiburRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentIzinRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentKategoriLokasiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentKonfigurasiOrganisasiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentKunciApiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentLokasiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentNomorDokumenRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentOrganisasiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentPenggunaPeranRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentPenggunaRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentPerangkatPenggunaRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentPeranIzinRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentPeranRepository;
use App\Domain\Platform\Infrastructure\Persistence\Repositories\EloquentUnitOrganisasiRepository;
use App\Domain\PreventifInspeksi\Domain\Repositories\ButirTemplatDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Domain\Repositories\InspeksiRepository;
use App\Domain\PreventifInspeksi\Domain\Repositories\JadwalPemeliharaanRepository;
use App\Domain\PreventifInspeksi\Domain\Repositories\JawabanDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Domain\Repositories\PelaksanaanDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Domain\Repositories\RencanaPemeliharaanAsetRepository;
use App\Domain\PreventifInspeksi\Domain\Repositories\RencanaPemeliharaanRepository;
use App\Domain\PreventifInspeksi\Domain\Repositories\TemplatDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Domain\Repositories\TemplatInspeksiRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories\EloquentButirTemplatDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories\EloquentInspeksiRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories\EloquentJadwalPemeliharaanRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories\EloquentJawabanDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories\EloquentPelaksanaanDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories\EloquentRencanaPemeliharaanAsetRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories\EloquentRencanaPemeliharaanRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories\EloquentTemplatDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories\EloquentTemplatInspeksiRepository;
use App\Domain\SiklusAset\Domain\Repositories\DetailMutasiAsetRepository;
use App\Domain\SiklusAset\Domain\Repositories\DetailPenghapusanAsetRepository;
use App\Domain\SiklusAset\Domain\Repositories\DetailSerahTerimaAsetRepository;
use App\Domain\SiklusAset\Domain\Repositories\PengajuanPenghapusanAsetRepository;
use App\Domain\SiklusAset\Domain\Repositories\PermintaanMutasiAsetRepository;
use App\Domain\SiklusAset\Domain\Repositories\SerahTerimaAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Repositories\EloquentDetailMutasiAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Repositories\EloquentDetailPenghapusanAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Repositories\EloquentDetailSerahTerimaAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Repositories\EloquentPengajuanPenghapusanAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Repositories\EloquentPermintaanMutasiAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Repositories\EloquentSerahTerimaAsetRepository;
use App\Domain\Sinkronisasi\Domain\Repositories\AntrianSinkronisasiRepository;
use App\Domain\Sinkronisasi\Domain\Repositories\PenandaSinkronisasiRepository;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Repositories\EloquentAntrianSinkronisasiRepository;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Repositories\EloquentPenandaSinkronisasiRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        OrganisasiRepository::class => EloquentOrganisasiRepository::class,
        UnitOrganisasiRepository::class => EloquentUnitOrganisasiRepository::class,
        KategoriLokasiRepository::class => EloquentKategoriLokasiRepository::class,
        LokasiRepository::class => EloquentLokasiRepository::class,
        PenggunaRepository::class => EloquentPenggunaRepository::class,
        PeranRepository::class => EloquentPeranRepository::class,
        IzinRepository::class => EloquentIzinRepository::class,
        PenggunaPeranRepository::class => EloquentPenggunaPeranRepository::class,
        PeranIzinRepository::class => EloquentPeranIzinRepository::class,
        PerangkatPenggunaRepository::class => EloquentPerangkatPenggunaRepository::class,
        KunciApiRepository::class => EloquentKunciApiRepository::class,
        KonfigurasiOrganisasiRepository::class => EloquentKonfigurasiOrganisasiRepository::class,
        NomorDokumenRepository::class => EloquentNomorDokumenRepository::class,
        HariLiburRepository::class => EloquentHariLiburRepository::class,
        BerkasRepository::class => EloquentBerkasRepository::class,
        LampiranEntitasRepository::class => EloquentLampiranEntitasRepository::class,
        TagRepository::class => EloquentTagRepository::class,
        EntitasTagRepository::class => EloquentEntitasTagRepository::class,
        DefinisiKolomKustomRepository::class => EloquentDefinisiKolomKustomRepository::class,
        NilaiKolomKustomRepository::class => EloquentNilaiKolomKustomRepository::class,
        KomentarEntitasRepository::class => EloquentKomentarEntitasRepository::class,
        KategoriPenyediaRepository::class => EloquentKategoriPenyediaRepository::class,
        PenyediaRepository::class => EloquentPenyediaRepository::class,
        PenyediaKategoriRepository::class => EloquentPenyediaKategoriRepository::class,
        KontakPenyediaRepository::class => EloquentKontakPenyediaRepository::class,
        PenilaianPenyediaRepository::class => EloquentPenilaianPenyediaRepository::class,
        KategoriAsetRepository::class => EloquentKategoriAsetRepository::class,
        MerekRepository::class => EloquentMerekRepository::class,
        ModelAsetRepository::class => EloquentModelAsetRepository::class,
        AsetRepository::class => EloquentAsetRepository::class,
        RelasiAsetRepository::class => EloquentRelasiAsetRepository::class,
        RiwayatLokasiAsetRepository::class => EloquentRiwayatLokasiAsetRepository::class,
        RiwayatPenanggungJawabAsetRepository::class => EloquentRiwayatPenanggungJawabAsetRepository::class,
        GaransiAsetRepository::class => EloquentGaransiAsetRepository::class,
        NilaiAsetRepository::class => EloquentNilaiAsetRepository::class,
        MeterAsetRepository::class => EloquentMeterAsetRepository::class,
        PembacaanMeterAsetRepository::class => EloquentPembacaanMeterAsetRepository::class,
        PermintaanMutasiAsetRepository::class => EloquentPermintaanMutasiAsetRepository::class,
        DetailMutasiAsetRepository::class => EloquentDetailMutasiAsetRepository::class,
        SerahTerimaAsetRepository::class => EloquentSerahTerimaAsetRepository::class,
        DetailSerahTerimaAsetRepository::class => EloquentDetailSerahTerimaAsetRepository::class,
        PengajuanPenghapusanAsetRepository::class => EloquentPengajuanPenghapusanAsetRepository::class,
        DetailPenghapusanAsetRepository::class => EloquentDetailPenghapusanAsetRepository::class,
        TingkatLayananRepository::class => EloquentTingkatLayananRepository::class,
        AturanTingkatLayananRepository::class => EloquentAturanTingkatLayananRepository::class,
        KategoriKeluhanRepository::class => EloquentKategoriKeluhanRepository::class,
        KeluhanRepository::class => EloquentKeluhanRepository::class,
        RiwayatStatusKeluhanRepository::class => EloquentRiwayatStatusKeluhanRepository::class,
        PerintahKerjaRepository::class => EloquentPerintahKerjaRepository::class,
        PerintahKerjaAsetRepository::class => EloquentPerintahKerjaAsetRepository::class,
        PenugasanPerintahKerjaRepository::class => EloquentPenugasanPerintahKerjaRepository::class,
        RiwayatStatusPerintahKerjaRepository::class => EloquentRiwayatStatusPerintahKerjaRepository::class,
        WaktuKerjaRepository::class => EloquentWaktuKerjaRepository::class,
        WaktuHentiAsetRepository::class => EloquentWaktuHentiAsetRepository::class,
        BiayaPerintahKerjaRepository::class => EloquentBiayaPerintahKerjaRepository::class,
        KodeKegagalanRepository::class => EloquentKodeKegagalanRepository::class,
        AnalisisKegagalanRepository::class => EloquentAnalisisKegagalanRepository::class,
        TemplatDaftarPeriksaRepository::class => EloquentTemplatDaftarPeriksaRepository::class,
        ButirTemplatDaftarPeriksaRepository::class => EloquentButirTemplatDaftarPeriksaRepository::class,
        PelaksanaanDaftarPeriksaRepository::class => EloquentPelaksanaanDaftarPeriksaRepository::class,
        JawabanDaftarPeriksaRepository::class => EloquentJawabanDaftarPeriksaRepository::class,
        RencanaPemeliharaanRepository::class => EloquentRencanaPemeliharaanRepository::class,
        RencanaPemeliharaanAsetRepository::class => EloquentRencanaPemeliharaanAsetRepository::class,
        JadwalPemeliharaanRepository::class => EloquentJadwalPemeliharaanRepository::class,
        TemplatInspeksiRepository::class => EloquentTemplatInspeksiRepository::class,
        InspeksiRepository::class => EloquentInspeksiRepository::class,
        JenisKalibrasiRepository::class => EloquentJenisKalibrasiRepository::class,
        RencanaKalibrasiRepository::class => EloquentRencanaKalibrasiRepository::class,
        PelaksanaanKalibrasiRepository::class => EloquentPelaksanaanKalibrasiRepository::class,
        TitikUkurKalibrasiRepository::class => EloquentTitikUkurKalibrasiRepository::class,
        HasilTitikUkurKalibrasiRepository::class => EloquentHasilTitikUkurKalibrasiRepository::class,
        GudangRepository::class => EloquentGudangRepository::class,
        LokasiGudangRepository::class => EloquentLokasiGudangRepository::class,
        KategoriSukuCadangRepository::class => EloquentKategoriSukuCadangRepository::class,
        SukuCadangRepository::class => EloquentSukuCadangRepository::class,
        KompatibilitasSukuCadangRepository::class => EloquentKompatibilitasSukuCadangRepository::class,
        KelompokSukuCadangRepository::class => EloquentKelompokSukuCadangRepository::class,
        StokSukuCadangRepository::class => EloquentStokSukuCadangRepository::class,
        MutasiStokRepository::class => EloquentMutasiStokRepository::class,
        DetailMutasiStokRepository::class => EloquentDetailMutasiStokRepository::class,
        PemakaianSukuCadangRepository::class => EloquentPemakaianSukuCadangRepository::class,
        ReservasiSukuCadangRepository::class => EloquentReservasiSukuCadangRepository::class,
        AnggaranRepository::class => EloquentAnggaranRepository::class,
        PosAnggaranRepository::class => EloquentPosAnggaranRepository::class,
        TransaksiAnggaranRepository::class => EloquentTransaksiAnggaranRepository::class,
        UsulanAsetRepository::class => EloquentUsulanAsetRepository::class,
        PenilaianUsulanAsetRepository::class => EloquentPenilaianUsulanAsetRepository::class,
        RencanaPengadaanRepository::class => EloquentRencanaPengadaanRepository::class,
        DetailRencanaPengadaanRepository::class => EloquentDetailRencanaPengadaanRepository::class,
        PermintaanPembelianRepository::class => EloquentPermintaanPembelianRepository::class,
        DetailPermintaanPembelianRepository::class => EloquentDetailPermintaanPembelianRepository::class,
        PermintaanPenawaranRepository::class => EloquentPermintaanPenawaranRepository::class,
        PenyediaPermintaanPenawaranRepository::class => EloquentPenyediaPermintaanPenawaranRepository::class,
        PenawaranPenyediaRepository::class => EloquentPenawaranPenyediaRepository::class,
        DetailPenawaranPenyediaRepository::class => EloquentDetailPenawaranPenyediaRepository::class,
        PesananPembelianRepository::class => EloquentPesananPembelianRepository::class,
        DetailPesananPembelianRepository::class => EloquentDetailPesananPembelianRepository::class,
        PenerimaanPembelianRepository::class => EloquentPenerimaanPembelianRepository::class,
        DetailPenerimaanPembelianRepository::class => EloquentDetailPenerimaanPembelianRepository::class,
        TagihanPenyediaRepository::class => EloquentTagihanPenyediaRepository::class,
        PembayaranPenyediaRepository::class => EloquentPembayaranPenyediaRepository::class,
        KontrakRepository::class => EloquentKontrakRepository::class,
        KontrakAsetRepository::class => EloquentKontrakAsetRepository::class,
        LayananKontrakRepository::class => EloquentLayananKontrakRepository::class,
        StandarKepatuhanRepository::class => EloquentStandarKepatuhanRepository::class,
        PersyaratanKepatuhanRepository::class => EloquentPersyaratanKepatuhanRepository::class,
        KepatuhanAsetRepository::class => EloquentKepatuhanAsetRepository::class,
        SertifikasiAsetRepository::class => EloquentSertifikasiAsetRepository::class,
        IntegrasiEksternalRepository::class => EloquentIntegrasiEksternalRepository::class,
        PemetaanDataEksternalRepository::class => EloquentPemetaanDataEksternalRepository::class,
        SinkronisasiEksternalRepository::class => EloquentSinkronisasiEksternalRepository::class,
        AlurPersetujuanRepository::class => EloquentAlurPersetujuanRepository::class,
        TahapPersetujuanRepository::class => EloquentTahapPersetujuanRepository::class,
        PermintaanPersetujuanRepository::class => EloquentPermintaanPersetujuanRepository::class,
        KeputusanPersetujuanRepository::class => EloquentKeputusanPersetujuanRepository::class,
        TemplatNotifikasiRepository::class => EloquentTemplatNotifikasiRepository::class,
        PreferensiNotifikasiRepository::class => EloquentPreferensiNotifikasiRepository::class,
        NotifikasiRepository::class => EloquentNotifikasiRepository::class,
        EskalasiTingkatLayananRepository::class => EloquentEskalasiTingkatLayananRepository::class,
        PanggilanBalikWebRepository::class => EloquentPanggilanBalikWebRepository::class,
        PengirimanPanggilanBalikWebRepository::class => EloquentPengirimanPanggilanBalikWebRepository::class,
        KotakKeluarPeristiwaRepository::class => EloquentKotakKeluarPeristiwaRepository::class,
        KunciIdempotensiRepository::class => EloquentKunciIdempotensiRepository::class,
        CatatanAuditRepository::class => EloquentCatatanAuditRepository::class,
        CatatanAksesRepository::class => EloquentCatatanAksesRepository::class,
        AntrianSinkronisasiRepository::class => EloquentAntrianSinkronisasiRepository::class,
        PenandaSinkronisasiRepository::class => EloquentPenandaSinkronisasiRepository::class,
        LaporanTersimpanRepository::class => EloquentLaporanTersimpanRepository::class,
        DasborTersimpanRepository::class => EloquentDasborTersimpanRepository::class,
        KomponenDasborRepository::class => EloquentKomponenDasborRepository::class,
        FiturPaketRepository::class => EloquentFiturPaketRepository::class,
        PaketLanggananRepository::class => EloquentPaketLanggananRepository::class,
        PaketFiturRepository::class => EloquentPaketFiturRepository::class,
        LanggananRepository::class => EloquentLanggananRepository::class,
        TagihanLanggananRepository::class => EloquentTagihanLanggananRepository::class,
        PembayaranLanggananRepository::class => EloquentPembayaranLanggananRepository::class,
    ];
}
