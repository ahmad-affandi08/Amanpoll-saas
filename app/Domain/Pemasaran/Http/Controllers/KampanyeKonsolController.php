<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Http\Requests\SimpanKampanyeBiayaRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanKampanyeKontenRequest;
use App\Domain\Pemasaran\Http\Requests\SimpanKampanyeTargetRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeBiaya;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeKonten;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeTarget;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Http\RedirectResponse;

/** Konsol biaya, target, dan konten satu kampanye (MARKETING.md 13, FASE 38.08). */
final class KampanyeKonsolController extends Controller
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** Satu channel satu hari hanya punya satu angka; mengirim ulang berarti mengoreksinya. */
    public function simpanBiaya(SimpanKampanyeBiayaRequest $request, Kampanye $kampanye): RedirectResponse
    {
        $data = $request->validated();

        $biaya = KampanyeBiaya::query()->updateOrCreate(
            [
                'KampanyeId' => $kampanye->Id,
                'Channel' => $data['Channel'],
                'Tanggal' => $data['Tanggal'],
            ],
            [
                'Jumlah' => $data['Jumlah'],
                'Catatan' => $data['Catatan'] ?? null,
            ],
        );

        $this->audit->catat('KampanyeBiaya.Disimpan', 'KampanyeBiaya', $biaya->Id, dataSesudah: [
            'KampanyeId' => $kampanye->Id,
            'Channel' => $data['Channel'],
            'Tanggal' => $data['Tanggal'],
            'Jumlah' => $data['Jumlah'],
        ]);

        return back()->with('sukses', 'Biaya kampanye tersimpan.');
    }

    public function hapusBiaya(Kampanye $kampanye, KampanyeBiaya $biaya): RedirectResponse
    {
        $this->pastikanMilik($kampanye, $biaya->KampanyeId);

        $this->audit->catat('KampanyeBiaya.Dihapus', 'KampanyeBiaya', $biaya->Id, dataSebelum: [
            'Channel' => $biaya->Channel->value,
            'Tanggal' => $biaya->Tanggal->toDateString(),
            'Jumlah' => (float) $biaya->Jumlah,
        ]);
        $biaya->delete();

        return back()->with('sukses', 'Biaya kampanye dihapus.');
    }

    /** Target dikirim utuh: yang tidak ada di kiriman berarti dicabut. */
    public function simpanTarget(SimpanKampanyeTargetRequest $request, Kampanye $kampanye): RedirectResponse
    {
        /** @var list<array{Metrik: string, Nilai: numeric-string|float|int}> $target */
        $target = $request->validated()['Target'];

        $this->transaksi->jalankan(function () use ($kampanye, $target): void {
            $metrik = array_map(fn (array $satu): string => $satu['Metrik'], $target);

            KampanyeTarget::query()
                ->where('KampanyeId', $kampanye->Id)
                ->whereNotIn('Metrik', $metrik)
                ->delete();

            foreach ($target as $satu) {
                KampanyeTarget::query()->updateOrCreate(
                    ['KampanyeId' => $kampanye->Id, 'Metrik' => $satu['Metrik']],
                    ['Nilai' => $satu['Nilai']],
                );
            }
        });

        $this->audit->catat('KampanyeTarget.Disimpan', 'Kampanye', $kampanye->Id, dataSesudah: [
            'Target' => $target,
        ]);

        return back()->with('sukses', 'Target kampanye tersimpan.');
    }

    public function simpanKonten(SimpanKampanyeKontenRequest $request, Kampanye $kampanye): RedirectResponse
    {
        $data = $request->validated();

        $konten = KampanyeKonten::create([
            'KampanyeId' => $kampanye->Id,
            'Jenis' => $data['Jenis'],
            'Judul' => $data['Judul'],
            'Tautan' => $data['Tautan'] ?? null,
            'Catatan' => $data['Catatan'] ?? null,
            'Urutan' => (int) ($data['Urutan'] ?? 0),
        ]);

        $this->audit->catat('KampanyeKonten.Ditambah', 'KampanyeKonten', $konten->Id, dataSesudah: [
            'KampanyeId' => $kampanye->Id,
            'Jenis' => $data['Jenis'],
            'Judul' => $data['Judul'],
        ]);

        return back()->with('sukses', 'Konten kampanye ditambahkan.');
    }

    public function hapusKonten(Kampanye $kampanye, KampanyeKonten $konten): RedirectResponse
    {
        $this->pastikanMilik($kampanye, $konten->KampanyeId);

        $this->audit->catat('KampanyeKonten.Dihapus', 'KampanyeKonten', $konten->Id, dataSebelum: [
            'Judul' => $konten->Judul,
        ]);
        $konten->delete();

        return back()->with('sukses', 'Konten kampanye dihapus.');
    }

    /** Rute bersarang tetap menerima id milik kampanye lain bila tidak diperiksa di sini. */
    private function pastikanMilik(Kampanye $kampanye, string $kampanyeId): void
    {
        if ($kampanyeId !== $kampanye->Id) {
            throw new DataTidakDitemukan('Baris itu bukan milik kampanye ini.');
        }
    }
}
