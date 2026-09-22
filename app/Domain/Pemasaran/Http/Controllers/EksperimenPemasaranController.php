<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Application\Services\PenghitungHasilEksperimen;
use App\Domain\Pemasaran\Application\Services\PenilaiEksperimen;
use App\Domain\Pemasaran\Domain\Enums\MetrikEksperimen;
use App\Domain\Pemasaran\Domain\Enums\StatusEksperimen;
use App\Domain\Pemasaran\Domain\Enums\TargetEksperimen;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Http\Requests\SimpanEksperimenRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksperimenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HasilEksperimen;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VarianEksperimen;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Konsol eksperimen A/B beserta penilaiannya (MARKETING.md 22). */
final class EksperimenPemasaranController extends Controller
{
    public function __construct(
        private readonly PenilaiEksperimen $penilai,
        private readonly PenghitungHasilEksperimen $penghitung,
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    public function index(): Response
    {
        $eksperimen = EksperimenPemasaran::query()
            ->with(['varian', 'hasil', 'pemenang:Id,Kode,Nama'])
            ->orderByDesc('DibuatPada')
            ->get();

        return Inertia::render('Pemasaran/Eksperimen', [
            'eksperimen' => $eksperimen->map(fn (EksperimenPemasaran $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'Target' => $satu->Target->value,
                'MetrikUtama' => $satu->MetrikUtama->value,
                'LabelMetrik' => $satu->MetrikUtama->label(),
                'Hipotesis' => $satu->Hipotesis,
                'Status' => $satu->Status->value,
                'TujuanStatus' => array_map(
                    fn (StatusEksperimen $tujuan): string => $tujuan->value,
                    $satu->Status->tujuanSah(),
                ),
                'MinimumSampel' => $satu->MinimumSampel,
                'Pemenang' => $satu->pemenang?->Kode,
                'AlasanKeputusan' => $satu->AlasanKeputusan,
                'DiputuskanPada' => $satu->DiputuskanPada?->toIso8601String(),
                'Varian' => $satu->varian->map(fn (VarianEksperimen $varian): array => [
                    'Id' => $varian->Id,
                    'Kode' => $varian->Kode,
                    'Nama' => $varian->Nama,
                    'Bobot' => $varian->Bobot,
                    'Kontrol' => $varian->Kontrol,
                    'Peserta' => $this->penghitung->peserta($varian),
                    'Hasil' => $satu->hasil
                        ->where('VarianEksperimenId', $varian->Id)
                        ->mapWithKeys(fn (HasilEksperimen $baris): array => [
                            $baris->Metrik->value => [
                                'Pembilang' => $baris->Pembilang,
                                'Penyebut' => $baris->Penyebut,
                                'Rasio' => (float) $baris->Rasio,
                            ],
                        ])->all(),
                ])->all(),
                'Penilaian' => $this->penilaianRingkas($satu),
            ])->all(),
            'pilihan' => [
                'Target' => array_column(TargetEksperimen::cases(), 'value'),
                'Metrik' => array_map(
                    fn (MetrikEksperimen $satu): array => ['Kunci' => $satu->value, 'Label' => $satu->label()],
                    MetrikEksperimen::cases(),
                ),
                'MinimumSampelBawaan' => $this->konfigurasi->angka(
                    KatalogKonfigurasiPemasaran::EKSPERIMEN_MINIMUM_SAMPEL,
                ),
            ],
        ]);
    }

    public function store(SimpanEksperimenRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $eksperimen = $this->transaksi->jalankan(function () use ($data): EksperimenPemasaran {
            $eksperimen = EksperimenPemasaran::create([
                'Kode' => $data['Kode'],
                'Nama' => $data['Nama'],
                'Target' => $data['Target'],
                'MetrikUtama' => $data['MetrikUtama'],
                'Hipotesis' => $data['Hipotesis'] ?? null,
                'Status' => StatusEksperimen::Draf,
                'MinimumSampel' => $data['MinimumSampel'],
            ]);

            $this->tetapkanVarian($eksperimen, $data['Varian']);

            return $eksperimen;
        });

        $this->audit->catat('Eksperimen.Dibuat', 'EksperimenPemasaran', $eksperimen->Id, dataSesudah: [
            'Kode' => $eksperimen->Kode,
            'MinimumSampel' => $eksperimen->MinimumSampel,
        ]);

        return back()->with('sukses', 'Eksperimen dibuat sebagai draf.');
    }

    /** Varian yang sudah punya peserta tidak dapat diubah; mengubahnya membatalkan arti angkanya. */
    public function update(SimpanEksperimenRequest $request, EksperimenPemasaran $eksperimen): RedirectResponse
    {
        $data = $request->validated();

        $this->transaksi->jalankan(function () use ($eksperimen, $data): void {
            $eksperimen->update([
                'Kode' => $data['Kode'],
                'Nama' => $data['Nama'],
                'Target' => $data['Target'],
                'MetrikUtama' => $data['MetrikUtama'],
                'Hipotesis' => $data['Hipotesis'] ?? null,
                'MinimumSampel' => $data['MinimumSampel'],
            ]);

            $this->tetapkanVarian($eksperimen, $data['Varian']);
        });

        return back()->with('sukses', 'Eksperimen diperbarui.');
    }

    public function pindahkanStatus(Request $request, EksperimenPemasaran $eksperimen): RedirectResponse
    {
        /** @var array{Status: string} $sah */
        $sah = $request->validate(['Status' => ['required', Rule::enum(StatusEksperimen::class)]]);

        $tujuan = StatusEksperimen::from($sah['Status']);
        $asal = $eksperimen->Status;

        if (! $asal->bolehPindahKe($tujuan)) {
            $daftar = implode(', ', array_map(
                fn (StatusEksperimen $satu): string => $satu->value,
                $asal->tujuanSah(),
            )) ?: 'tidak ke mana-mana';

            throw new AturanBisnisDilanggar("Eksperimen berstatus {$asal->value} hanya dapat berpindah ke {$daftar}.");
        }

        $eksperimen->Status = $tujuan;

        if ($tujuan === StatusEksperimen::Aktif && $eksperimen->MulaiPada === null) {
            $eksperimen->MulaiPada = CarbonImmutable::now();
        }

        if ($tujuan === StatusEksperimen::Selesai) {
            $eksperimen->SelesaiPada = CarbonImmutable::now();
        }

        $eksperimen->save();

        return back()->with('sukses', "Eksperimen berpindah ke {$tujuan->value}.");
    }

    public function hitung(EksperimenPemasaran $eksperimen): RedirectResponse
    {
        $this->penghitung->hitung($eksperimen);

        return back()->with('sukses', 'Hasil eksperimen dihitung ulang.');
    }

    /** Tombol ini pun tidak dapat melewati ambang sampel; penilaiannya yang memutuskan. */
    public function nyatakanPemenang(EksperimenPemasaran $eksperimen): RedirectResponse
    {
        $pemenang = $this->penilai->nyatakanPemenang($eksperimen);

        return back()->with('sukses', "Varian {$pemenang->Kode} dinyatakan menang.");
    }

    /** @return array{BolehDinyatakan: bool, Alasan: string|null, Pemenang: string|null} */
    private function penilaianRingkas(EksperimenPemasaran $eksperimen): array
    {
        if ($eksperimen->varian->count() < 2) {
            return [
                'BolehDinyatakan' => false,
                'Alasan' => 'Eksperimen memerlukan setidaknya dua varian.',
                'Pemenang' => null,
            ];
        }

        $penilaian = $this->penilai->nilai($eksperimen);

        return [
            'BolehDinyatakan' => $penilaian->bolehDinyatakan,
            'Alasan' => $penilaian->alasan,
            'Pemenang' => $penilaian->pemenang?->Kode,
        ];
    }

    /** @param list<array{Kode: string, Nama: string, Bobot: int, Kontrol: bool}> $varian */
    private function tetapkanVarian(EksperimenPemasaran $eksperimen, array $varian): void
    {
        $kode = array_map(fn (array $satu): string => $satu['Kode'], $varian);

        $berpeserta = VarianEksperimen::query()
            ->where('EksperimenPemasaranId', $eksperimen->Id)
            ->whereNotIn('Kode', $kode)
            ->whereHas('partisipasi')
            ->pluck('Kode')
            ->all();

        if ($berpeserta !== []) {
            throw new AturanBisnisDilanggar(
                'Varian '.implode(', ', $berpeserta).' sudah punya peserta dan tidak dapat dihapus.',
            );
        }

        VarianEksperimen::query()
            ->where('EksperimenPemasaranId', $eksperimen->Id)
            ->whereNotIn('Kode', $kode)
            ->delete();

        foreach ($varian as $satu) {
            VarianEksperimen::query()->updateOrCreate(
                ['EksperimenPemasaranId' => $eksperimen->Id, 'Kode' => $satu['Kode']],
                ['Nama' => $satu['Nama'], 'Bobot' => $satu['Bobot'], 'Kontrol' => $satu['Kontrol']],
            );
        }
    }
}
