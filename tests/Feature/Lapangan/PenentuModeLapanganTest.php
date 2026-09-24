<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Platform\Application\Actions\CabutPeranDariPengguna;
use App\Domain\Platform\Application\Actions\TetapkanPeranKePengguna;
use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use App\Domain\Platform\Domain\Enums\ModeLapangan;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;

/** Penentuan mode Mode Lapangan dari penanda peran, bukan kode peran (PRD 8.20). */
final class PenentuModeLapanganTest extends KasusLapangan
{
    public function test_teknisi_menang_saat_pengguna_memegang_peran_teknisi_dan_pelapor(): void
    {
        $pengguna = $this->penggunaDenganPeran(['PELAPOR', 'TEKNISI']);
        $penentu = app(PenentuModeLapangan::class);

        $this->assertSame(ModeLapangan::Teknisi, $penentu->mode($pengguna));
        $this->assertTrue($penentu->lapanganMurni($pengguna));
        $this->assertFalse($penentu->bisaBeralih($pengguna));
    }

    public function test_pengguna_campuran_bisa_beralih_dan_bukan_lapangan_murni(): void
    {
        $pengguna = $this->penggunaMeja(['Aset.Lihat'], $this->penggunaDenganPeran(['PELAPOR']));
        $penentu = app(PenentuModeLapangan::class);

        $this->assertSame(ModeLapangan::Pelapor, $penentu->mode($pengguna));
        $this->assertFalse($penentu->lapanganMurni($pengguna));
        $this->assertTrue($penentu->bisaBeralih($pengguna));
    }

    public function test_pengguna_tanpa_peran_bukan_pengguna_lapangan(): void
    {
        $pengguna = $this->buatPengguna();
        $penentu = app(PenentuModeLapangan::class);

        $this->assertNull($penentu->mode($pengguna));
        $this->assertFalse($penentu->lapanganMurni($pengguna));
        $this->assertFalse($penentu->bisaBeralih($pengguna));
    }

    /** Tenant bebas mengganti kode perannya; yang dibaca hanya penanda. */
    public function test_penentuan_memakai_penanda_bukan_kode_peran(): void
    {
        $pengguna = $this->buatPengguna();
        $this->dalamOrganisasi(function () use ($pengguna): void {
            Peran::query()->where('Kode', 'TEKNISI')->update(['TampilanLapangan' => null]);
            $peran = Peran::create(['Kode' => 'MEKANIK-SHIFT', 'Nama' => 'Mekanik Shift', 'TampilanLapangan' => ModeLapangan::Teknisi]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        });
        $pemegangTeknisiTanpaPenanda = $this->penggunaDenganPeran(['TEKNISI']);
        $penentu = app(PenentuModeLapangan::class);

        $this->assertSame(ModeLapangan::Teknisi, $penentu->mode($pengguna));
        $this->assertNull($penentu->mode($pemegangTeknisiTanpaPenanda));
    }

    public function test_penugasan_kedaluwarsa_dan_peran_terhapus_tidak_dihitung(): void
    {
        $pengguna = $this->penggunaMeja(['Aset.Lihat']);
        $this->dalamOrganisasi(function () use ($pengguna): void {
            PenggunaPeran::create([
                'PenggunaId' => $pengguna->Id,
                'PeranId' => Peran::query()->where('Kode', 'TEKNISI')->firstOrFail()->Id,
                'BerlakuSampai' => now()->subDay(),
            ]);
            $terhapus = Peran::create(['Kode' => 'LAMA', 'Nama' => 'Pelapor Lama', 'TampilanLapangan' => ModeLapangan::Pelapor]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $terhapus->Id]);
            $terhapus->delete();
        });

        $this->assertNull(app(PenentuModeLapangan::class)->mode($pengguna));
    }

    /** Hasil di-cache; perubahan penugasan harus langsung berlaku, seperti LingkupAkses. */
    public function test_menetapkan_dan_mencabut_peran_langsung_mengubah_hasil_yang_tercache(): void
    {
        $pengguna = $this->penggunaMeja(['Aset.Lihat']);
        $penentu = app(PenentuModeLapangan::class);
        $this->assertNull($penentu->mode($pengguna));

        $penugasan = $this->dalamOrganisasi(fn () => app(TetapkanPeranKePengguna::class)
            ->jalankan($pengguna, Peran::query()->where('Kode', 'PELAPOR')->firstOrFail()));
        $this->assertSame(ModeLapangan::Pelapor, $penentu->mode($pengguna));

        $this->dalamOrganisasi(fn () => app(CabutPeranDariPengguna::class)->jalankan($penugasan));
        $this->assertNull($penentu->mode($pengguna));
    }
}
