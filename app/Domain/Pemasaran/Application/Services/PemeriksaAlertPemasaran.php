<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\StatusEksekusiOtomasi;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Domain\Enums\StatusRewardReferral;
use App\Domain\Pemasaran\Domain\KatalogAlertPemasaran;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AlertPemasaran;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/** Memeriksa kondisi yang pantas membangunkan tim growth; ambangnya dari setelan, satu kode satu baris per hari (MARKETING.md 5). */
final class PemeriksaAlertPemasaran
{
    public function __construct(private readonly LayananKonfigurasiPemasaran $konfigurasi) {}

    /** @return list<AlertPemasaran> */
    public function periksa(?CarbonImmutable $pada = null): array
    {
        $pada ??= CarbonImmutable::now();

        return array_values(array_filter([
            $this->trialKonversiTurun($pada),
            $this->leadTanpaAktivitas($pada),
            $this->otomasiGagal($pada),
            $this->emailBounceNaik($pada),
            $this->kampanyeTanpaTrial($pada),
            $this->rewardReferralGagal($pada),
            $this->konversiHalamanAnomali($pada),
        ]));
    }

    /** @param array<string, mixed> $rincian */
    public function catat(string $kode, string $isi, array $rincian, CarbonImmutable $pada): ?AlertPemasaran
    {
        try {
            return AlertPemasaran::create([
                'Kode' => $kode,
                'Tingkat' => KatalogAlertPemasaran::tingkat($kode),
                'Judul' => KatalogAlertPemasaran::judul($kode),
                'Isi' => mb_substr($isi, 0, 500),
                'Rincian' => $rincian,
                'Tanggal' => $pada->toDateString(),
                'DibuatPada' => $pada,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Sudah dicatat hari ini; memang hanya satu yang diinginkan.
            return null;
        }
    }

    private function trialKonversiTurun(CarbonImmutable $pada): ?AlertPemasaran
    {
        $ambang = $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::ALERT_TRIAL_KONVERSI_MIN);
        $sejak = $pada->subDays(30);

        $trial = DB::table('Trial')->whereBetween('MulaiPada', [$sejak, $pada])->count();

        if ($trial < 5) {
            return null;
        }

        $bayar = DB::table('Trial')
            ->whereBetween('MulaiPada', [$sejak, $pada])
            ->whereNotNull('KonversiPada')
            ->count();

        $persen = round($bayar / $trial * 100, 1);

        if ($persen >= $ambang) {
            return null;
        }

        return $this->catat(
            KatalogAlertPemasaran::TRIAL_KONVERSI_TURUN,
            "Konversi trial 30 hari terakhir {$persen}%, di bawah ambang {$ambang}%.",
            ['Trial' => $trial, 'Bayar' => $bayar, 'Persen' => $persen, 'Ambang' => $ambang],
            $pada,
        );
    }

    private function leadTanpaAktivitas(CarbonImmutable $pada): ?AlertPemasaran
    {
        $hari = $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::ALERT_LEAD_DIAM_HARI);
        $minimal = $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::ALERT_LEAD_DIAM_MIN);

        $diam = DB::table('Prospek')
            ->where(function ($kueri) use ($pada, $hari): void {
                $kueri->whereNull('AktivitasTerakhirPada')
                    ->orWhere('AktivitasTerakhirPada', '<', $pada->subDays($hari));
            })
            ->count();

        if ($diam < $minimal) {
            return null;
        }

        return $this->catat(
            KatalogAlertPemasaran::LEAD_TANPA_AKTIVITAS,
            "{$diam} prospek tidak beraktivitas lebih dari {$hari} hari.",
            ['Jumlah' => $diam, 'Hari' => $hari],
            $pada,
        );
    }

    private function otomasiGagal(CarbonImmutable $pada): ?AlertPemasaran
    {
        $dlq = DB::table('EksekusiOtomasiPemasaran')
            ->where('Status', StatusEksekusiOtomasi::GagalPermanen->value)
            ->whereBetween('DiperbaruiPada', [$pada->subDay(), $pada])
            ->count();

        if ($dlq < 1) {
            return null;
        }

        return $this->catat(
            KatalogAlertPemasaran::OTOMASI_GAGAL,
            "{$dlq} eksekusi otomasi berhenti permanen dalam 24 jam terakhir.",
            ['Jumlah' => $dlq],
            $pada,
        );
    }

    private function emailBounceNaik(CarbonImmutable $pada): ?AlertPemasaran
    {
        $ambang = $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::ALERT_BOUNCE_MAKS);
        $sejak = $pada->subDays(7);

        $terkirim = DB::table('PengirimanEmailPemasaran')
            ->whereNotNull('DikirimPada')
            ->whereBetween('DikirimPada', [$sejak, $pada])
            ->count();

        if ($terkirim < 20) {
            return null;
        }

        $bounce = DB::table('PengirimanEmailPemasaran')
            ->where('Status', StatusPengirimanEmail::Bounce->value)
            ->whereBetween('DikirimPada', [$sejak, $pada])
            ->count();

        $persen = round($bounce / $terkirim * 100, 1);

        if ($persen <= $ambang) {
            return null;
        }

        return $this->catat(
            KatalogAlertPemasaran::EMAIL_BOUNCE_NAIK,
            "Bounce email tujuh hari terakhir {$persen}%, di atas ambang {$ambang}%.",
            ['Terkirim' => $terkirim, 'Bounce' => $bounce, 'Persen' => $persen, 'Ambang' => $ambang],
            $pada,
        );
    }

    /** Dibaca dari metrik yang sudah dihitung, sehingga pemeriksaan ini tidak menyusuri seluruh kampanye. */
    private function kampanyeTanpaTrial(CarbonImmutable $pada): ?AlertPemasaran
    {
        $minimal = $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::ALERT_KAMPANYE_VISITOR_MIN);

        $baris = DB::table('MetrikKampanye')
            ->whereNotNull('KampanyeId')
            ->whereBetween('Tanggal', [$pada->subDays(30)->toDateString(), $pada->toDateString()])
            ->groupBy('KampanyeId')
            ->havingRaw('sum(Visitor) >= ? and sum(Trial) = 0', [$minimal])
            ->selectRaw('KampanyeId, sum(Visitor) as visitor')
            ->get();

        if ($baris->isEmpty()) {
            return null;
        }

        return $this->catat(
            KatalogAlertPemasaran::KAMPANYE_TANPA_TRIAL,
            "{$baris->count()} kampanye mengumpulkan minimal {$minimal} visitor tanpa satu pun trial.",
            ['Kampanye' => $baris->pluck('visitor', 'KampanyeId')->all()],
            $pada,
        );
    }

    private function rewardReferralGagal(CarbonImmutable $pada): ?AlertPemasaran
    {
        $gagal = DB::table('RewardReferral')
            ->where('Status', StatusRewardReferral::Gagal->value)
            ->count();

        if ($gagal < 1) {
            return null;
        }

        return $this->catat(
            KatalogAlertPemasaran::REWARD_REFERRAL_GAGAL,
            "{$gagal} imbalan referral gagal diberikan dan masih menunggu.",
            ['Jumlah' => $gagal],
            $pada,
        );
    }

    private function konversiHalamanAnomali(CarbonImmutable $pada): ?AlertPemasaran
    {
        $minimal = $this->konfigurasi->angka(KatalogKonfigurasiPemasaran::ALERT_HALAMAN_VIEW_MIN);
        $sejak = $pada->subDays(7);

        $dilihat = DB::table('EventPemasaran')
            ->where('Jenis', KatalogPeristiwaPemasaran::HALAMAN_DILIHAT)
            ->whereBetween('TerjadiPada', [$sejak, $pada])
            ->count();

        if ($dilihat < $minimal) {
            return null;
        }

        $formulir = DB::table('PengirimanFormulir')
            ->whereBetween('DikirimPada', [$sejak, $pada])
            ->count();

        if ($formulir > 0) {
            return null;
        }

        return $this->catat(
            KatalogAlertPemasaran::KONVERSI_HALAMAN_ANOMALI,
            "{$dilihat} kunjungan halaman dalam tujuh hari tanpa satu pun formulir terkirim.",
            ['Dilihat' => $dilihat, 'Formulir' => 0],
            $pada,
        );
    }
}
