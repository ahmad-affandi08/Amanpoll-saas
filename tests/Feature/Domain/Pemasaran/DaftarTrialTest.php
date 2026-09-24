<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Application\Actions\CatatPembayaranLangganan;
use App\Domain\Langganan\Application\Services\LayananLangganan;
use App\Domain\Langganan\Application\Services\RegistriPenyediaPembayaran;
use App\Domain\Langganan\Domain\Contracts\MenerimaKartuDiMuka;
use App\Domain\Langganan\Domain\Contracts\PenyediaPembayaran;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\ValueObjects\InstruksiPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Domain\ValueObjects\PesananPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Pemasaran\Application\Actions\DaftarkanTrial;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Domain\Platform\Domain\ValueObjects\KatalogPeranAwal;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Http\Middleware\TetapkanSesiPengunjung;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Pendaftaran trial mandiri (MARKETING.md 12, 34.1). */
final class DaftarTrialTest extends KasusTrial
{
    public function test_pendaftaran_menghasilkan_organisasi_pengguna_langganan_dan_trial(): void
    {
        $trial = $this->daftar();

        $organisasi = Organisasi::query()->find($trial->OrganisasiId);
        $this->assertNotNull($organisasi);
        $this->assertSame('Pabrik Sejahtera', $organisasi->Nama);

        $pengguna = Pengguna::query()->withoutGlobalScopes()
            ->where('OrganisasiId', $organisasi->Id)
            ->first();
        $this->assertNotNull($pengguna);
        $this->assertTrue(Hash::check('rahasia-panjang', (string) $pengguna->KataSandi));

        $langganan = app(LayananLangganan::class)->untukOrganisasi($organisasi->Id);
        $this->assertNotNull($langganan);
        $this->assertSame(StatusLangganan::UjiCoba->value, $langganan->Status);
        $this->assertSame($langganan->Id, $trial->LanggananId);
    }

    public function test_pemilik_baru_memegang_seluruh_izin(): void
    {
        $trial = $this->daftar();

        // Dibaca dari dalam tenant barunya, persis seperti pemiliknya nanti membacanya.
        app(KonteksOrganisasi::class)->tetapkan($trial->OrganisasiId);

        $pengguna = Pengguna::query()->withoutGlobalScopes()
            ->with('penggunaPeran.peran.peranIzin')
            ->where('OrganisasiId', $trial->OrganisasiId)
            ->firstOrFail();

        $peran = $pengguna->penggunaPeran->first()?->peran;

        $this->assertSame('PEMILIK', $peran?->Kode);
        $this->assertSame(Izin::query()->count(), $peran?->peranIzin->count());
    }

    /**
     * Pemilik memegang seluruh izin, jadi tanpa peran bawaan orang kedua di
     * organisasi itu hanya bisa diberi akses penuh atau tidak sama sekali.
     */
    public function test_pendaftaran_memasang_peran_bawaan(): void
    {
        $trial = $this->daftar();

        app(KonteksOrganisasi::class)->tetapkan($trial->OrganisasiId);

        $teknisi = Peran::query()->where('Kode', 'TEKNISI')->first();

        $this->assertNotNull($teknisi);
        $this->assertFalse($teknisi->BawaanSistem);
        $this->assertSame(
            count(KatalogPeranAwal::semua()) + 1,
            Peran::query()->count(),
            'Peran bawaan ditambah peran Pemilik.',
        );
    }

    public function test_pendaftaran_membuat_prospek_bersumber_trial(): void
    {
        $trial = $this->daftar();

        $prospek = Prospek::query()->find($trial->ProspekId);

        $this->assertNotNull($prospek);
        $this->assertSame(SumberProspek::Trial->value, $prospek->Sumber);
        $this->assertSame($trial->OrganisasiId, $prospek->OrganisasiId);
    }

    public function test_kunjungan_sebelumnya_tetap_tertaut_ke_trialnya(): void
    {
        $pengenal = (string) Str::ulid();

        $trial = $this->daftar(pengenal: $pengenal);

        $this->assertSame($pengenal, $trial->PengenalPengunjung);
        $this->assertTrue(EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::TRIAL_DIMULAI)
            ->where('PengenalPengunjung', $pengenal)
            ->exists());
    }

    public function test_pendaftaran_lewat_http_mengarahkan_ke_masuk(): void
    {
        $this->get('/daftar')->assertOk();

        $this->post('/daftar', $this->muatan())
            ->assertRedirect(route('login'))
            ->assertSessionHas('sukses');

        $this->assertSame(1, Trial::query()->count());
    }

    public function test_pendaftaran_http_membawa_pengenal_pengunjung_dari_cookie(): void
    {
        $pengenal = (string) Str::ulid();

        $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, $pengenal)
            ->post('/daftar', $this->muatan())
            ->assertRedirect();

        $this->assertSame($pengenal, Trial::query()->firstOrFail()->PengenalPengunjung);
    }

    public function test_kata_sandi_yang_tidak_cocok_ditolak(): void
    {
        $this->post('/daftar', [...$this->muatan(), 'KataSandi_confirmation' => 'beda-sekali'])
            ->assertSessionHasErrors('KataSandi');

        $this->assertSame(0, Trial::query()->count());
    }

    public function test_tanpa_persetujuan_ditolak(): void
    {
        $muatan = $this->muatan();
        unset($muatan['Persetujuan']);

        $this->post('/daftar', $muatan)->assertSessionHasErrors('Persetujuan');

        $this->assertSame(0, Trial::query()->count());
    }

    public function test_pendaftaran_dibatasi_lajunya(): void
    {
        for ($ke = 0; $ke < 3; $ke++) {
            $this->post('/daftar', $this->muatan("orang{$ke}@pabrik.test"));
        }

        $this->post('/daftar', $this->muatan('orang9@pabrik.test'))->assertStatus(429);
    }

    public function test_kartu_diperlukan_menutup_pendaftaran_saat_penyedia_tidak_mendukung(): void
    {
        $this->nyalakanKartu();

        $this->expectException(AturanBisnisDilanggar::class);

        app(DaftarkanTrial::class)->jalankan($this->muatan());
    }

    public function test_kartu_diperlukan_menolak_pendaftaran_tanpa_token(): void
    {
        $this->nyalakanKartu();
        $this->pasangPenyediaKartu(sah: true);

        $this->expectException(AturanBisnisDilanggar::class);

        app(DaftarkanTrial::class)->jalankan($this->muatan());
    }

    public function test_kartu_yang_ditolak_penyedia_menggagalkan_pendaftaran(): void
    {
        $this->nyalakanKartu();
        $this->pasangPenyediaKartu(sah: false);

        $this->expectException(AturanBisnisDilanggar::class);

        app(DaftarkanTrial::class)->jalankan([...$this->muatan(), 'TokenKartu' => 'tok_palsu']);
    }

    public function test_kartu_yang_diterima_penyedia_meloloskan_pendaftaran(): void
    {
        $this->nyalakanKartu();
        $this->pasangPenyediaKartu(sah: true);

        $trial = app(DaftarkanTrial::class)->jalankan([...$this->muatan(), 'TokenKartu' => 'tok_sah']);

        $this->assertSame(StatusTrial::Setup, $trial->Status);
    }

    public function test_formulir_menutup_dirinya_saat_kartu_tidak_dapat_diterima(): void
    {
        $this->nyalakanKartu();

        $props = $this->get('/daftar')->viewData('page')['props'];

        $this->assertTrue($props['kartuDiminta']);
        $this->assertFalse($props['penyediaSiapKartu']);
    }

    public function test_trial_hasil_pendaftaran_dapat_dikonversi_pembayaran(): void
    {
        $trial = $this->daftar();
        $langganan = app(LayananLangganan::class)->untukOrganisasi($trial->OrganisasiId);
        $this->assertNotNull($langganan);

        app(KonteksOrganisasi::class)->tetapkan($trial->OrganisasiId);

        $tagihan = TagihanLangganan::create([
            'OrganisasiId' => $trial->OrganisasiId,
            'LanggananId' => $langganan->Id,
            'Nomor' => 'INV-'.Str::upper(Str::random(8)),
            'PeriodeMulai' => now()->toDateString(),
            'PeriodeSelesai' => now()->addMonth()->toDateString(),
            'JatuhTempo' => now()->addDays(14)->toDateString(),
            'Subtotal' => 250_000,
            'Pajak' => 0,
            'Total' => 250_000,
            'MataUang' => 'IDR',
            'Status' => 'BelumDibayar',
        ]);

        app(CatatPembayaranLangganan::class)
            ->dariPeristiwa('TransferManual', new PeristiwaPembayaran(
                idPeristiwa: (string) Str::ulid(),
                nomorTagihan: (string) $tagihan->Nomor,
                jumlah: 250_000,
                status: StatusPembayaranLangganan::Berhasil,
            ));

        $this->assertSame(StatusTrial::Konversi, $trial->fresh()?->Status);
    }

    private function daftar(?string $pengenal = null): Trial
    {
        return app(DaftarkanTrial::class)->jalankan($this->muatan(), $pengenal);
    }

    /** @return array<string, mixed> */
    private function muatan(string $email = 'budi@pabrik.test'): array
    {
        return [
            'NamaOrganisasi' => 'Pabrik Sejahtera',
            'Nama' => 'Budi',
            'Email' => $email,
            'Telepon' => '0811000111',
            'KataSandi' => 'rahasia-panjang',
            'KataSandi_confirmation' => 'rahasia-panjang',
            'Persetujuan' => true,
        ];
    }

    private function nyalakanKartu(): void
    {
        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::TRIAL_KARTU_DIPERLUKAN, true);
    }

    private function pasangPenyediaKartu(bool $sah): void
    {
        $penyedia = new class($sah) implements MenerimaKartuDiMuka, PenyediaPembayaran
        {
            public function __construct(private readonly bool $sah) {}

            public function kode(): string
            {
                return 'KartuUji';
            }

            public function nama(): string
            {
                return 'Kartu Uji';
            }

            public function mulaiPembayaran(TagihanLangganan $tagihan, PesananPembayaran $pesanan): InstruksiPembayaran
            {
                return InstruksiPembayaran::rincian([]);
            }

            public function webhookSah(Request $permintaan): bool
            {
                return true;
            }

            public function terjemahkanWebhook(Request $permintaan): PeristiwaPembayaran
            {
                throw new \LogicException('Tidak dipakai dalam test ini.');
            }

            public function metodePembayaranSah(string $token): bool
            {
                return $this->sah;
            }
        };

        app(RegistriPenyediaPembayaran::class)->daftarkan($penyedia);
        config(['amanpoll.langganan.penyedia_pembayaran' => 'KartuUji']);
    }
}
