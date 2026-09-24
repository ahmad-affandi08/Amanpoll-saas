<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Notifikasi\Notifications\NotifikasiUmum;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Shared\Infrastructure\Persistence\PembacaUkuranBasisData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Pemantau ukuran basis data terhadap batas hosting (FASE 45).
 *
 * Harian: mencatat total data+indeks, sepuluh tabel terbesar, dan ukuran
 * CatatanAudit (append-only, jadi pertumbuhannya perlu terlihat meski tidak
 * dipangkas). Melewati `ambang_peringatan_persen` menulis log warning,
 * `ambang_kritis_persen` log error; keduanya juga mengirim email ke admin
 * platform aktif bila `kirim_email_admin` menyala. Admin platform tidak punya
 * kotak notifikasi in-app, jadi email adalah satu-satunya kanal langsung ke
 * mereka; bila gagal terkirim, log error-nya tetap ada.
 */
final class PantauUkuranBasisData extends Command
{
    protected $signature = 'basisdata:pantau-ukuran';

    protected $description = 'Catat ukuran basis data dan tabel terbesar; peringatkan saat mendekati batas hosting';

    public function handle(PembacaUkuranBasisData $pembaca): int
    {
        $tabel = $pembaca->ukuranTabel();
        usort($tabel, fn (array $a, array $b): int => $b['Byte'] <=> $a['Byte']);

        $total = array_sum(array_column($tabel, 'Byte'));
        $batas = max(1, (int) config('amanpoll.retensi.pemantau.batas_byte', 3 * 1024 * 1024 * 1024));
        $persen = round($total / $batas * 100, 1);
        $terbesar = array_slice($tabel, 0, 10);
        $audit = array_values(array_filter($tabel, fn (array $satu): bool => $satu['Nama'] === 'CatatanAudit'))[0] ?? null;

        $konteks = [
            'TotalByte' => $total,
            'Total' => self::mb($total),
            'BatasByte' => $batas,
            'Persen' => $persen,
            'Terbesar' => array_map(fn (array $satu): array => [...$satu, 'Ukuran' => self::mb($satu['Byte'])], $terbesar),
            'CatatanAudit' => $audit === null ? null : [...$audit, 'Ukuran' => self::mb($audit['Byte'])],
        ];

        Log::info('Ukuran basis data.', $konteks);

        $this->info("Total basis data: {$konteks['Total']} ({$persen}% dari ".self::mb($batas).').');
        $this->table(['Tabel', 'Ukuran', 'Perkiraan baris'], array_map(
            fn (array $satu): array => [$satu['Nama'], self::mb($satu['Byte']), $satu['PerkiraanBaris']],
            $terbesar,
        ));
        if ($audit !== null) {
            $this->line('CatatanAudit (append-only): '.self::mb($audit['Byte']).'.');
        }

        $this->peringatkan($persen, $total, $batas, $terbesar);

        return self::SUCCESS;
    }

    /** @param list<array{Nama: string, Byte: int, PerkiraanBaris: int}> $terbesar */
    private function peringatkan(float $persen, int $total, int $batas, array $terbesar): void
    {
        $kritis = (float) config('amanpoll.retensi.pemantau.ambang_kritis_persen', 85);
        $peringatan = (float) config('amanpoll.retensi.pemantau.ambang_peringatan_persen', 70);

        if ($persen < $peringatan) {
            return;
        }

        $tingkat = $persen >= $kritis ? 'KRITIS' : 'PERINGATAN';
        $pesan = sprintf(
            'Basis data Amanpoll %s: %s dari batas %s (%s%%). Tabel terbesar: %s. Periksa `retensi:pangkas` dan kebijakan retensi sebelum hosting menolak tulisan baru.',
            $tingkat,
            self::mb($total),
            self::mb($batas),
            $persen,
            implode(', ', array_map(fn (array $satu): string => $satu['Nama'].' '.self::mb($satu['Byte']), array_slice($terbesar, 0, 5))),
        );

        if ($tingkat === 'KRITIS') {
            Log::error($pesan);
            $this->error($pesan);
        } else {
            Log::warning($pesan);
            $this->warn($pesan);
        }

        if (! (bool) config('amanpoll.retensi.pemantau.kirim_email_admin', true)) {
            return;
        }

        $email = AdminPlatform::query()
            ->where('Status', 'Aktif')
            ->whereNotNull('Email')
            ->where('Email', '!=', '')
            ->pluck('Email')
            ->map(fn (mixed $satu): string => (string) $satu)
            ->unique()
            ->values()
            ->all();

        if ($email === []) {
            Log::error('Peringatan ukuran basis data tidak terkirim: tidak ada admin platform aktif beremail.');

            return;
        }

        try {
            Notification::route('mail', $email)->notify(new NotifikasiUmum("[{$tingkat}] Ukuran basis data Amanpoll", $pesan));
        } catch (Throwable $galat) {
            Log::error('Email peringatan ukuran basis data gagal dikirim.', ['Galat' => $galat::class]);
        }
    }

    private static function mb(int $byte): string
    {
        return number_format($byte / 1024 / 1024, 1, ',', '.').' MB';
    }
}
