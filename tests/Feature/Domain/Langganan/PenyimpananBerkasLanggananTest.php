<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/**
 * Kartu "Penyimpanan berkas" di halaman langganan (PRD 11.1): ruang disk
 * dihitung dari ukuran TERSIMPAN dan salinan bersama dihitung sekali; ukuran
 * asli menjumlah seluruh berkas sebagaimana dikirim.
 */
final class PenyimpananBerkasLanggananTest extends KasusLangganan
{
    public function test_pemakaian_memakai_ukuran_tersimpan_dan_salinan_bersama_dihitung_sekali(): void
    {
        Storage::fake('local');
        $this->buatLangganan($this->buatPaketLengkap());
        $csv = "Kode,Nama\n".implode("\n", array_map(fn (int $i): string => "AST-{$i},Pompa infus {$i}", range(1, 500)));

        $pertama = $this->simpan($csv, 'aset.csv');
        $kedua = $this->simpan($csv, 'aset-salinan.csv');
        $this->assertSame($pertama->LokasiPenyimpanan, $kedua->LokasiPenyimpanan, 'Isi identik berbagi satu salinan.');
        $terhapus = $this->simpan('berkas yang sudah dihapus', 'hapus.txt');
        app(PenyimpanBerkas::class)->hapus($terhapus);

        $organisasiLain = Organisasi::create(['Kode' => 'ORG-LAIN-'.uniqid(), 'Nama' => 'Lain', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($organisasiLain->Id);
        $this->simpan(str_repeat('milik organisasi lain ', 500), 'lain.txt');
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $tersimpan = (int) $pertama->UkuranTersimpanByte;
        $asli = 2 * strlen($csv);

        $this->actingAs($this->buatPengguna(['Pengaturan.Kelola']))
            ->get(route('langganan.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Langganan/Index')
                ->where('penyimpanan.JumlahBerkas', 2)
                ->where('penyimpanan.UkuranAsliByte', $asli)
                ->where('penyimpanan.UkuranTersimpanByte', $tersimpan)
                ->where('penyimpanan.PersenHemat', round(($asli - $tersimpan) / $asli * 100, 1)));
    }

    private function simpan(string $isi, string $nama): Berkas
    {
        $sementara = (string) tempnam(sys_get_temp_dir(), 'uji-');
        file_put_contents($sementara, $isi);

        try {
            return app(PenyimpanBerkas::class)->simpan($sementara, 'text/csv', $nama);
        } finally {
            @unlink($sementara);
        }
    }
}
