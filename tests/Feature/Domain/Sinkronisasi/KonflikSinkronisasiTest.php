<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Sinkronisasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JawabanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** 20.06 — perubahan offline yang bertabrakan dengan server berhenti sebagai konflik. */
final class KonflikSinkronisasiTest extends TestCase
{
    use DatabaseTransactions;

    private const PERANGKAT = 'perangkat-konflik-01';

    public function test_versi_entitas_yang_berubah_di_server_menghentikan_mutasi_sebagai_konflik(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkan();
        $versiLama = $perintahKerja->Versi;

        // Koordinator membatalkan pekerjaan selagi teknisi offline.
        $perintahKerja->Status = StatusPerintahKerja::Dibatalkan->value;
        $perintahKerja->Versi++;
        $perintahKerja->save();

        $respons = $this->dorong($teknisi, [$this->mutasiTerimaPenugasan($perintahKerja->Id, $versiLama)]);
        $respons->assertOk();

        $baris = $this->baris($respons, 'op-konflik-versi');
        $this->assertSame('Konflik', $baris['Status']);
        $this->assertSame('VersiBerbeda', $baris['Konflik']['Alasan']);
        $this->assertSame($versiLama, $baris['Konflik']['VersiKlien']);
        $this->assertSame($versiLama + 1, $baris['Konflik']['VersiServer']);

        // Server tidak tersentuh selama konflik belum diputuskan.
        $this->assertSame(StatusPerintahKerja::Dibatalkan->value, $perintahKerja->refresh()->Status);
        $this->assertSame($versiLama + 1, $perintahKerja->Versi);
    }

    public function test_keputusan_pakai_server_membuang_mutasi_lokal_dan_tercatat_di_audit(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkan();
        $versiLama = $perintahKerja->Versi;
        $perintahKerja->Versi++;
        $perintahKerja->save();

        $respons = $this->dorong($teknisi, [$this->mutasiTerimaPenugasan($perintahKerja->Id, $versiLama)]);
        $antrianId = $this->baris($respons, 'op-konflik-versi')['Id'];

        $this->actingAs($teknisi)
            ->postJson("/offline/antrian/{$antrianId}/konflik", ['Keputusan' => 'PakaiServer'])
            ->assertOk()
            ->assertJsonPath('Mutasi.Status', 'Dibatalkan');

        $this->assertSame(StatusPerintahKerja::Ditugaskan->value, $perintahKerja->refresh()->Status);
        $this->assertSame(1, $this->jumlahAudit('Sinkronisasi.KonflikDiselesaikan'));
    }

    public function test_keputusan_terapkan_ulang_memakai_versi_server_terbaru(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkan();
        $versiLama = $perintahKerja->Versi;

        // Perubahan di server yang tidak mengunci alur teknisi (mis. penjadwalan ulang).
        $perintahKerja->DijadwalkanMulaiPada = now()->addDay();
        $perintahKerja->Versi++;
        $perintahKerja->save();

        $respons = $this->dorong($teknisi, [$this->mutasiTerimaPenugasan($perintahKerja->Id, $versiLama)]);
        $antrianId = $this->baris($respons, 'op-konflik-versi')['Id'];

        $this->actingAs($teknisi)
            ->postJson("/offline/antrian/{$antrianId}/konflik", ['Keputusan' => 'TerapkanUlang'])
            ->assertOk()
            ->assertJsonPath('Mutasi.Status', 'Selesai');

        $this->assertSame(StatusPerintahKerja::Diterima->value, $perintahKerja->refresh()->Status);
        $this->assertSame(1, $this->jumlahAudit('Sinkronisasi.KonflikDiselesaikan'));
        $this->assertSame(1, $this->jumlahAudit('Sinkronisasi.Diterapkan'));
    }

    public function test_daftar_periksa_yang_sudah_difinalisasi_server_menjadi_konflik_bukan_ditimpa(): void
    {
        ['organisasi' => $organisasi, 'teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkan();
        ['pelaksanaan' => $pelaksanaan, 'butir' => $butir] = $this->siapkanDaftarPeriksa($organisasi, $teknisi, $perintahKerja);

        // Rekan kerja menyelesaikan daftar periksa yang sama dari kantor.
        $pelaksanaan->Status = 'Selesai';
        $pelaksanaan->Skor = 100;
        $pelaksanaan->SelesaiPada = now();
        $pelaksanaan->save();

        $respons = $this->dorong($teknisi, [[
            'KunciOperasi' => 'op-jawaban-01',
            'Operasi' => 'DaftarPeriksa.SimpanJawaban',
            'EntitasId' => $pelaksanaan->Id,
            'VersiKlien' => null,
            'MuatanData' => [
                'Jawaban' => [['ButirTemplatDaftarPeriksaId' => $butir->Id, 'NilaiTeks' => 'Jawaban dari lapangan']],
            ],
        ]]);

        $baris = $this->baris($respons, 'op-jawaban-01');
        $this->assertSame('Konflik', $baris['Status']);
        $this->assertSame('EntitasTerkunci', $baris['Konflik']['Alasan']);

        $this->assertNull(
            JawabanDaftarPeriksa::query()->withoutGlobalScopes()
                ->where('PelaksanaanDaftarPeriksaId', $pelaksanaan->Id)
                ->value('NilaiTeks'),
            'Hasil yang sudah final tidak boleh ditimpa jawaban offline.',
        );
        $this->assertSame('Selesai', $pelaksanaan->refresh()->Status);
    }

    public function test_konflik_dicatat_ke_audit_saat_terdeteksi(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkan();
        $versiLama = $perintahKerja->Versi;
        $perintahKerja->Versi++;
        $perintahKerja->save();

        $this->dorong($teknisi, [$this->mutasiTerimaPenugasan($perintahKerja->Id, $versiLama)])->assertOk();

        $this->assertSame(1, $this->jumlahAudit('Sinkronisasi.Konflik'));
    }

    public function test_menyelesaikan_mutasi_yang_tidak_berkonflik_ditolak(): void
    {
        ['teknisi' => $teknisi, 'perintahKerja' => $perintahKerja] = $this->siapkan();

        $respons = $this->dorong($teknisi, [$this->mutasiTerimaPenugasan($perintahKerja->Id, $perintahKerja->Versi)]);
        $baris = $this->baris($respons, 'op-konflik-versi');
        $this->assertSame('Selesai', $baris['Status']);

        $this->actingAs($teknisi)
            ->postJson("/offline/antrian/{$baris['Id']}/konflik", ['Keputusan' => 'PakaiServer'])
            ->assertStatus(422);
    }

    /** @return array<string, mixed> */
    private function mutasiTerimaPenugasan(string $perintahKerjaId, int $versiKlien): array
    {
        return [
            'KunciOperasi' => 'op-konflik-versi',
            'Operasi' => 'PerintahKerja.ResponsPenugasan',
            'EntitasId' => $perintahKerjaId,
            'VersiKlien' => $versiKlien,
            'MuatanData' => ['Respons' => 'Terima'],
        ];
    }

    /** @param list<array<string, mixed>> $mutasi */
    private function dorong(Pengguna $teknisi, array $mutasi): TestResponse
    {
        return $this->actingAs($teknisi)->postJson('/offline/antrian', [
            'IdentitasPerangkat' => self::PERANGKAT,
            'NamaPerangkat' => 'Ponsel Teknisi',
            'Platform' => 'Android',
            'Mutasi' => $mutasi,
        ]);
    }

    /** @return array<string, mixed> */
    private function baris(TestResponse $respons, string $kunciOperasi): array
    {
        return collect($respons->json('Antrean'))->firstWhere('KunciOperasi', $kunciOperasi);
    }

    private function jumlahAudit(string $aksi): int
    {
        return CatatanAudit::query()->withoutGlobalScopes()->where('Aksi', $aksi)->count();
    }

    /**
     * @return array{organisasi: Organisasi, teknisi: Pengguna, perintahKerja: PerintahKerja}
     */
    private function siapkan(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-KNF-'.uniqid(), 'Nama' => 'Organisasi Konflik']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $teknisi = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi Konflik',
            'Email' => uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $perintahKerja = PerintahKerja::create([
            'OrganisasiId' => $organisasi->Id,
            'Nomor' => 'WO-KNF-'.uniqid(),
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

        return ['organisasi' => $organisasi, 'teknisi' => $teknisi, 'perintahKerja' => $perintahKerja->refresh()];
    }

    /**
     * @return array{pelaksanaan: PelaksanaanDaftarPeriksa, butir: ButirTemplatDaftarPeriksa}
     */
    private function siapkanDaftarPeriksa(
        Organisasi $organisasi,
        Pengguna $teknisi,
        PerintahKerja $perintahKerja,
    ): array {
        $templat = TemplatDaftarPeriksa::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'TDP-'.uniqid(),
            'Nama' => 'Pemeriksaan Genset',
            'Jenis' => 'Preventif',
            'VersiTemplat' => 1,
            'Aktif' => true,
        ]);

        $butir = ButirTemplatDaftarPeriksa::create([
            'OrganisasiId' => $organisasi->Id,
            'TemplatDaftarPeriksaId' => $templat->Id,
            'Urutan' => 1,
            'Pertanyaan' => 'Level oli mesin',
            'TipeJawaban' => 'Teks',
            'Wajib' => true,
        ]);

        $pelaksanaan = PelaksanaanDaftarPeriksa::create([
            'OrganisasiId' => $organisasi->Id,
            'TemplatDaftarPeriksaId' => $templat->Id,
            'PerintahKerjaId' => $perintahKerja->Id,
            'DilaksanakanOleh' => $teknisi->Id,
            'MulaiPada' => now(),
            'Status' => 'Draft',
        ]);

        JawabanDaftarPeriksa::create([
            'OrganisasiId' => $organisasi->Id,
            'PelaksanaanDaftarPeriksaId' => $pelaksanaan->Id,
            'ButirTemplatDaftarPeriksaId' => $butir->Id,
            'Sesuai' => null,
        ]);

        return ['pelaksanaan' => $pelaksanaan, 'butir' => $butir];
    }
}
