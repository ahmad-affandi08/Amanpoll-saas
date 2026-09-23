<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FASE 26.01 — aturan penyimpanan waktu: "Semua kolom waktu ... disimpan UTC"
 * (ADR 0001 §4, ARSITEKTUR, TASK 00.02 "Pastikan timezone aplikasi UTC untuk
 * penyimpanan waktu"). Presentasi per zona waktu organisasi dijaga terpisah
 * di ZonaWaktuTampilanOrganisasiTest.
 */
final class PenyimpananWaktuUtcTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-01 03:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_kolom_waktu_tersimpan_di_basis_data_dalam_utc(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'UTC-'.uniqid(), 'Nama' => 'Organisasi UTC', 'Status' => 'Aktif']);

        // Dibaca mentah dari kolomnya, tanpa cast model yang dapat menutupi zona waktunya.
        $mentah = (string) DB::table('Organisasi')->where('Id', $organisasi->Id)->value('DibuatPada');

        $this->assertSame('2026-09-01 03:00:00', substr($mentah, 0, 19), 'Kolom waktu harus berisi jam UTC, bukan jam dinding zona lain.');
    }

    /**
     * Sesi basis data berjalan dalam UTC, apa pun zona sistem servernya.
     *
     * Ratusan kolom memakai DEFAULT/ON UPDATE CURRENT_TIMESTAMP, yang dihitung
     * MySQL dengan zona sesinya sendiri. Di lingkungan pengembangan zona sistemnya
     * kebetulan UTC, jadi tanpa pemeriksaan langsung ini penguncian di
     * config/database.php dapat hilang tanpa satu test pun merah -- lalu di
     * shared hosting berzona lain, kolom-kolom itu diam-diam tergeser.
     */
    public function test_sesi_basis_data_berjalan_dalam_utc(): void
    {
        $this->assertSame('+00:00', (string) DB::selectOne('select @@session.time_zone as zona')->zona);
    }

    /**
     * Waktu berzona selain UTC disimpan sebagai momennya, bukan jam dindingnya.
     *
     * Eloquent memformat jam dinding objek waktu apa adanya, jadi tanpa
     * normalisasi `08:00+07:00` tersimpan sebagai 08:00 -- tujuh jam
     * meleset. Waktu berakhiran `Z` tidak menguji ini sama sekali: jam
     * dindingnya sudah UTC. Sisi sebaliknya ikut dijaga: tanggal kalender
     * tidak punya zona dan tidak boleh ikut digeser ke hari sebelumnya.
     */
    public function test_waktu_berzona_selain_utc_disimpan_sebagai_momen_utc_tanpa_menggeser_tanggal(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'UTC-HL-'.uniqid(), 'Nama' => 'Organisasi Hari Libur', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $dariString = new HariLibur(['Nama' => 'Dari string berzona', 'BerulangTahunan' => false]);
        $dariString->Tanggal = '2026-09-22';
        $dariString->forceFill(['DibuatPada' => '2026-09-01T08:00:00+07:00'])->save();

        // Kolom `date` biasa. (HariLibur.Tanggal ber-cast `date:Y-m-d`, yang oleh
        // Eloquent tidak dianggap kolom tanggal sama sekali, sehingga tidak
        // dapat menguji sisi ini.)
        $kategori = KategoriAset::create(['Kode' => 'KAT-UTC', 'Nama' => 'Alat Kesehatan']);
        $aset = Aset::create(['KategoriAsetId' => $kategori->Id, 'KodeAset' => 'AST-UTC', 'Nama' => 'Tensimeter', 'Status' => StatusAset::Aktif->value]);
        $sertifikat = new SertifikasiAset([
            'AsetId' => $aset->Id,
            'JenisSertifikasi' => 'Kalibrasi',
            'NomorSertifikat' => 'SRT-UTC-01',
            'Status' => 'Aktif',
        ]);
        // 00:30 di Jayapura tanggal 22 adalah 15:30 UTC tanggal 21: tanggalnya tetap 22.
        $sertifikat->TerbitPada = CarbonImmutable::parse('2026-09-22 00:30:00', 'Asia/Jayapura');
        $sertifikat->forceFill(['DibuatPada' => CarbonImmutable::parse('2026-09-01 10:00:00', 'Asia/Jayapura')])->save();
        $mentahSertifikat = DB::table('SertifikasiAset')->where('Id', $sertifikat->Id)->first(['TerbitPada', 'DibuatPada']);

        $mentah = fn (HariLibur $baris) => DB::table('HariLibur')->where('Id', $baris->Id)->first(['Tanggal', 'DibuatPada']);

        $this->assertSame('2026-09-01 01:00:00', substr((string) $mentah($dariString)->DibuatPada, 0, 19), 'String berzona +07:00.');
        $this->assertSame('2026-09-01 01:00:00', substr((string) $mentahSertifikat->DibuatPada, 0, 19), 'Objek berzona Asia/Jayapura.');
        $this->assertSame('2026-09-22', substr((string) $mentah($dariString)->Tanggal, 0, 10));
        $this->assertSame('2026-09-22', substr((string) $mentahSertifikat->TerbitPada, 0, 10), 'Tanggal kalender tidak boleh digeser ke UTC.');
    }

    public function test_waktu_kerja_offline_berzona_utc_tidak_bergeser_saat_disimpan(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'UTC-WK-'.uniqid(), 'Nama' => 'Organisasi Waktu Kerja', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $teknisi = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi UTC',
            'Email' => 'teknisi.'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia-panjang-01',
            'Status' => 'Aktif',
        ]);
        $perintahKerja = PerintahKerja::create([
            'OrganisasiId' => $organisasi->Id,
            'Nomor' => 'WO-UTC-'.uniqid(),
            'Judul' => 'Perbaikan genset',
            'Jenis' => 'Korektif',
            'Status' => StatusPerintahKerja::Ditugaskan->value,
            'Prioritas' => 'Normal',
            'Versi' => 1,
        ]);
        PenugasanPerintahKerja::create([
            'OrganisasiId' => $organisasi->Id,
            'PerintahKerjaId' => $perintahKerja->Id,
            'PenggunaId' => $teknisi->Id,
            'DitugaskanOleh' => $teknisi->Id,
            'DitugaskanPada' => now(),
            'Status' => 'Ditugaskan',
        ]);
        app(KonteksOrganisasi::class)->bersihkan();

        // Ponsel mengirim waktu ISO 8601 dengan offset UTC eksplisit (bentuk toISOString()).
        $this->actingAs($teknisi)->postJson(route('offline.antrian.dorong'), [
            'IdentitasPerangkat' => 'ponsel-utc-01',
            'Mutasi' => [
                [
                    'KunciOperasi' => 'op-utc-terima',
                    'Operasi' => 'PerintahKerja.ResponsPenugasan',
                    'EntitasId' => $perintahKerja->Id,
                    'VersiKlien' => 1,
                    'MuatanData' => ['Respons' => 'Terima'],
                ],
                [
                    'KunciOperasi' => 'op-utc-waktu',
                    'Operasi' => 'PerintahKerja.CatatWaktuKerja',
                    'EntitasId' => $perintahKerja->Id,
                    'VersiKlien' => null,
                    'MuatanData' => [
                        'MulaiPada' => '2026-09-01T01:00:00Z',
                        'SelesaiPada' => '2026-09-01T02:00:00Z',
                    ],
                ],
            ],
        ])->assertOk()->assertJsonPath('Antrean.1.Status', 'Selesai');

        $waktuKerja = WaktuKerja::query()->withoutGlobalScopes()
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->sole();

        // Instan yang dikirim klien harus kembali sebagai instan yang sama.
        $this->assertSame('2026-09-01 01:00:00', $waktuKerja->MulaiPada?->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-01 02:00:00', $waktuKerja->SelesaiPada?->utc()->format('Y-m-d H:i:s'));
    }

    /**
     * Objek waktu yang diikat ke kueri dikirim sebagai momennya.
     *
     * `Connection::prepareBindings()` memformat jam dinding objek waktu tanpa
     * melihat zonanya. Batas hari Jayapura (00:00 WIT = 15:00 UTC) yang
     * dioper apa adanya menyaring sejak 00:00 UTC, sembilan jam terlambat, dan
     * `update()` lewat query builder menulis jam dinding yang salah tanpa
     * melewati model sama sekali.
     */
    public function test_objek_waktu_berzona_yang_diikat_ke_kueri_dikirim_sebagai_momen_utc(): void
    {
        $awalHariWit = CarbonImmutable::parse('2026-09-22 00:00:00', 'Asia/Jayapura');
        $kode = 'UTC-IKAT-'.uniqid();
        $organisasi = Organisasi::create(['Kode' => $kode, 'Nama' => 'Organisasi Ikat', 'Status' => 'Aktif']);

        DB::table('Organisasi')->where('Id', $organisasi->Id)->update(['DibuatPada' => $awalHariWit->addHours(2)]);
        $this->assertSame(
            '2026-09-21 17:00:00',
            substr((string) DB::table('Organisasi')->where('Id', $organisasi->Id)->value('DibuatPada'), 0, 19),
            'Update lewat query builder harus menulis momen UTC.',
        );

        $ditemukan = DB::table('Organisasi')
            ->where('Kode', $kode)
            ->whereBetween('DibuatPada', [$awalHariWit, $awalHariWit->endOfDay()])
            ->count();
        $this->assertSame(1, $ditemukan, '02:00 WIT tanggal 22 termasuk hari 22 di Jayapura.');

        $sebelumnya = DB::table('Organisasi')
            ->where('Kode', $kode)
            ->where('DibuatPada', '<', $awalHariWit)
            ->count();
        $this->assertSame(0, $sebelumnya);
    }

    /**
     * Tanggal tanpa jam untuk kolom berjam berarti awal hari itu di rumah sakitnya.
     *
     * Pemilih tanggal di formulir mengirim `2026-09-25`. Dibaca sebagai
     * tengah malam UTC, jadwalnya jatuh pukul 09:00 WIT; dibaca di zona
     * organisasi, jadwalnya 00:00 WIT seperti yang dipilih.
     */
    public function test_tanggal_tanpa_jam_untuk_kolom_berjam_dibaca_sebagai_awal_hari_di_zona_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'UTC-TGL-'.uniqid(), 'Nama' => 'RS Jayapura', 'Status' => 'Aktif', 'ZonaWaktu' => 'Asia/Jayapura']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $perintahKerja = PerintahKerja::create([
            'OrganisasiId' => $organisasi->Id,
            'Nomor' => 'WO-TGL-'.uniqid(),
            'Judul' => 'Kalibrasi berkala',
            'Jenis' => 'Preventif',
            'Status' => StatusPerintahKerja::Draf->value,
            'Prioritas' => 'Normal',
            'DijadwalkanMulaiPada' => '2026-09-25',
            'DijadwalkanSelesaiPada' => '2026-09-25T10:00:00+09:00',
        ]);

        $mentah = DB::table('PerintahKerja')->where('Id', $perintahKerja->Id)->first(['DijadwalkanMulaiPada', 'DijadwalkanSelesaiPada']);
        $this->assertSame('2026-09-24 15:00:00', substr((string) $mentah->DijadwalkanMulaiPada, 0, 19));
        $this->assertSame('2026-09-25 01:00:00', substr((string) $mentah->DijadwalkanSelesaiPada, 0, 19), 'Waktu berjam tetap dibaca sebagai momennya.');
    }
}
