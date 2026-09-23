<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Http\Requests\ImporProspekRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Ekspor\NetralkanRumus;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Impor CSV dan ekspor prospek (MARKETING.md 5.1, 27). */
final class ImporEksporProspekController extends Controller
{
    /** Kolom yang diterima impor, sekaligus urutan kolom hasil ekspor. */
    private const KOLOM = [
        'Nama', 'Email', 'Telepon', 'WhatsApp', 'Jabatan',
        'Perusahaan', 'Industri', 'Kota', 'Negara',
    ];

    public function __construct(
        private readonly LayananAudit $audit,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    public function impor(ImporProspekRequest $request, CatatProspek $aksi): RedirectResponse
    {
        $berkas = $request->file('Berkas');
        $jalur = $berkas?->getRealPath();

        if (! is_string($jalur)) {
            return back()->with('gagal', 'Berkas tidak dapat dibaca.');
        }

        $hasil = ['diproses' => 0, 'dilewati' => 0];
        $pegangan = fopen($jalur, 'rb');

        if ($pegangan === false) {
            return back()->with('gagal', 'Berkas tidak dapat dibuka.');
        }

        try {
            $kepala = fgetcsv($pegangan, escape: '');

            if ($kepala === false) {
                return back()->with('gagal', 'Berkas CSV kosong.');
            }

            $petaKolom = $this->petaKolom($kepala);

            while (($baris = fgetcsv($pegangan, escape: '')) !== false) {
                $data = $this->baris($baris, $petaKolom);

                // Nama adalah satu-satunya kolom wajib.
                if (($data['Nama'] ?? '') === '') {
                    $hasil['dilewati']++;

                    continue;
                }

                $aksi->jalankan($data, SumberProspek::ImporCsv);
                $hasil['diproses']++;
            }
        } finally {
            fclose($pegangan);
        }

        $this->audit->catat('Prospek.Diimpor', 'Prospek', null, dataSesudah: $hasil);

        return back()->with(
            'sukses',
            "Impor selesai: {$hasil['diproses']} prospek diproses, {$hasil['dilewati']} baris dilewati.",
        );
    }

    public function ekspor(): StreamedResponse
    {
        $jumlah = Prospek::query()->count();

        $this->audit->catat('Prospek.Diekspor', 'Prospek', null, dataSesudah: ['Jumlah' => $jumlah]);

        return response()->streamDownload(function (): void {
            $keluaran = fopen('php://output', 'wb');

            if ($keluaran === false) {
                return;
            }

            fwrite($keluaran, "\xEF\xBB\xBF");
            fputcsv($keluaran, [...self::KOLOM, 'Sumber', 'Tahap', 'Skor', 'DibuatPada'], escape: '');

            // Dialirkan per potongan: daftar prospek tumbuh tanpa batas.
            Prospek::query()
                ->with(['organisasiProspek', 'tahap'])
                ->orderBy('DibuatPada')
                ->chunk(500, function ($kumpulan) use ($keluaran): void {
                    foreach ($kumpulan as $prospek) {
                        fputcsv($keluaran, NetralkanRumus::barisCsv([
                            $prospek->Nama,
                            $prospek->Email,
                            $prospek->Telepon,
                            $prospek->WhatsApp,
                            $prospek->Jabatan,
                            $prospek->organisasiProspek?->Nama,
                            $prospek->organisasiProspek?->Industri,
                            $prospek->organisasiProspek?->Kota,
                            $prospek->organisasiProspek?->Negara,
                            $prospek->Sumber,
                            $prospek->tahap?->Nama,
                            $prospek->Skor,
                            $prospek->DibuatPada->toIso8601String(),
                        ]), escape: '');
                    }
                });

            fclose($keluaran);
        }, 'prospek-'.$this->kalender->sekarang()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  list<string|null>  $kepala
     * @return array<string, int>
     */
    private function petaKolom(array $kepala): array
    {
        $peta = [];

        foreach ($kepala as $indeks => $nama) {
            $bersih = trim((string) $nama);

            foreach (self::KOLOM as $kolom) {
                if (strcasecmp($bersih, $kolom) === 0) {
                    $peta[$kolom] = $indeks;
                }
            }
        }

        return $peta;
    }

    /**
     * @param  list<string|null>  $baris
     * @param  array<string, int>  $petaKolom
     * @return array<string, string>
     */
    private function baris(array $baris, array $petaKolom): array
    {
        $data = [];

        foreach ($petaKolom as $kolom => $indeks) {
            $data[$kolom] = trim((string) ($baris[$indeks] ?? ''));
        }

        return $data;
    }
}
