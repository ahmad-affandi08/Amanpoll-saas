<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Izin\ScopeLingkup;
use App\Domain\Kalibrasi\Application\Actions\KelolaJenisKalibrasi;
use App\Domain\Kalibrasi\Application\Actions\KelolaPelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Application\Actions\KelolaRencanaKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Pemeliharaan\Application\Actions\KelolaWaktuKerja;
use App\Domain\Pemeliharaan\Application\Actions\ResponsPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\TugaskanPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\UbahStatusPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\PreventifInspeksi\Application\Actions\JadwalkanPemeliharaanPreventif;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaButirDaftarPeriksa;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaInspeksi;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaPelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaRencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatInspeksi;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pemeliharaan preventif, inspeksi K3, dan kalibrasi perusahaan demo selama setahun.
 *
 * Templat daftar periksa dan rencana preventif disusun koordinator; perintah kerja
 * preventif lahir dari penjadwal resmi (`JadwalkanPemeliharaanPreventif`) yang
 * dijalankan tiap pagi dengan jam dimundurkan, lalu ditugaskan, dikerjakan,
 * diisi daftar periksanya, diverifikasi, dan ditutup lewat Action perintah kerja.
 * Inspeksi APAR, hydrant, dan panel berjalan lewat `KelolaInspeksi`; kalibrasi alat
 * ukur laboratorium lewat Action domain Kalibrasi dengan penyedia VND-007.
 * Tidak ada suku cadang yang diambil dari stok di seeder ini.
 */
final class DemoPreventifKalibrasiSeeder extends Seeder
{
    use KonteksDemo;

    /**
     * Templat daftar periksa: kode => [nama, jenis, kode kategori aset, kode model atau null,
     * email koordinator penyusun, butir]. Butir YaTidak selalu dirumuskan positif (Ya = sesuai).
     *
     * @var array<string, array{0: string, 1: string, 2: string|null, 3: string|null, 4: string, 5: list<array<string, mixed>>}>
     */
    private const TEMPLAT_DAFTAR_PERIKSA = [
        'DP-GEN' => ['PM Bulanan Genset', 'Pemeliharaan', 'KAT-GENSET', null, 'koordinator.teknik@amanpoll.test', [
            ['Kode' => 'GEN-01', 'Pertanyaan' => 'Level oli mesin di antara garis minimum dan maksimum', 'Tipe' => 'YaTidak', 'Catatan' => 'Level oli di bawah garis minimum, ditambah 4 liter SAE 15W-40.'],
            ['Kode' => 'GEN-02', 'Pertanyaan' => 'Level coolant radiator cukup dan tidak keruh', 'Tipe' => 'YaTidak', 'Catatan' => 'Coolant berkurang, ditambah dan dipantau kemungkinan rembes.'],
            ['Kode' => 'GEN-03', 'Pertanyaan' => 'Tegangan baterai starter', 'Tipe' => 'Angka', 'Satuan' => 'V', 'Min' => 25.5, 'Max' => 28.5, 'Arah' => 'bawah', 'Catatan' => 'Tegangan baterai starter rendah, perlu uji kapasitas baterai.'],
            ['Kode' => 'GEN-04', 'Pertanyaan' => 'Bebas rembesan oli, solar, dan air pendingin', 'Tipe' => 'YaTidak', 'Catatan' => 'Rembesan solar pada sambungan selang return, klem dikencangkan.'],
            ['Kode' => 'GEN-05', 'Pertanyaan' => 'Level tangki harian solar', 'Tipe' => 'Angka', 'Satuan' => '%', 'Min' => 50, 'Max' => 100, 'Arah' => 'bawah', 'Catatan' => 'Tangki harian di bawah 50%, diminta pengisian ke bagian umum.'],
            ['Kode' => 'GEN-06', 'Pertanyaan' => 'Uji jalan tanpa beban 15 menit berjalan normal', 'Tipe' => 'YaTidak', 'Catatan' => 'Mesin sulit start pada percobaan pertama.'],
            ['Kode' => 'GEN-07', 'Pertanyaan' => 'Frekuensi keluaran saat uji jalan', 'Tipe' => 'Angka', 'Satuan' => 'Hz', 'Min' => 49.5, 'Max' => 50.5, 'Arah' => 'atas', 'Catatan' => 'Frekuensi sedikit di atas batas, governor perlu disetel.'],
            ['Kode' => 'GEN-08', 'Pertanyaan' => 'Tegangan keluaran antarfasa', 'Tipe' => 'Angka', 'Satuan' => 'V', 'Min' => 370, 'Max' => 410, 'Arah' => 'atas', 'Catatan' => 'Tegangan keluaran di atas batas, AVR perlu diperiksa.'],
            ['Kode' => 'GEN-09', 'Pertanyaan' => 'Kondisi V-belt dan selang radiator', 'Tipe' => 'Pilihan', 'Pilihan' => ['Baik', 'Aus ringan', 'Perlu ganti'], 'Pemicu' => 'Perlu ganti', 'Catatan' => 'V-belt retak halus, diusulkan penggantian pada PM berikutnya.'],
            ['Kode' => 'GEN-10', 'Pertanyaan' => 'Catatan teknisi', 'Tipe' => 'Teks'],
        ]],
        'DP-KMP' => ['Servis Berkala Kompresor Udara', 'Pemeliharaan', 'KAT-KOMP', 'GA75VSD', 'koordinator.teknik@amanpoll.test', [
            ['Kode' => 'KMP-01', 'Pertanyaan' => 'Jam operasi saat servis', 'Tipe' => 'Angka', 'Satuan' => 'jam', 'Meter' => true, 'Contoh' => [18000, 26000]],
            ['Kode' => 'KMP-02', 'Pertanyaan' => 'Tekanan kerja jaringan udara', 'Tipe' => 'Angka', 'Satuan' => 'bar', 'Min' => 6.5, 'Max' => 7.5, 'Arah' => 'bawah', 'Catatan' => 'Tekanan kerja turun di bawah 6,5 bar saat beban puncak mesin blow.'],
            ['Kode' => 'KMP-03', 'Pertanyaan' => 'Suhu keluaran elemen kompresor', 'Tipe' => 'Angka', 'Satuan' => '°C', 'Min' => 70, 'Max' => 100, 'Arah' => 'atas', 'Catatan' => 'Suhu elemen tinggi, sirip cooler berdebu dan dibersihkan.'],
            ['Kode' => 'KMP-04', 'Pertanyaan' => 'Level oli kompresor pada kaca penduga normal', 'Tipe' => 'YaTidak', 'Catatan' => 'Level oli kompresor rendah, ditambah Roto-Inject.'],
            ['Kode' => 'KMP-05', 'Pertanyaan' => 'Indikator selisih tekanan filter udara', 'Tipe' => 'Pilihan', 'Pilihan' => ['Normal', 'Mendekati batas', 'Melebihi batas'], 'Pemicu' => 'Melebihi batas', 'Catatan' => 'Filter udara melewati batas selisih tekanan, diusulkan penggantian.'],
            ['Kode' => 'KMP-06', 'Pertanyaan' => 'Drain kondensat otomatis membuang air dengan baik', 'Tipe' => 'YaTidak', 'Catatan' => 'Auto drain macet, dibersihkan saringannya.'],
            ['Kode' => 'KMP-07', 'Pertanyaan' => 'Bebas kebocoran udara pada sambungan dan selang', 'Tipe' => 'YaTidak', 'Catatan' => 'Kebocoran udara di fitting outlet dryer, seal diganti.'],
        ]],
        'DP-CHL' => ['PM Bulanan Chiller', 'Pemeliharaan', 'KAT-AC', 'YK300', 'koordinator.teknik@amanpoll.test', [
            ['Kode' => 'CHL-01', 'Pertanyaan' => 'Suhu air dingin keluar (leaving)', 'Tipe' => 'Angka', 'Satuan' => '°C', 'Min' => 6, 'Max' => 8, 'Arah' => 'atas', 'Catatan' => 'Suhu air keluar di atas 8 °C saat beban siang.'],
            ['Kode' => 'CHL-02', 'Pertanyaan' => 'Suhu air dingin masuk (entering)', 'Tipe' => 'Angka', 'Satuan' => '°C', 'Min' => 11, 'Max' => 13.5, 'Arah' => 'atas', 'Catatan' => 'Suhu air balik tinggi, beban AHU produksi perlu dicek.'],
            ['Kode' => 'CHL-03', 'Pertanyaan' => 'Tekanan refrigeran sisi hisap', 'Tipe' => 'Angka', 'Satuan' => 'psi', 'Min' => 30, 'Max' => 50, 'Arah' => 'bawah', 'Catatan' => 'Tekanan hisap rendah, indikasi kekurangan refrigeran.'],
            ['Kode' => 'CHL-04', 'Pertanyaan' => 'Tekanan kondensor', 'Tipe' => 'Angka', 'Satuan' => 'psi', 'Min' => 110, 'Max' => 160, 'Arah' => 'atas', 'Catatan' => 'Tekanan kondensor tinggi, tube kondensor perlu dibersihkan.'],
            ['Kode' => 'CHL-05', 'Pertanyaan' => 'Arus kompresor fasa R', 'Tipe' => 'Angka', 'Satuan' => 'A', 'Min' => 250, 'Max' => 420, 'Arah' => 'atas', 'Catatan' => 'Arus kompresor mendekati batas, dipantau pada PM berikutnya.'],
            ['Kode' => 'CHL-06', 'Pertanyaan' => 'Pendekatan suhu kondensor masih dalam batas (tube bersih)', 'Tipe' => 'YaTidak', 'Catatan' => 'Approach kondensor naik, dijadwalkan brushing tube.'],
            ['Kode' => 'CHL-07', 'Pertanyaan' => 'Bebas kebocoran refrigeran (uji detektor)', 'Tipe' => 'YaTidak', 'Catatan' => 'Detektor mendeteksi kebocoran halus di flare valve service.'],
        ]],
        'DP-LFT' => ['PM Bulanan Lift', 'Pemeliharaan', 'KAT-LIFT', null, 'koordinator.teknik@amanpoll.test', [
            ['Kode' => 'LFT-01', 'Pertanyaan' => 'Rem mesin traksi menahan kabin dengan baik', 'Tipe' => 'YaTidak', 'Catatan' => 'Kampas rem mesin traksi mulai tipis, dijadwalkan penggantian.'],
            ['Kode' => 'LFT-02', 'Pertanyaan' => 'Kondisi tali baja (wire rope)', 'Tipe' => 'Pilihan', 'Pilihan' => ['Baik', 'Aus ringan', 'Perlu ganti'], 'Pemicu' => 'Perlu ganti', 'Catatan' => 'Ditemukan serabut putus pada salah satu wire rope.'],
            ['Kode' => 'LFT-03', 'Pertanyaan' => 'Pintu kabin dan sensor pintu (light curtain) berfungsi', 'Tipe' => 'YaTidak', 'Catatan' => 'Light curtain sesekali tidak mendeteksi, lensa dibersihkan.'],
            ['Kode' => 'LFT-04', 'Pertanyaan' => 'Selisih berhenti terhadap lantai (leveling)', 'Tipe' => 'Angka', 'Satuan' => 'mm', 'Min' => 0, 'Max' => 10, 'Arah' => 'atas', 'Catatan' => 'Leveling lantai 7 meleset lebih dari 10 mm, perlu penyetelan.'],
            ['Kode' => 'LFT-05', 'Pertanyaan' => 'Interkom dan bel alarm darurat berfungsi', 'Tipe' => 'YaTidak', 'Catatan' => 'Interkom kabin ke ruang kontrol tidak jernih.'],
            ['Kode' => 'LFT-06', 'Pertanyaan' => 'ARD (automatic rescue device) diuji dan berfungsi', 'Tipe' => 'YaTidak', 'Catatan' => 'Baterai ARD lemah, diusulkan penggantian.'],
            ['Kode' => 'LFT-07', 'Pertanyaan' => 'Kebersihan pit dan ruang mesin', 'Tipe' => 'Pilihan', 'Pilihan' => ['Bersih', 'Cukup', 'Kotor'], 'Pemicu' => 'Kotor', 'Catatan' => 'Pit lift kotor dan ada genangan air, dibersihkan.'],
        ]],
        'DP-FRK' => ['Servis Berkala Forklift 250 Jam', 'Pemeliharaan', 'KAT-FORK', '8FD25', 'koordinator.teknik@amanpoll.test', [
            ['Kode' => 'FRK-01', 'Pertanyaan' => 'Hour meter saat servis', 'Tipe' => 'Angka', 'Satuan' => 'jam', 'Meter' => true, 'Contoh' => [3500, 8500]],
            ['Kode' => 'FRK-02', 'Pertanyaan' => 'Level oli mesin normal setelah penggantian', 'Tipe' => 'YaTidak', 'Catatan' => 'Oli mesin kurang, ditambah.'],
            ['Kode' => 'FRK-03', 'Pertanyaan' => 'Level oli hidrolik normal', 'Tipe' => 'YaTidak', 'Catatan' => 'Oli hidrolik di bawah batas, ada rembesan di silinder tilt.'],
            ['Kode' => 'FRK-04', 'Pertanyaan' => 'Rem kaki dan rem tangan berfungsi baik', 'Tipe' => 'YaTidak', 'Catatan' => 'Rem tangan kurang menahan di tanjakan dock, disetel.'],
            ['Kode' => 'FRK-05', 'Pertanyaan' => 'Kondisi ban', 'Tipe' => 'Pilihan', 'Pilihan' => ['Baik', 'Aus sedang', 'Harus ganti'], 'Pemicu' => 'Harus ganti', 'Catatan' => 'Ban depan kanan aus melewati batas, diusulkan penggantian.'],
            ['Kode' => 'FRK-06', 'Pertanyaan' => 'Rantai mast dan garpu bebas retak', 'Tipe' => 'YaTidak', 'Catatan' => 'Rantai mast kering, dilumasi dan dipantau.'],
            ['Kode' => 'FRK-07', 'Pertanyaan' => 'Lampu, klakson, dan alarm mundur berfungsi', 'Tipe' => 'YaTidak', 'Catatan' => 'Alarm mundur mati, sekring diganti.'],
        ]],
        'DP-ACS' => ['PM Triwulanan AC Split', 'Pemeliharaan', 'KAT-AC', 'FTKC50', 'koordinator.teknik@amanpoll.test', [
            ['Kode' => 'ACS-01', 'Pertanyaan' => 'Filter udara dicuci dan bersih', 'Tipe' => 'YaTidak', 'Catatan' => 'Filter udara robek, perlu diganti baru.'],
            ['Kode' => 'ACS-02', 'Pertanyaan' => 'Suhu udara keluar evaporator', 'Tipe' => 'Angka', 'Satuan' => '°C', 'Min' => 10, 'Max' => 16, 'Arah' => 'atas', 'Catatan' => 'Udara keluar kurang dingin.'],
            ['Kode' => 'ACS-03', 'Pertanyaan' => 'Tekanan refrigeran R-32 sisi hisap', 'Tipe' => 'Angka', 'Satuan' => 'psi', 'Min' => 115, 'Max' => 150, 'Arah' => 'bawah', 'Catatan' => 'Tekanan R-32 rendah, dilakukan pengecekan kebocoran.'],
            ['Kode' => 'ACS-04', 'Pertanyaan' => 'Arus kompresor', 'Tipe' => 'Angka', 'Satuan' => 'A', 'Min' => 3, 'Max' => 9, 'Arah' => 'atas', 'Catatan' => 'Arus kompresor tinggi, kapasitor perlu diuji.'],
            ['Kode' => 'ACS-05', 'Pertanyaan' => 'Saluran pembuangan (drain) lancar', 'Tipe' => 'YaTidak', 'Catatan' => 'Drain tersumbat lendir, disedot dan dibilas.'],
            ['Kode' => 'ACS-06', 'Pertanyaan' => 'Kondisi unit outdoor', 'Tipe' => 'Pilihan', 'Pilihan' => ['Bersih', 'Berdebu', 'Korosi'], 'Pemicu' => 'Korosi', 'Catatan' => 'Rangka unit outdoor mulai korosi.'],
        ]],
        'DP-INJ' => ['PM Bulanan Mesin Injeksi', 'Pemeliharaan', 'KAT-MESIN', 'MA3800', 'koordinator.teknik@amanpoll.test', [
            ['Kode' => 'INJ-01', 'Pertanyaan' => 'Suhu oli hidrolik saat produksi', 'Tipe' => 'Angka', 'Satuan' => '°C', 'Min' => 35, 'Max' => 55, 'Arah' => 'atas', 'Catatan' => 'Suhu oli hidrolik tinggi, oil cooler perlu dibersihkan.'],
            ['Kode' => 'INJ-02', 'Pertanyaan' => 'Tekanan sistem hidrolik', 'Tipe' => 'Angka', 'Satuan' => 'bar', 'Min' => 150, 'Max' => 175, 'Arah' => 'bawah', 'Catatan' => 'Tekanan sistem turun, indikasi keausan pompa.'],
            ['Kode' => 'INJ-03', 'Pertanyaan' => 'Level oli hidrolik cukup', 'Tipe' => 'YaTidak', 'Catatan' => 'Oli hidrolik di bawah batas, ditambah ISO VG 46.'],
            ['Kode' => 'INJ-04', 'Pertanyaan' => 'Indikator filter oli hidrolik hijau (tidak buntu)', 'Tipe' => 'YaTidak', 'Catatan' => 'Indikator filter merah, diusulkan penggantian elemen filter.'],
            ['Kode' => 'INJ-05', 'Pertanyaan' => 'Semua zona heater barrel mencapai setelan', 'Tipe' => 'YaTidak', 'Catatan' => 'Zona heater 3 lambat naik, thermocouple dicek.'],
            ['Kode' => 'INJ-06', 'Pertanyaan' => 'Pelumasan toggle dan tie bar terisi', 'Tipe' => 'YaTidak', 'Catatan' => 'Pelumasan sentral toggle kosong, diisi grease.'],
            ['Kode' => 'INJ-07', 'Pertanyaan' => 'Tombol darurat dan safety gate berfungsi', 'Tipe' => 'YaTidak', 'Catatan' => 'Limit switch safety gate operator kendur, dikencangkan.'],
            ['Kode' => 'INJ-08', 'Pertanyaan' => 'Kondisi screw dan nozzle', 'Tipe' => 'Pilihan', 'Pilihan' => ['Baik', 'Aus ringan', 'Perlu overhaul'], 'Pemicu' => 'Perlu overhaul', 'Catatan' => 'Screw tip aus, bocor balik material, diusulkan overhaul.'],
        ]],
        'DP-SRV' => ['PM Triwulanan Server & Penyimpanan', 'Pemeliharaan', 'KAT-IT', null, 'koordinator.it@amanpoll.test', [
            ['Kode' => 'SRV-01', 'Pertanyaan' => 'Lampu status dan panel iDRAC/DSM tanpa peringatan kritis', 'Tipe' => 'YaTidak', 'Catatan' => 'Ada peringatan kipas #3 di iDRAC, dijadwalkan penggantian.'],
            ['Kode' => 'SRV-02', 'Pertanyaan' => 'Suhu inlet perangkat', 'Tipe' => 'Angka', 'Satuan' => '°C', 'Min' => 18, 'Max' => 27, 'Arah' => 'atas', 'Catatan' => 'Suhu inlet di atas 27 °C, aliran udara rak diperbaiki.'],
            ['Kode' => 'SRV-03', 'Pertanyaan' => 'Kapasitas penyimpanan terpakai', 'Tipe' => 'Angka', 'Satuan' => '%', 'Min' => 0, 'Max' => 80, 'Arah' => 'atas', 'Catatan' => 'Penyimpanan terpakai lebih dari 80%, diusulkan pembersihan arsip lama.'],
            ['Kode' => 'SRV-04', 'Pertanyaan' => 'Pencadangan harian 7 hari terakhir seluruhnya berhasil', 'Tipe' => 'YaTidak', 'Catatan' => 'Dua pencadangan harian gagal karena kuota target penuh.'],
            ['Kode' => 'SRV-05', 'Pertanyaan' => 'Firmware dan patch keamanan terbaru sudah terpasang', 'Tipe' => 'YaTidak', 'Catatan' => 'Patch BIOS terbaru belum terpasang, menunggu jendela pemeliharaan.'],
            ['Kode' => 'SRV-06', 'Pertanyaan' => 'Debu filter depan rak dibersihkan', 'Tipe' => 'YaTidak', 'Catatan' => 'Filter depan rak sangat berdebu.'],
        ]],
        'DP-UPS' => ['PM Bulanan UPS & AC Presisi Ruang Server', 'Pemeliharaan', null, null, 'koordinator.it@amanpoll.test', [
            ['Kode' => 'UPS-01', 'Pertanyaan' => 'Suhu ruang server', 'Tipe' => 'Angka', 'Satuan' => '°C', 'Min' => 18, 'Max' => 25, 'Arah' => 'atas', 'Catatan' => 'Suhu ruang server di atas 25 °C.'],
            ['Kode' => 'UPS-02', 'Pertanyaan' => 'Kelembapan ruang server', 'Tipe' => 'Angka', 'Satuan' => '%RH', 'Min' => 40, 'Max' => 60, 'Arah' => 'atas', 'Catatan' => 'Kelembapan tinggi, humidifier AC presisi dicek.'],
            ['Kode' => 'UPS-03', 'Pertanyaan' => 'Beban UPS', 'Tipe' => 'Angka', 'Satuan' => '%', 'Min' => 0, 'Max' => 75, 'Arah' => 'atas', 'Catatan' => 'Beban UPS melewati 75%, perlu redistribusi beban rak.'],
            ['Kode' => 'UPS-04', 'Pertanyaan' => 'Self-test baterai UPS lulus', 'Tipe' => 'YaTidak', 'Catatan' => 'Self-test baterai gagal pada string B.'],
            ['Kode' => 'UPS-05', 'Pertanyaan' => 'Filter udara AC presisi bersih', 'Tipe' => 'YaTidak', 'Catatan' => 'Filter AC presisi kotor, dicuci.'],
            ['Kode' => 'UPS-06', 'Pertanyaan' => 'Alarm dan notifikasi ke tim IT berfungsi', 'Tipe' => 'YaTidak', 'Catatan' => 'Notifikasi email UPS tidak terkirim, SMTP relay diperbaiki.'],
        ]],
        'DP-APAR' => ['Inspeksi Bulanan APAR', 'Inspeksi', 'KAT-APAR', null, 'koordinator.teknik@amanpoll.test', [
            ['Kode' => 'APAR-01', 'Pertanyaan' => 'Seluruh unit APAR pada set berada di posisinya', 'Tipe' => 'YaTidak', 'Catatan' => 'Satu unit APAR dipindahkan tanpa izin, dikembalikan ke posisi.'],
            ['Kode' => 'APAR-02', 'Pertanyaan' => 'Jarum manometer semua unit di zona hijau', 'Tipe' => 'YaTidak', 'Catatan' => 'Manometer unit APAR di zona merah.'],
            ['Kode' => 'APAR-03', 'Pertanyaan' => 'Segel dan pin pengaman utuh', 'Tipe' => 'YaTidak', 'Catatan' => 'Segel dan pin pengaman hilang.'],
            ['Kode' => 'APAR-04', 'Pertanyaan' => 'Selang dan nozzle tidak retak atau tersumbat', 'Tipe' => 'YaTidak', 'Catatan' => 'Selang APAR retak.'],
            ['Kode' => 'APAR-05', 'Pertanyaan' => 'Label petunjuk dan kartu inspeksi terpasang', 'Tipe' => 'YaTidak', 'Catatan' => 'Kartu inspeksi hilang, dipasang baru.'],
            ['Kode' => 'APAR-06', 'Pertanyaan' => 'Akses ke APAR tidak terhalang barang', 'Tipe' => 'YaTidak', 'Catatan' => 'Akses APAR terhalang palet, diminta dipindahkan.'],
            ['Kode' => 'APAR-07', 'Pertanyaan' => 'Masa berlaku isi ulang', 'Tipe' => 'Pilihan', 'Pilihan' => ['Berlaku', 'Habis < 3 bulan', 'Kedaluwarsa'], 'Pemicu' => 'Kedaluwarsa', 'Catatan' => 'Ada unit APAR yang masa isi ulangnya sudah lewat.'],
        ]],
        'DP-HYD' => ['Inspeksi Sistem Hydrant', 'Inspeksi', 'KAT-APAR', null, 'koordinator.teknik@amanpoll.test', [
            ['Kode' => 'HYD-01', 'Pertanyaan' => 'Tekanan statis jaringan hydrant', 'Tipe' => 'Angka', 'Satuan' => 'bar', 'Min' => 7, 'Max' => 10, 'Arah' => 'bawah', 'Catatan' => 'Tekanan statis jaringan di bawah 7 bar.'],
            ['Kode' => 'HYD-02', 'Pertanyaan' => 'Pompa jockey start dan stop otomatis', 'Tipe' => 'YaTidak', 'Catatan' => 'Pompa jockey tidak start otomatis.'],
            ['Kode' => 'HYD-03', 'Pertanyaan' => 'Uji jalan pompa diesel 30 menit normal', 'Tipe' => 'YaTidak', 'Catatan' => 'Pompa diesel hydrant sulit start.'],
            ['Kode' => 'HYD-04', 'Pertanyaan' => 'Level solar tangki pompa diesel', 'Tipe' => 'Angka', 'Satuan' => '%', 'Min' => 75, 'Max' => 100, 'Arah' => 'bawah', 'Catatan' => 'Solar tangki pompa diesel di bawah 75%.'],
            ['Kode' => 'HYD-05', 'Pertanyaan' => 'Box hydrant lengkap (selang, nozzle, kunci)', 'Tipe' => 'YaTidak', 'Catatan' => 'Nozzle di box hydrant H-07 hilang.'],
            ['Kode' => 'HYD-06', 'Pertanyaan' => 'Kondisi pilar hydrant dan katup', 'Tipe' => 'Pilihan', 'Pilihan' => ['Baik', 'Bocor ringan', 'Rusak'], 'Pemicu' => 'Rusak', 'Catatan' => 'Katup pilar hydrant rusak.'],
        ]],
        'DP-PNL' => ['Inspeksi Panel Listrik & Termografi', 'Inspeksi', 'KAT-PANEL', null, 'koordinator.teknik@amanpoll.test', [
            ['Kode' => 'PNL-01', 'Pertanyaan' => 'Suhu titik terpanas terminal (termografi)', 'Tipe' => 'Angka', 'Satuan' => '°C', 'Min' => 0, 'Max' => 65, 'Arah' => 'atas', 'Catatan' => 'Hotspot terminal melewati 65 °C.'],
            ['Kode' => 'PNL-02', 'Pertanyaan' => 'Arus beban tertinggi per fasa', 'Tipe' => 'Angka', 'Satuan' => 'A', 'Contoh' => [380, 1450]],
            ['Kode' => 'PNL-03', 'Pertanyaan' => 'Ketidakseimbangan beban antarfasa', 'Tipe' => 'Angka', 'Satuan' => '%', 'Min' => 0, 'Max' => 10, 'Arah' => 'atas', 'Catatan' => 'Ketidakseimbangan beban di atas 10%, perlu pemerataan beban.'],
            ['Kode' => 'PNL-04', 'Pertanyaan' => 'Pintu panel terkunci dan rambu bahaya terpasang', 'Tipe' => 'YaTidak', 'Catatan' => 'Kunci pintu panel rusak.'],
            ['Kode' => 'PNL-05', 'Pertanyaan' => 'Panel bebas debu, sarang hewan, dan kelembapan', 'Tipe' => 'YaTidak', 'Catatan' => 'Ditemukan sarang tikus di dasar panel.'],
            ['Kode' => 'PNL-06', 'Pertanyaan' => 'Lampu indikator dan alat ukur panel berfungsi', 'Tipe' => 'YaTidak', 'Catatan' => 'Lampu indikator fasa T mati.'],
            ['Kode' => 'PNL-07', 'Pertanyaan' => 'Pengencangan baut terminal sudah dilakukan', 'Tipe' => 'YaTidak', 'Catatan' => 'Pengencangan ditunda menunggu shutdown.'],
        ]],
    ];

    /**
     * Durasi kerja (menit) dan ringkasan penyelesaian per templat pemeliharaan.
     *
     * @var array<string, array{0: int, 1: string}>
     */
    private const PROFIL_PEKERJAAN = [
        'DP-GEN' => [90, 'Pemeriksaan oli, coolant, baterai, dan uji jalan tanpa beban 15 menit selesai. Genset siap operasi otomatis (ATS mode Auto).'],
        'DP-KMP' => [150, 'Servis berkala kompresor selesai: pembersihan cooler, pemeriksaan oli, filter, dan drain kondensat. Tekanan jaringan normal.'],
        'DP-CHL' => [150, 'PM chiller selesai: pencatatan parameter operasi, uji kebocoran refrigeran, dan pemeriksaan kondensor.'],
        'DP-LFT' => [120, 'PM bulanan lift selesai: rem, wire rope, pintu, leveling, interkom, dan ARD diperiksa. Lift kembali beroperasi normal.'],
        'DP-FRK' => [100, 'Servis 250 jam forklift selesai: ganti oli mesin, cek hidrolik, rem, ban, dan perlengkapan keselamatan.'],
        'DP-ACS' => [60, 'Cuci AC split selesai: filter, evaporator, dan drain dibersihkan; tekanan refrigeran dan arus kompresor normal.'],
        'DP-INJ' => [120, 'PM bulanan mesin injeksi selesai: sistem hidrolik, heater barrel, pelumasan, dan perangkat keselamatan diperiksa.'],
        'DP-SRV' => [75, 'PM server selesai: status perangkat keras, pencadangan, kapasitas penyimpanan, dan patch diperiksa.'],
        'DP-UPS' => [60, 'PM ruang server selesai: self-test UPS, suhu dan kelembapan ruang, serta filter AC presisi diperiksa.'],
    ];

    /**
     * Rencana preventif: kode, nama, templat, interval, satuan, strategi, ambang meter,
     * prioritas, toleransi hari, unit pengelola, koordinator, aset [kode, teknisi,
     * hari relatif jadwal terdekat terhadap hari ini, status bila dibiarkan terlambat].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: int, 4: string, 5: string, 6: int|null, 7: string, 8: int, 9: string, 10: string, 11: list<array{0: string, 1: string, 2: int, 3: string|null}>}>
     */
    private const RENCANA = [
        ['PM-GEN-01', 'PM Bulanan Genset', 'DP-GEN', 1, 'Bulan', 'Interval', null, 'Tinggi', 3, 'TEKFAS', 'koordinator.teknik@amanpoll.test', [
            ['UTL-GEN-01', 'teknisi.listrik@amanpoll.test', 4, null],
            ['UTL-GEN-02', 'teknisi.listrik@amanpoll.test', -6, 'Diterima'],
        ]],
        ['PM-KMP-01', 'Servis Kompresor 2.000 Jam', 'DP-KMP', 3, 'Bulan', 'Kombinasi', 2000, 'Tinggi', 7, 'TEKFAS', 'koordinator.teknik@amanpoll.test', [
            ['UTL-KMP-01', 'teknisi.teknik@amanpoll.test', 5, null],
            ['UTL-KMP-02', 'teknisi.teknik@amanpoll.test', -35, null],
        ]],
        ['PM-CHL-01', 'PM Bulanan Chiller', 'DP-CHL', 1, 'Bulan', 'Interval', null, 'Tinggi', 3, 'TEKFAS', 'koordinator.teknik@amanpoll.test', [
            ['UTL-CHL-01', 'teknisi.teknik@amanpoll.test', -1, null],
        ]],
        ['PM-LFT-01', 'PM Bulanan Lift', 'DP-LFT', 1, 'Bulan', 'Interval', null, 'Tinggi', 3, 'TEKFAS', 'koordinator.teknik@amanpoll.test', [
            ['GDG-LFT-01', 'teknisi.listrik@amanpoll.test', 0, null],
            ['GDG-LFT-02', 'teknisi.listrik@amanpoll.test', 2, null],
            ['GDG-LFT-03', 'teknisi.listrik@amanpoll.test', -12, null],
        ]],
        ['PM-FRK-01', 'Servis Forklift 250 Jam', 'DP-FRK', 6, 'Minggu', 'Kombinasi', 250, 'Normal', 5, 'TEKFAS', 'koordinator.teknik@amanpoll.test', [
            ['GDL-FRK-01', 'teknisi.teknik@amanpoll.test', -9, null],
            ['GDL-FRK-02', 'teknisi.teknik@amanpoll.test', 3, null],
            ['GDL-FRK-03', 'teknisi.teknik@amanpoll.test', -16, null],
        ]],
        ['PM-ACS-01', 'PM Triwulanan AC Split', 'DP-ACS', 3, 'Bulan', 'Interval', null, 'Normal', 7, 'TEKFAS', 'koordinator.teknik@amanpoll.test', [
            ['PRD-AC-01', 'teknisi.teknik@amanpoll.test', -2, null],
            ['GDL-AC-01', 'teknisi.teknik@amanpoll.test', -20, null],
            ['GDG-AC-01', 'teknisi.teknik@amanpoll.test', -50, null],
            ['GDG-AC-02', 'teknisi.teknik@amanpoll.test', 30, null],
            ['GDG-AC-03', 'teknisi.teknik@amanpoll.test', -3, 'Ditugaskan'],
        ]],
        ['PM-INJ-01', 'PM Bulanan Mesin Injeksi', 'DP-INJ', 1, 'Bulan', 'Interval', null, 'Tinggi', 3, 'TEKFAS', 'koordinator.teknik@amanpoll.test', [
            ['PRD-INJ-01', 'teknisi.teknik@amanpoll.test', 0, null],
            ['PRD-INJ-02', 'teknisi.teknik@amanpoll.test', -4, 'Diterima'],
            ['PRD-INJ-03', 'teknisi.listrik@amanpoll.test', 1, null],
            ['PRD-INJ-04', 'teknisi.listrik@amanpoll.test', 6, null],
        ]],
        ['PM-SRV-01', 'PM Triwulanan Server & Penyimpanan', 'DP-SRV', 3, 'Bulan', 'Interval', null, 'Tinggi', 7, 'IT', 'koordinator.it@amanpoll.test', [
            ['IT-SRV-01', 'teknisi.it@amanpoll.test', -8, null],
            ['IT-SRV-02', 'teknisi.it@amanpoll.test', 40, null],
            ['IT-NAS-01', 'teknisi.it@amanpoll.test', -5, 'Ditugaskan'],
        ]],
        ['PM-UPS-01', 'PM Bulanan UPS & AC Presisi Ruang Server', 'DP-UPS', 1, 'Bulan', 'Interval', null, 'Tinggi', 3, 'IT', 'koordinator.it@amanpoll.test', [
            ['GDG-UPS-01', 'teknisi.it@amanpoll.test', 0, null],
            ['GDG-AC-04', 'teknisi.it@amanpoll.test', 2, null],
        ]],
    ];

    /**
     * Jadwal preventif yang dibatalkan koordinator: kode aset => [kira-kira berapa hari lalu, alasan].
     *
     * @var array<string, array{0: int, 1: string}>
     */
    private const JADWAL_DIBATALKAN = [
        'UTL-GEN-02' => [200, 'Genset cadangan sedang overhaul top end oleh vendor; PM bulan ini digabung ke pekerjaan overhaul.'],
        'GDG-LFT-02' => [120, 'Lift #2 dihentikan untuk modernisasi panel kontrol oleh vendor; PM bulanan dibatalkan.'],
    ];

    /**
     * Templat inspeksi K3: kode, nama, kategori aset, templat daftar periksa, interval hari.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: int}>
     */
    private const TEMPLAT_INSPEKSI = [
        ['TI-APAR', 'Inspeksi Bulanan APAR', 'KAT-APAR', 'DP-APAR', 30],
        ['TI-HYD', 'Inspeksi Bulanan Sistem Hydrant', 'KAT-APAR', 'DP-HYD', 30],
        ['TI-PNL', 'Inspeksi Triwulanan Panel Listrik & Termografi', 'KAT-PANEL', 'DP-PNL', 90],
    ];

    /**
     * Aset yang diinspeksi: templat, kode aset, teknisi, hari relatif jadwal terdekat,
     * dan apakah jadwal terdekat yang sudah lewat dibiarkan belum dikerjakan.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: int, 4: bool}>
     */
    private const INSPEKSI_ASET = [
        ['TI-APAR', 'K3-APR-01', 'teknisi.teknik@amanpoll.test', -2, false],
        ['TI-APAR', 'K3-APR-02', 'teknisi.teknik@amanpoll.test', -6, true],
        ['TI-APAR', 'K3-APR-03', 'teknisi.listrik@amanpoll.test', 3, false],
        ['TI-HYD', 'K3-HYD-01', 'teknisi.teknik@amanpoll.test', -9, false],
        ['TI-PNL', 'UTL-PNL-01', 'teknisi.listrik@amanpoll.test', -15, false],
        ['TI-PNL', 'UTL-PNL-02', 'teknisi.listrik@amanpoll.test', 5, false],
        ['TI-PNL', 'UTL-TRF-01', 'teknisi.listrik@amanpoll.test', -40, false],
    ];

    /**
     * Temuan inspeksi yang disengaja: kode aset, kira-kira berapa hari lalu, butir tidak
     * sesuai, hasil, temuan, tindak lanjut, status perintah kerja korektif (null = tanpa PK).
     *
     * @var list<array{0: string, 1: int, 2: list<string>, 3: string, 4: string, 5: string, 6: string|null}>
     */
    private const TEMUAN_INSPEKSI = [
        ['K3-APR-02', 150, ['APAR-02', 'APAR-03'], 'Gagal',
            'Dua dari delapan unit APAR powder di rak bahan baku jarum manometernya di zona merah, satu unit kehilangan pin pengaman dan segel.',
            'Unit bermasalah ditarik dan diganti unit cadangan; isi ulang diajukan ke PT Proteksi Api Sentosa.', 'Selesai'],
        ['K3-HYD-01', 60, ['HYD-02'], 'Gagal',
            'Pompa jockey tidak start otomatis saat tekanan jaringan turun ke 6 bar; pressure switch macet.',
            'Pompa jockey dioperasikan manual sementara; dibuat perintah kerja korektif penggantian pressure switch.', 'Selesai'],
        ['UTL-PNL-01', 15, ['PNL-01', 'PNL-07'], 'PerluPerhatian',
            'Hotspot 78 °C pada terminal MCCB feeder kompresor #1 fasa S; baut terminal indikasi kendur.',
            'Pengencangan dan penggantian lug dijadwalkan saat shutdown mingguan; dibuat perintah kerja korektif.', 'Diterima'],
        ['K3-APR-03', 90, ['APAR-07'], 'PerluPerhatian',
            'Tiga unit APAR CO2 di lantai 8 Menara Sinar melewati masa isi ulang.',
            'Isi ulang diajukan bersamaan kunjungan vendor bulan berikutnya; unit cadangan dipasang sementara.', null],
    ];

    /**
     * Jenis kalibrasi dan titik ukurnya: kode => [nama, deskripsi, titik [nama, satuan, referensi, toleransi ±]].
     *
     * @var array<string, array{0: string, 1: string, 2: list<array{0: string, 1: string, 2: float, 3: float}>}>
     */
    private const JENIS_KALIBRASI = [
        'KAL-MASSA-AN' => ['Kalibrasi Timbangan Analitik', 'Kalibrasi massa dengan anak timbangan kelas E2 untuk timbangan laboratorium QA.', [
            ['Beban 10 g', 'g', 10, 0.0003], ['Beban 50 g', 'g', 50, 0.0003], ['Beban 100 g', 'g', 100, 0.0004], ['Beban 200 g', 'g', 200, 0.0005],
        ]],
        'KAL-MASSA-LT' => ['Kalibrasi Timbangan Lantai', 'Kalibrasi timbangan lantai gudang dengan anak timbangan kelas M1.', [
            ['Beban 500 kg', 'kg', 500, 0.5], ['Beban 1.000 kg', 'kg', 1000, 1], ['Beban 2.000 kg', 'kg', 2000, 2], ['Beban 3.000 kg', 'kg', 3000, 3],
        ]],
        'KAL-DIMENSI' => ['Kalibrasi Jangka Sorong', 'Kalibrasi dimensi dengan gauge block untuk pemeriksaan ketebalan preform dan tutup botol.', [
            ['Gauge block 10 mm', 'mm', 10, 0.02], ['Gauge block 50 mm', 'mm', 50, 0.02], ['Gauge block 100 mm', 'mm', 100, 0.03], ['Gauge block 150 mm', 'mm', 150, 0.03],
        ]],
        'KAL-LISTRIK' => ['Kalibrasi Multimeter', 'Kalibrasi besaran listrik dengan multifunction calibrator.', [
            ['Tegangan DC 10 V', 'V', 10, 0.01], ['Tegangan AC 230 V', 'V', 230, 1.5], ['Arus DC 1 A', 'A', 1, 0.012], ['Resistansi 10 kΩ', 'kΩ', 10, 0.02],
        ]],
        'KAL-SUHU' => ['Kalibrasi Oven Laboratorium', 'Pemetaan dan kalibrasi suhu oven pengering sampel resin.', [
            ['Setelan 60 °C', '°C', 60, 2], ['Setelan 105 °C', '°C', 105, 2], ['Setelan 150 °C', '°C', 150, 3],
        ]],
        'KAL-GAYA' => ['Kalibrasi Tensile Tester', 'Verifikasi gaya dengan proving ring sesuai ISO 7500-1 kelas 1.', [
            ['Gaya 100 N', 'N', 100, 1], ['Gaya 1.000 N', 'N', 1000, 5], ['Gaya 5.000 N', 'N', 5000, 25],
        ]],
        'KAL-TEKANAN' => ['Kalibrasi Pressure Gauge', 'Kalibrasi tekanan dengan dead weight tester untuk gauge master utilitas.', [
            ['Tekanan 5 bar', 'bar', 5, 0.25], ['Tekanan 10 bar', 'bar', 10, 0.25], ['Tekanan 15 bar', 'bar', 15, 0.25], ['Tekanan 20 bar', 'bar', 20, 0.25],
        ]],
    ];

    /**
     * Rencana kalibrasi: kode aset, jenis, interval hari, jatuh tempo awal (hari lalu),
     * pelaksanaan [hari lalu, hasil, catatan], pelaksanaan terjadwal [dibuat hari lalu, tanggal relatif] atau null.
     *
     * @var list<array{0: string, 1: string, 2: int, 3: int, 4: list<array{0: int, 1: string, 2: string}>, 5: array{0: int, 1: int}|null}>
     */
    private const RENCANA_KALIBRASI = [
        ['LAB-TMB-01', 'KAL-MASSA-AN', 365, 200, [[200, 'Lolos', 'Seluruh titik dalam toleransi; timbangan layak pakai untuk analisis kadar air resin.']], null],
        ['LAB-TMB-02', 'KAL-MASSA-LT', 365, 12, [], [8, 5]],
        ['LAB-JSR-01', 'KAL-DIMENSI', 180, 220, [
            [220, 'Lolos', 'Seluruh titik dalam toleransi.'],
            [40, 'Lolos', 'Seluruh titik dalam toleransi; rahang dibersihkan dari sisa resin.'],
        ], null],
        ['LAB-JSR-02', 'KAL-DIMENSI', 180, 345, [
            [345, 'Lolos', 'Seluruh titik dalam toleransi.'],
            [165, 'Lolos', 'Seluruh titik dalam toleransi.'],
        ], null],
        ['LAB-MLT-01', 'KAL-LISTRIK', 365, 90, [[90, 'Lolos', 'Seluruh besaran dalam spesifikasi pabrikan.']], null],
        ['LAB-OVN-01', 'KAL-SUHU', 365, 345, [[345, 'Lolos', 'Keseragaman suhu ruang oven baik pada ketiga setelan.']], null],
        ['LAB-TNS-01', 'KAL-GAYA', 180, 195, [[195, 'LolosDenganCatatan', 'Lolos, tetapi koreksi pada 5.000 N mendekati batas toleransi; load cell dipantau.']], [3, 4]],
        ['LAB-PRG-01', 'KAL-TEKANAN', 180, 280, [
            [280, 'Lolos', 'Seluruh titik dalam toleransi.'],
            [100, 'Gagal', 'Penyimpangan pada 15 dan 20 bar melebihi toleransi; gauge ditarik dari pemakaian untuk penyetelan.'],
            [86, 'Lolos', 'Kalibrasi ulang setelah penyetelan mekanisme bourdon; seluruh titik kembali dalam toleransi.'],
        ], null],
    ];

    /** Urutan status perintah kerja untuk menentukan sampai langkah mana siklusnya dijalankan. */
    private const TINGKAT_STATUS = [
        'Draf' => 0, 'Ditugaskan' => 1, 'Diterima' => 2, 'Dikerjakan' => 3,
        'MenungguVerifikasi' => 4, 'Selesai' => 5, 'Ditutup' => 6,
    ];

    private const LABORATORIUM = 'Laboratorium Kalibrasi PT Kalibrasi Presisi Indonesia, Bandung (terakreditasi KAN)';

    private CarbonImmutable $hariIni;

    private CarbonImmutable $sekarangNyata;

    /** @var array<string, true> */
    private array $hariLibur = [];

    /** @var array<string, list<array{definisi: array<string, mixed>, id: string}>> */
    private array $butirTemplat = [];

    private int $urutanSertifikat = 0;

    public function run(): void
    {
        $this->masukKonteks();

        if ($this->sudahDisemai('RencanaPemeliharaan')) {
            return;
        }

        mt_srand(46);
        $this->sekarangNyata = CarbonImmutable::now();
        $this->hariIni = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $this->hariLibur = DB::table('HariLibur')
            ->where('OrganisasiId', $this->organisasiId())
            ->pluck('Tanggal')
            ->mapWithKeys(fn ($tanggal): array => [substr((string) $tanggal, 0, 10) => true])
            ->all();

        $templat = $this->semaiTemplatDaftarPeriksa();
        $this->semaiRencanaPemeliharaan($templat);
        $this->jalankanPenjadwalHarian();
        $this->kerjakanPerintahKerjaPreventif();
        $this->semaiInspeksi($templat);
        $this->semaiKalibrasi();
    }

    /**
     * Templat daftar periksa beserta butirnya, disusun koordinator setahun lalu.
     *
     * @return array<string, string> kode templat => Id
     */
    private function semaiTemplatDaftarPeriksa(): array
    {
        $kelolaTemplat = app(KelolaTemplatDaftarPeriksa::class);
        $kelolaButir = app(KelolaButirDaftarPeriksa::class);
        $id = [];
        $menit = 0;

        foreach (self::TEMPLAT_DAFTAR_PERIKSA as $kode => [$nama, $jenis, $kategori, $model, $koordinator, $daftarButir]) {
            $id[$kode] = $this->padaWaktu($this->hariLalu(359, 9, $menit), function () use ($kelolaTemplat, $kelolaButir, $kode, $nama, $jenis, $kategori, $model, $koordinator, $daftarButir): string {
                $templat = $kelolaTemplat->buat([
                    'Kode' => $kode,
                    'Nama' => $nama,
                    'Jenis' => $jenis,
                    'KategoriAsetId' => $kategori === null ? null : $this->idDari('KategoriAset', ['Kode' => $kategori]),
                    'ModelAsetId' => $model === null ? null : $this->idDari('ModelAset', ['KodeModel' => $model]),
                ], $this->pengguna($koordinator));

                foreach ($daftarButir as $urutan => $butir) {
                    $tersimpan = $kelolaButir->simpan($templat, [
                        'Urutan' => $urutan + 1,
                        'Kode' => $butir['Kode'],
                        'Pertanyaan' => $butir['Pertanyaan'],
                        'TipeJawaban' => $butir['Tipe'],
                        'Satuan' => $butir['Satuan'] ?? null,
                        'Wajib' => $butir['Tipe'] !== 'Teks',
                        'NilaiMinimum' => $butir['Min'] ?? null,
                        'NilaiMaksimum' => $butir['Max'] ?? null,
                        'Pilihan' => $butir['Pilihan'] ?? null,
                        'MemicuTemuanJika' => match (true) {
                            $butir['Tipe'] === 'YaTidak' => ['nilai' => false],
                            isset($butir['Pemicu']) => ['nilai' => $butir['Pemicu']],
                            default => null,
                        },
                    ]);
                    $this->butirTemplat[$kode][] = ['definisi' => $butir, 'id' => $tersimpan->Id];
                }

                return $templat->Id;
            }, $koordinator);
            $menit += 12;
        }

        return $id;
    }

    /**
     * Rencana preventif dan aset terdaftar. Jadwal pertama tiap aset dihitung mundur dari
     * jadwal terdekatnya sehingga setelah setahun penjadwalan jatuh tepat di hari relatif itu.
     *
     * @param  array<string, string>  $templat
     */
    private function semaiRencanaPemeliharaan(array $templat): void
    {
        $kelolaRencana = app(KelolaRencanaPemeliharaan::class);
        $batasAwal = $this->hariIni->subDays(350);
        $tanggalMulai = $this->hariIni->subDays(358)->toDateString();

        foreach (self::RENCANA as $urutan => [$kode, $nama, $kodeTemplat, $interval, $satuan, $strategi, $ambangMeter, $prioritas, $toleransi, $unit, $koordinator, $daftarAset]) {
            $this->padaWaktu($this->hariLalu(358, 10, $urutan * 7), function () use ($kelolaRencana, $templat, $kode, $nama, $kodeTemplat, $interval, $satuan, $strategi, $ambangMeter, $prioritas, $toleransi, $unit, $koordinator, $daftarAset, $batasAwal, $tanggalMulai): void {
                $rencana = $kelolaRencana->buat([
                    'Kode' => $kode,
                    'Nama' => $nama,
                    'Jenis' => 'Preventif',
                    'TemplatDaftarPeriksaId' => $templat[$kodeTemplat],
                    'Prioritas' => $prioritas,
                    'StrategiJadwal' => $strategi,
                    'IntervalNilai' => $interval,
                    'IntervalSatuan' => $satuan,
                    'BerdasarkanMeter' => $ambangMeter !== null,
                    'AmbangMeter' => $ambangMeter,
                    'ToleransiHari' => $toleransi,
                    'BuatPerintahKerjaHariSebelum' => 7,
                    'UnitPengelolaId' => $this->idDari('UnitOrganisasi', ['Kode' => $unit]),
                ], $this->pengguna($koordinator));

                foreach ($daftarAset as [$kodeAset, , $hariRelatif]) {
                    $tanggal = $this->hariIni->addDays($hariRelatif);
                    while (($sebelumnya = $this->mundurInterval($tanggal, $interval, $satuan))->greaterThanOrEqualTo($batasAwal)) {
                        $tanggal = $sebelumnya;
                    }

                    $kelolaRencana->tetapkanAset(
                        $rencana,
                        $this->idDari('Aset', ['KodeAset' => $kodeAset]),
                        $tanggalMulai,
                        $tanggal->toDateString(),
                    );
                }
            }, $koordinator);
        }
    }

    /** Penjadwal preventif resmi dijalankan tiap pagi pukul 06.00 selama setahun, seperti cron. */
    private function jalankanPenjadwalHarian(): void
    {
        $penjadwal = app(JadwalkanPemeliharaanPreventif::class);
        $manajer = $this->pengguna('manajer.aset@amanpoll.test');

        for ($hariLalu = 357; $hariLalu >= 0; $hariLalu--) {
            $this->padaWaktu(
                $this->padaTanggal($this->hariIni->subDays($hariLalu), 6),
                fn (): array => $penjadwal->jalankan(organisasiId: $this->organisasiId(), penggunaId: $manajer),
                'manajer.aset@amanpoll.test',
            );
        }
    }

    /** Menjalankan siklus setiap perintah kerja preventif hasil penjadwal sesuai umur jadwalnya. */
    private function kerjakanPerintahKerjaPreventif(): void
    {
        $asetRencana = [];
        foreach (self::RENCANA as $rencana) {
            foreach ($rencana[11] as [$kodeAset, $teknisi, , $statusTerlambat]) {
                $asetRencana[$kodeAset] = [$teknisi, $rencana[10], $statusTerlambat, $rencana[2]];
            }
        }

        $daftarJadwal = DB::table('JadwalPemeliharaan as j')
            ->join('RencanaPemeliharaanAset as rpa', 'rpa.Id', '=', 'j.RencanaPemeliharaanAsetId')
            ->join('Aset as a', 'a.Id', '=', 'rpa.AsetId')
            ->where('j.OrganisasiId', $this->organisasiId())
            ->orderBy('j.TanggalJadwal')
            ->orderBy('a.KodeAset')
            ->get(['j.PerintahKerjaId', 'j.TanggalJadwal', 'a.KodeAset', 'a.Id as AsetId']);

        $dibatalkan = [];
        foreach (self::JADWAL_DIBATALKAN as $kodeAset => [$hariLaluTarget]) {
            $terdekat = $daftarJadwal
                ->where('KodeAset', $kodeAset)
                ->sortBy(fn ($jadwal): int => abs($this->hariLaluDari((string) $jadwal->TanggalJadwal) - $hariLaluTarget))
                ->first();
            if ($terdekat !== null) {
                $dibatalkan[(string) $terdekat->PerintahKerjaId] = $kodeAset;
            }
        }

        foreach ($daftarJadwal->values() as $indeks => $jadwal) {
            [$teknisi, $koordinator, $statusTerlambat, $kodeTemplat] = $asetRencana[$jadwal->KodeAset];
            $perintahKerjaId = (string) $jadwal->PerintahKerjaId;
            $tanggalJadwal = CarbonImmutable::parse((string) $jadwal->TanggalJadwal, 'Asia/Jakarta')->startOfDay();
            $hariLalu = $this->hariLaluDari((string) $jadwal->TanggalJadwal);

            if (isset($dibatalkan[$perintahKerjaId])) {
                $alasan = self::JADWAL_DIBATALKAN[$dibatalkan[$perintahKerjaId]][1];
                $this->padaWaktu(
                    $this->padaTanggal($this->hariKerja($tanggalJadwal->subDays(4), -1), 10, 5),
                    fn (): PerintahKerja => $this->ubahStatus($perintahKerjaId, StatusPerintahKerja::Dibatalkan, $alasan, null, $koordinator),
                    $koordinator,
                );

                continue;
            }

            $statusAkhir = match (true) {
                $hariLalu < -2 => 'Draf',
                $hariLalu < 0 => 'Ditugaskan',
                $hariLalu === 0 => 'Dikerjakan',
                $hariLalu <= 20 && $statusTerlambat !== null => $statusTerlambat,
                $hariLalu <= 2 => 'MenungguVerifikasi',
                $hariLalu <= 30 => 'Selesai',
                default => 'Ditutup',
            };

            $terlambatKerja = ($indeks % 9 === 4 && $hariLalu > 10) ? 2 + $indeks % 3 : 0;
            $tanggalKerja = $this->hariKerja($tanggalJadwal->addDays($terlambatKerja));
            if ($tanggalKerja->greaterThan($this->hariIni)) {
                $tanggalKerja = $this->hariIni;
            }

            $pelaksanaanId = DB::table('PelaksanaanDaftarPeriksa')->where('PerintahKerjaId', $perintahKerjaId)->value('Id');
            $tidakSesuai = [];
            if ($indeks % 8 === 5) {
                $kandidat = $this->butirDapatDinilai($kodeTemplat);
                $tidakSesuai[] = $kandidat[intdiv($indeks, 8) % count($kandidat)];
            }
            $waktuPeriksa = $this->waktuLokal($tanggalKerja, 11);
            $jawaban = $this->susunJawaban($kodeTemplat, $tidakSesuai, (string) $jadwal->AsetId, $waktuPeriksa);

            [$durasi, $ringkasan] = self::PROFIL_PEKERJAAN[$kodeTemplat];
            foreach ($tidakSesuai as $kodeButir) {
                $ringkasan .= ' Temuan: '.$this->catatanButir($kodeTemplat, $kodeButir).' Sudah dilaporkan ke koordinator untuk tindak lanjut.';
            }

            $this->jalankanSiklus(
                perintahKerjaId: $perintahKerjaId,
                teknisi: $teknisi,
                koordinator: $koordinator,
                statusAkhir: $statusAkhir,
                tanggalTugas: $this->hariKerja($tanggalJadwal->subDays(3), -1),
                tanggalKerja: $tanggalKerja,
                durasiMenit: $durasi + ($indeks % 4) * 10,
                ringkasan: $ringkasan,
                pelaksanaanId: is_string($pelaksanaanId) ? $pelaksanaanId : null,
                jawaban: $jawaban,
                indeks: $indeks,
                catatanMulai: 'Mulai pemeliharaan preventif sesuai daftar periksa.',
            );
        }
    }

    /**
     * Satu siklus perintah kerja: ditugaskan koordinator, diterima dan dikerjakan teknisi
     * (daftar periksa dan waktu kerja dicatat), diverifikasi, lalu ditutup koordinator.
     * Berhenti di `$statusAkhir`; langkah yang jatuh sesudah hari ini tidak dijalankan.
     *
     * @param  list<array<string, mixed>>  $jawaban
     */
    private function jalankanSiklus(
        string $perintahKerjaId,
        string $teknisi,
        string $koordinator,
        string $statusAkhir,
        CarbonImmutable $tanggalTugas,
        CarbonImmutable $tanggalKerja,
        int $durasiMenit,
        string $ringkasan,
        ?string $pelaksanaanId,
        array $jawaban,
        int $indeks,
        string $catatanMulai,
    ): void {
        $tingkat = self::TINGKAT_STATUS[$statusAkhir];
        $teknisiId = $this->pengguna($teknisi);

        if ($tingkat < 1) {
            return;
        }

        $waktuTugas = $this->padaTanggal($tanggalTugas, 8, 10 + ($indeks % 5) * 9);
        $this->padaWaktu($waktuTugas, function () use ($perintahKerjaId, $teknisiId, $koordinator): void {
            app(TugaskanPerintahKerja::class)->jalankan($this->perintahKerja($perintahKerjaId), [$teknisiId], 'Ketua', false, $this->pengguna($koordinator));
        }, $koordinator);

        if ($tingkat < 2) {
            return;
        }

        $this->padaWaktu($this->batasi($waktuTugas->addMinutes(25 + ($indeks % 4) * 15)), function () use ($perintahKerjaId, $teknisiId): void {
            $penugasan = PenugasanPerintahKerja::query()
                ->where('PerintahKerjaId', $perintahKerjaId)
                ->where('PenggunaId', $teknisiId)
                ->where('Status', StatusPenugasanPerintahKerja::Ditugaskan->value)
                ->firstOrFail();
            app(ResponsPenugasanPerintahKerja::class)->jalankan($penugasan, 'Terima', 'Siap dikerjakan sesuai jadwal.', $teknisiId);
        }, $teknisi);

        if ($tingkat < 3) {
            return;
        }

        $mulai = $this->slotKosong($teknisiId, $this->waktuLokal($tanggalKerja, 8 + $indeks % 3, ($indeks % 2) * 30), $durasiMenit);
        $selesai = $mulai->addMinutes($durasiMenit);
        $belumSelesai = $tingkat === 3 || $selesai->addMinutes(10)->greaterThan($this->batasSekarang());

        $this->padaWaktu($this->batasi($mulai), fn (): PerintahKerja => $this->ubahStatus($perintahKerjaId, StatusPerintahKerja::Dikerjakan, $catatanMulai, null, $teknisi), $teknisi);

        if ($belumSelesai) {
            if ($pelaksanaanId !== null && $jawaban !== []) {
                $sebagian = array_slice($jawaban, 0, (int) ceil(count($jawaban) / 2));
                $this->padaWaktu($this->batasi($mulai->addMinutes(20)), function () use ($pelaksanaanId, $sebagian, $teknisiId): void {
                    app(KelolaPelaksanaanDaftarPeriksa::class)->simpanJawaban(PelaksanaanDaftarPeriksa::query()->findOrFail($pelaksanaanId), $sebagian, $teknisiId);
                }, $teknisi);
            }

            return;
        }

        if ($pelaksanaanId !== null && $jawaban !== []) {
            $this->padaWaktu($selesai->subMinutes(10), function () use ($pelaksanaanId, $jawaban, $teknisiId): void {
                app(KelolaPelaksanaanDaftarPeriksa::class)->simpanJawaban(PelaksanaanDaftarPeriksa::query()->findOrFail($pelaksanaanId), $jawaban, $teknisiId);
            }, $teknisi);
            $this->padaWaktu($selesai->subMinutes(5), function () use ($pelaksanaanId, $teknisiId): void {
                app(KelolaPelaksanaanDaftarPeriksa::class)->finalisasi(PelaksanaanDaftarPeriksa::query()->findOrFail($pelaksanaanId), 'Daftar periksa diisi di lokasi.', $teknisiId);
            }, $teknisi);
        }

        $this->padaWaktu($selesai->addMinutes(2), function () use ($perintahKerjaId, $teknisiId, $mulai, $selesai): void {
            app(KelolaWaktuKerja::class)->catatSelesai($this->perintahKerja($perintahKerjaId), $teknisiId, $mulai, $selesai, 'Pengerjaan di lokasi aset.');
        }, $teknisi);
        $this->padaWaktu($selesai->addMinutes(5), fn (): PerintahKerja => $this->ubahStatus($perintahKerjaId, StatusPerintahKerja::MenungguVerifikasi, 'Pekerjaan selesai, mohon verifikasi.', $ringkasan, $teknisi), $teknisi);

        $tanggalVerifikasi = $this->hariKerja($tanggalKerja->addDay());
        if ($tingkat < 5 || $tanggalVerifikasi->greaterThan($this->hariIni)) {
            return;
        }

        $this->padaWaktu($this->padaTanggal($tanggalVerifikasi, 9, 5 + ($indeks % 4) * 12), fn (): PerintahKerja => $this->ubahStatus($perintahKerjaId, StatusPerintahKerja::Selesai, 'Hasil pekerjaan diperiksa dan diverifikasi.', null, $koordinator), $koordinator);

        $tanggalTutup = $this->hariKerja($tanggalVerifikasi->addDays(2));
        if ($tingkat < 6 || $tanggalTutup->greaterThan($this->hariIni)) {
            return;
        }

        $this->padaWaktu($this->padaTanggal($tanggalTutup, 15, 20 + $indeks % 30), fn (): PerintahKerja => $this->ubahStatus($perintahKerjaId, StatusPerintahKerja::Ditutup, 'Dokumen pekerjaan lengkap, ditutup.', null, $koordinator), $koordinator);
    }

    /**
     * Inspeksi K3 berkala: dijadwalkan koordinator lima hari sebelumnya, dilaksanakan teknisi
     * dengan daftar periksa, dan temuan berat ditindaklanjuti perintah kerja korektif.
     *
     * @param  array<string, string>  $templatDaftarPeriksa
     */
    private function semaiInspeksi(array $templatDaftarPeriksa): void
    {
        $kelolaInspeksi = app(KelolaInspeksi::class);
        $koordinator = 'koordinator.teknik@amanpoll.test';
        $templatInspeksi = [];

        foreach (self::TEMPLAT_INSPEKSI as $urutan => [$kode, $nama, $kategori, $kodeDaftarPeriksa, $interval]) {
            $templatInspeksi[$kode] = $this->padaWaktu($this->hariLalu(359, 14, $urutan * 10), fn (): TemplatInspeksi => $kelolaInspeksi->buatTemplat([
                'Kode' => $kode,
                'Nama' => $nama,
                'KategoriAsetId' => $this->idDari('KategoriAset', ['Kode' => $kategori]),
                'TemplatDaftarPeriksaId' => $templatDaftarPeriksa[$kodeDaftarPeriksa],
                'IntervalHari' => $interval,
            ], $this->pengguna($koordinator)), $koordinator);
        }

        $jadwal = [];
        foreach (self::INSPEKSI_ASET as [$kodeTemplat, $kodeAset, $teknisi, $hariRelatif, $terlambat]) {
            $interval = $templatInspeksi[$kodeTemplat]->IntervalHari;
            for ($tanggal = $this->hariIni->addDays($hariRelatif); $tanggal->greaterThanOrEqualTo($this->hariIni->subDays(345)); $tanggal = $tanggal->subDays($interval)) {
                if ($tanggal->greaterThan($this->hariIni->addDays(7))) {
                    continue;
                }
                $jadwal[] = [
                    'tanggal' => $tanggal,
                    'templat' => $kodeTemplat,
                    'aset' => $kodeAset,
                    'teknisi' => $teknisi,
                    'dibiarkan' => $terlambat && $tanggal->equalTo($this->hariIni->addDays($hariRelatif)),
                ];
            }
        }
        usort($jadwal, fn (array $a, array $b): int => [$a['tanggal'], $a['aset']] <=> [$b['tanggal'], $b['aset']]);

        $temuanTerpilih = [];
        foreach (self::TEMUAN_INSPEKSI as $urutan => $temuan) {
            $terdekat = null;
            foreach ($jadwal as $posisi => $satu) {
                if ($satu['aset'] !== $temuan[0]) {
                    continue;
                }
                $selisih = abs((int) $satu['tanggal']->diffInDays($this->hariIni) - $temuan[1]);
                if ($terdekat === null || $selisih < $terdekat[1]) {
                    $terdekat = [$posisi, $selisih];
                }
            }
            if ($terdekat !== null) {
                $temuanTerpilih[$terdekat[0]] = $urutan;
            }
        }

        foreach ($jadwal as $indeks => $satu) {
            $tanggal = $satu['tanggal'];
            $asetId = $this->idDari('Aset', ['KodeAset' => $satu['aset']]);
            $teknisiId = $this->pengguna($satu['teknisi']);
            $kodeDaftarPeriksa = collect(self::TEMPLAT_INSPEKSI)->firstWhere(0, $satu['templat'])[3];

            $inspeksi = $this->padaWaktu(
                $this->padaTanggal($this->hariKerja($tanggal->subDays(5), -1), 8, 30 + $indeks % 20),
                fn (): Inspeksi => $kelolaInspeksi->jadwalkan([
                    'TemplatInspeksiId' => $templatInspeksi[$satu['templat']]->Id,
                    'AsetId' => $asetId,
                    'DijadwalkanPada' => $this->waktuLokal($tanggal, 8),
                    'DilaksanakanOleh' => $teknisiId,
                ], $this->pengguna($koordinator)),
                $koordinator,
            );

            $tanggalPelaksanaan = $this->hariKerja($tanggal);
            if ($satu['dibiarkan'] || $tanggalPelaksanaan->greaterThanOrEqualTo($this->hariIni)) {
                continue;
            }

            $temuan = isset($temuanTerpilih[$indeks]) ? self::TEMUAN_INSPEKSI[$temuanTerpilih[$indeks]] : null;
            $tidakSesuai = $temuan[2] ?? [];
            if ($temuan === null && $indeks % 8 === 5) {
                $kandidat = $this->butirDapatDinilai($kodeDaftarPeriksa);
                $tidakSesuai = [$kandidat[intdiv($indeks, 8) % count($kandidat)]];
            }

            $mulai = $this->waktuLokal($tanggalPelaksanaan, 9 + $indeks % 5, ($indeks % 3) * 15);
            $jawaban = $this->susunJawaban($kodeDaftarPeriksa, $tidakSesuai, $asetId, $mulai);
            $pelaksanaanId = (string) $inspeksi->PelaksanaanDaftarPeriksaId;

            $this->padaWaktu($mulai->addMinutes(35), function () use ($pelaksanaanId, $jawaban, $teknisiId): void {
                $pelaksanaan = PelaksanaanDaftarPeriksa::query()->findOrFail($pelaksanaanId);
                app(KelolaPelaksanaanDaftarPeriksa::class)->simpanJawaban($pelaksanaan, $jawaban, $teknisiId);
                app(KelolaPelaksanaanDaftarPeriksa::class)->finalisasi($pelaksanaan, null, $teknisiId);
            }, $satu['teknisi']);

            $temuanTeks = $temuan[4] ?? implode(' ', array_map(fn (string $kode): string => $this->catatanButir($kodeDaftarPeriksa, $kode), $tidakSesuai));
            $hasil = $temuan[3] ?? ($tidakSesuai === [] ? 'Lolos' : 'PerluPerhatian');

            $this->padaWaktu($mulai->addMinutes(40), fn (): Inspeksi => $kelolaInspeksi->laksanakan($inspeksi, [
                'Hasil' => $hasil,
                'Temuan' => $tidakSesuai === [] ? null : $temuanTeks,
                'TindakLanjut' => $temuan[5] ?? ($tidakSesuai === [] ? null : 'Diperbaiki langsung di lokasi oleh teknisi.'),
            ], $teknisiId), $satu['teknisi']);

            if (($temuan[6] ?? null) === null) {
                continue;
            }

            $waktuKorektif = $mulai->addMinutes(90);
            $perintahKerja = $this->padaWaktu($waktuKorektif, fn (): PerintahKerja => $kelolaInspeksi->buatPerintahKerjaKorektif(Inspeksi::query()->findOrFail($inspeksi->Id), [
                'Judul' => 'Tindak lanjut inspeksi '.$satu['aset'].': '.mb_strimwidth((string) $temuan[4], 0, 90, '…'),
                'Prioritas' => 'Tinggi',
            ], $this->pengguna($koordinator)), $koordinator);

            $statusKorektif = $temuan[6];
            if ($statusKorektif === 'Selesai' && $this->hariIni->diffInDays($tanggalPelaksanaan, true) > 30) {
                $statusKorektif = 'Ditutup';
            }

            $this->jalankanSiklus(
                perintahKerjaId: $perintahKerja->Id,
                teknisi: $satu['teknisi'],
                koordinator: $koordinator,
                statusAkhir: $statusKorektif,
                tanggalTugas: $tanggalPelaksanaan,
                tanggalKerja: $this->hariKerja($tanggalPelaksanaan->addDay()),
                durasiMenit: 150,
                ringkasan: 'Tindak lanjut temuan inspeksi selesai: '.lcfirst((string) $temuan[5]).' Sistem diuji ulang dan berfungsi normal.',
                pelaksanaanId: null,
                jawaban: [],
                indeks: $indeks,
                catatanMulai: 'Mulai perbaikan temuan inspeksi.',
            );
        }
    }

    /** Jenis kalibrasi, titik ukur, rencana untuk alat ukur LAB-*, dan riwayat pelaksanaannya. */
    private function semaiKalibrasi(): void
    {
        $petugas = 'kalibrasi@amanpoll.test';
        $petugasId = $this->pengguna($petugas);
        $kelolaJenis = app(KelolaJenisKalibrasi::class);
        $kelolaRencana = app(KelolaRencanaKalibrasi::class);
        $kategoriUkur = $this->idDari('KategoriAset', ['Kode' => 'KAT-UKUR']);
        $penyedia = $this->idDari('Penyedia', ['Kode' => 'VND-007']);
        $unitTekfas = $this->idDari('UnitOrganisasi', ['Kode' => 'TEKFAS']);

        $jenis = [];
        $urutan = 0;
        foreach (self::JENIS_KALIBRASI as $kode => [$nama, $deskripsi, $daftarTitik]) {
            $jenis[$kode] = $this->padaWaktu($this->hariLalu(357, 9, $urutan++ * 8), function () use ($kelolaJenis, $kode, $nama, $deskripsi, $daftarTitik, $kategoriUkur, $petugasId): JenisKalibrasi {
                $satu = $kelolaJenis->buat(['Kode' => $kode, 'Nama' => $nama, 'Deskripsi' => $deskripsi], $petugasId);
                foreach ($daftarTitik as $posisi => [$namaTitik, $satuan, $referensi, $toleransi]) {
                    $kelolaJenis->tambahTitikUkur($satu, [
                        'KategoriAsetId' => $kategoriUkur,
                        'Nama' => $namaTitik,
                        'Satuan' => $satuan,
                        'NilaiReferensi' => $referensi,
                        'ToleransiMinus' => $toleransi,
                        'ToleransiPlus' => $toleransi,
                        'Urutan' => $posisi + 1,
                    ], $petugasId);
                }

                return $satu;
            }, $petugas);
        }

        // Pelaksanaan diurutkan menurut waktu penjadwalannya lintas aset: nomor dokumen
        // Kalibrasi direset tiap tahun, jadi memanggilnya mundur-maju melintasi pergantian
        // tahun menghasilkan nomor ganda.
        $antrean = [];
        foreach (self::RENCANA_KALIBRASI as $posisi => [$kodeAset, $kodeJenis, $interval, $jatuhTempoAwal, $riwayat, $terjadwal]) {
            $asetId = $this->idDari('Aset', ['KodeAset' => $kodeAset]);
            $rencana = $this->padaWaktu($this->hariLalu(356, 10, $posisi * 6), fn (): RencanaKalibrasi => $kelolaRencana->buat([
                'AsetId' => $asetId,
                'JenisKalibrasiId' => $jenis[$kodeJenis]->Id,
                'PenyediaId' => $penyedia,
                'IntervalHari' => $interval,
                'TanggalMulai' => $this->hariIni->subDays($jatuhTempoAwal + $interval)->toDateString(),
                'TanggalBerikutnya' => $this->hariIni->subDays($jatuhTempoAwal)->toDateString(),
                'PeringatanHariSebelum' => 30,
                'UnitPengelolaId' => $unitTekfas,
            ], $petugasId), $petugas);

            foreach ($riwayat as [$hariLalu, $hasil, $catatan]) {
                $tanggal = $this->hariKerja($this->hariIni->subDays($hariLalu));
                $antrean[] = [$this->hariKerja($tanggal->subDays(7), -1), fn () => $this->laksanakanKalibrasi($rencana, $asetId, $tanggal, $hasil, $catatan, $petugas)];
            }

            if ($terjadwal !== null) {
                [$dibuatHariLalu, $tanggalRelatif] = $terjadwal;
                $dibuat = $this->hariKerja($this->hariIni->subDays($dibuatHariLalu), -1);
                $antrean[] = [$dibuat, fn () => $this->padaWaktu($this->padaTanggal($dibuat, 10, 15), fn (): PelaksanaanKalibrasi => app(KelolaPelaksanaanKalibrasi::class)->jadwalkan([
                    'AsetId' => $asetId,
                    'RencanaKalibrasiId' => $rencana->Id,
                    'TanggalKalibrasi' => $this->hariKerja($this->hariIni->addDays($tanggalRelatif))->toDateString(),
                    'Laboratorium' => self::LABORATORIUM,
                    'Catatan' => 'Menunggu jadwal kunjungan teknisi laboratorium ke pabrik Cikarang.',
                    'DilaksanakanOleh' => $petugasId,
                ], $petugasId), $petugas)];
            }
        }

        usort($antrean, fn (array $a, array $b): int => $a[0] <=> $b[0]);
        foreach ($antrean as [, $pekerjaan]) {
            $pekerjaan();
        }
    }

    /** Satu pelaksanaan kalibrasi: dijadwalkan sepekan sebelumnya, hasil titik ukur dan sertifikat dicatat empat hari sesudahnya. */
    private function laksanakanKalibrasi(RencanaKalibrasi $rencana, string $asetId, CarbonImmutable $tanggal, string $hasil, string $catatan, string $petugas): void
    {
        $petugasId = $this->pengguna($petugas);
        $kelolaPelaksanaan = app(KelolaPelaksanaanKalibrasi::class);

        $pelaksanaan = $this->padaWaktu($this->padaTanggal($this->hariKerja($tanggal->subDays(7), -1), 10, 30), fn (): PelaksanaanKalibrasi => $kelolaPelaksanaan->jadwalkan([
            'AsetId' => $asetId,
            'RencanaKalibrasiId' => $rencana->Id,
            'TanggalKalibrasi' => $tanggal->toDateString(),
            'Laboratorium' => self::LABORATORIUM,
            'DilaksanakanOleh' => $petugasId,
        ], $petugasId), $petugas);

        $titikUkur = DB::table('TitikUkurKalibrasi')
            ->where('JenisKalibrasiId', $rencana->JenisKalibrasiId)
            ->get(['Id', 'Urutan', 'ToleransiPlus'])
            ->keyBy('Id');
        $daftarTitik = $pelaksanaan->hasilTitikUkur()->get()
            ->sortBy(fn ($titik): int => (int) ($titikUkur[$titik->TitikUkurKalibrasiId]->Urutan ?? 0))
            ->values();

        $gagal = $hasil === 'Gagal';
        $daftarHasil = [];
        foreach ($daftarTitik as $posisi => $titik) {
            $referensi = (float) $titik->NilaiReferensi;
            $toleransi = (float) ($titikUkur[$titik->TitikUkurKalibrasiId]->ToleransiPlus ?? 0);
            $faktor = $gagal && $posisi >= 2
                ? 1.3 + 0.3 * $posisi
                : (mt_rand(-60, 60) / 100) * ($hasil === 'LolosDenganCatatan' && $posisi === 2 ? 1.5 : 1);
            $desimal = $toleransi < 0.01 ? 5 : ($toleransi < 1 ? 3 : 2);

            $daftarHasil[] = [
                'Id' => $titik->Id,
                'TitikUkurKalibrasiId' => $titik->TitikUkurKalibrasiId,
                'NamaTitik' => $titik->NamaTitik,
                'NilaiTerukur' => round($referensi + $toleransi * $faktor, $desimal),
                'Ketidakpastian' => round($toleransi * 0.3, $desimal),
                'Satuan' => $titik->Satuan,
            ];
        }

        $waktuHasil = $this->padaTanggal($this->hariKerja($tanggal->addDays(4)), 14, 10);
        $this->padaWaktu($waktuHasil, fn () => $kelolaPelaksanaan->simpanHasilTitikUkur($pelaksanaan, $daftarHasil, $petugasId), $petugas);
        $this->padaWaktu($waktuHasil->addMinutes(15), fn (): PelaksanaanKalibrasi => $kelolaPelaksanaan->finalisasi($pelaksanaan->fresh() ?? $pelaksanaan, [
            'Hasil' => $hasil,
            'NomorSertifikat' => sprintf('KPI/CAL/%s/%04d', $tanggal->format('Y/m'), 212 + (++$this->urutanSertifikat) * 17),
            'TanggalKalibrasi' => $tanggal->toDateString(),
            'Laboratorium' => self::LABORATORIUM,
            'KondisiLingkungan' => ['Suhu' => sprintf('%.1f °C', 22.5 + mt_rand(0, 15) / 10), 'Kelembapan' => sprintf('%d %%RH', mt_rand(48, 60))],
            'Catatan' => $catatan,
        ], $petugasId), $petugas);
    }

    /**
     * Jawaban daftar periksa: nilai wajar di tengah rentang, kecuali butir yang sengaja tidak sesuai.
     *
     * @param  list<string>  $tidakSesuai
     * @return list<array<string, mixed>>
     */
    private function susunJawaban(string $kodeTemplat, array $tidakSesuai, string $asetId, CarbonImmutable $waktu): array
    {
        $jawaban = [];
        foreach ($this->butirTemplat[$kodeTemplat] as ['definisi' => $butir, 'id' => $butirId]) {
            $temuan = in_array($butir['Kode'], $tidakSesuai, true);
            $satu = ['ButirTemplatDaftarPeriksaId' => $butirId];

            switch ($butir['Tipe']) {
                case 'YaTidak':
                    $satu['NilaiBoolean'] = ! $temuan;
                    break;
                case 'Pilihan':
                    $satu['NilaiTeks'] = $temuan ? $butir['Pemicu'] : $butir['Pilihan'][mt_rand(0, 9) < 8 ? 0 : 1];
                    break;
                case 'Angka':
                    $satu['NilaiAngka'] = $this->nilaiAngka($butir, $temuan, $asetId, $waktu);
                    break;
                default:
                    if (mt_rand(0, 3) === 0) {
                        $satu['NilaiTeks'] = 'Area kerja dibersihkan kembali setelah pekerjaan selesai.';
                    } else {
                        continue 2;
                    }
            }

            if ($temuan) {
                $satu['Catatan'] = $butir['Catatan'] ?? null;
            }
            $jawaban[] = $satu;
        }

        return $jawaban;
    }

    /** @param array<string, mixed> $butir */
    private function nilaiAngka(array $butir, bool $temuan, string $asetId, CarbonImmutable $waktu): float
    {
        if (! isset($butir['Min'], $butir['Max'])) {
            if (($butir['Meter'] ?? false) === true) {
                $nilai = DB::table('PembacaanMeterAset as p')
                    ->join('MeterAset as m', 'm.Id', '=', 'p.MeterAsetId')
                    ->where('m.AsetId', $asetId)
                    ->where('p.DibacaPada', '<=', $waktu)
                    ->orderByDesc('p.DibacaPada')
                    ->value('p.Nilai');
                if ($nilai !== null) {
                    return (float) $nilai + mt_rand(8, 90);
                }
            }
            [$bawah, $atas] = $butir['Contoh'];

            return (float) mt_rand((int) $bawah, (int) $atas);
        }

        $min = (float) $butir['Min'];
        $max = (float) $butir['Max'];
        $rentang = $max - $min;
        $desimal = $rentang < 5 ? 2 : 1;

        if ($temuan) {
            return round(($butir['Arah'] ?? 'atas') === 'bawah' ? $min - $rentang * 0.12 : $max + $rentang * 0.12, $desimal);
        }

        return round($min + $rentang * (0.3 + mt_rand(0, 40) / 100), $desimal);
    }

    /**
     * Kode butir yang dapat dinilai sesuai/tidak sesuai pada sebuah templat.
     *
     * @return list<string>
     */
    private function butirDapatDinilai(string $kodeTemplat): array
    {
        $kode = [];
        foreach ($this->butirTemplat[$kodeTemplat] as ['definisi' => $butir]) {
            if ($butir['Tipe'] === 'YaTidak' || isset($butir['Pemicu']) || isset($butir['Min'], $butir['Max'])) {
                $kode[] = $butir['Kode'];
            }
        }

        return $kode;
    }

    private function catatanButir(string $kodeTemplat, string $kodeButir): string
    {
        foreach ($this->butirTemplat[$kodeTemplat] as ['definisi' => $butir]) {
            if ($butir['Kode'] === $kodeButir) {
                return (string) ($butir['Catatan'] ?? $butir['Pertanyaan']);
            }
        }

        return $kodeButir;
    }

    private function ubahStatus(string $perintahKerjaId, StatusPerintahKerja $tujuan, ?string $catatan, ?string $ringkasan, string $email): PerintahKerja
    {
        $perintahKerja = $this->perintahKerja($perintahKerjaId);

        return app(UbahStatusPerintahKerja::class)->jalankan($perintahKerja, $tujuan, $catatan, $ringkasan, $perintahKerja->Versi, $this->pengguna($email));
    }

    private function perintahKerja(string $id): PerintahKerja
    {
        return PerintahKerja::query()->withoutGlobalScope(ScopeLingkup::class)->findOrFail($id);
    }

    /**
     * Awal sesi kerja pertama yang tidak bertumpuk dengan sesi lain teknisi itu, bergeser
     * per 30 menit (sesi dari seeder lain juga dihormati).
     */
    private function slotKosong(string $penggunaId, CarbonImmutable $mulai, int $durasiMenit): CarbonImmutable
    {
        for ($percobaan = 0; $percobaan < 24; $percobaan++) {
            $bertumpuk = DB::table('WaktuKerja')
                ->where('PenggunaId', $penggunaId)
                ->where('MulaiPada', '<', $mulai->addMinutes($durasiMenit))
                ->where(fn ($kueri) => $kueri->whereNull('SelesaiPada')->orWhere('SelesaiPada', '>', $mulai))
                ->exists();
            if (! $bertumpuk) {
                return $mulai;
            }
            $mulai = $mulai->addMinutes(30);
        }

        return $mulai;
    }

    private function mundurInterval(CarbonImmutable $tanggal, int $nilai, string $satuan): CarbonImmutable
    {
        return match ($satuan) {
            'Minggu' => $tanggal->subWeeks($nilai),
            'Bulan' => $tanggal->subMonthsNoOverflow($nilai),
            'Tahun' => $tanggal->subYears($nilai),
            default => $tanggal->subDays($nilai),
        };
    }

    /** Hari kerja terdekat (bukan Minggu, bukan hari libur) ke arah `$arah`. */
    private function hariKerja(CarbonImmutable $tanggal, int $arah = 1): CarbonImmutable
    {
        while ($tanggal->isSunday() || isset($this->hariLibur[$tanggal->toDateString()])) {
            $tanggal = $tanggal->addDays($arah);
        }

        return $tanggal;
    }

    /** Selisih hari tanggal lokal terhadap hari ini; positif berarti sudah lewat. */
    private function hariLaluDari(string $tanggal): int
    {
        return (int) CarbonImmutable::parse($tanggal, 'Asia/Jakarta')->startOfDay()->diffInDays($this->hariIni, false);
    }

    /** Jam lokal pada tanggal tertentu, dalam UTC, tanpa dibatasi hari ini (untuk nilai data). */
    private function waktuLokal(CarbonImmutable $tanggal, int $jam, int $menit = 0): CarbonImmutable
    {
        return CarbonImmutable::create($tanggal->year, $tanggal->month, $tanggal->day, $jam, 0, 0, 'Asia/Jakarta')
            ->addMinutes($menit)
            ->utc();
    }

    /** Jam lokal untuk memundurkan jam aplikasi; tidak pernah melewati saat ini. */
    private function padaTanggal(CarbonImmutable $tanggal, int $jam, int $menit = 0): CarbonImmutable
    {
        return $this->batasi($this->waktuLokal($tanggal, $jam, $menit));
    }

    private function batasi(CarbonImmutable $waktu): CarbonImmutable
    {
        return $waktu->greaterThan($this->batasSekarang()) ? $this->batasSekarang() : $waktu;
    }

    private function batasSekarang(): CarbonImmutable
    {
        return $this->sekarangNyata->subMinute();
    }
}
