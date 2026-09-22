<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\ChannelKampanye;
use App\Domain\Pemasaran\Domain\Enums\ObjectiveKampanye;
use App\Domain\Pemasaran\Domain\Enums\StatusKampanye;
use App\Domain\Pemasaran\Http\Requests\SimpanKampanyeRequest;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeChannel;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\UtmPemasaran;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Kampanye pemasaran (MARKETING.md 13). */
final class KampanyeController extends Controller
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    public function index(): Response
    {
        $kampanye = Kampanye::query()
            ->with('channel')
            ->withCount('channel')
            ->orderByDesc('DibuatPada')
            ->get();

        // Jumlah kunjungan per kampanye dibaca sekali sebagai peta, bukan satu query per baris.
        $kunjungan = UtmPemasaran::query()
            ->whereNotNull('KampanyeId')
            ->selectRaw('KampanyeId, COUNT(*) as Jumlah')
            ->groupBy('KampanyeId')
            ->pluck('Jumlah', 'KampanyeId');

        return Inertia::render('Pemasaran/Kampanye', [
            'kampanye' => $kampanye->map(fn (Kampanye $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'Objective' => $satu->Objective,
                'Status' => $satu->Status,
                'MulaiPada' => $satu->MulaiPada?->toDateString(),
                'SelesaiPada' => $satu->SelesaiPada?->toDateString(),
                'Channel' => $satu->channel->pluck('Channel')->all(),
                'JumlahKunjungan' => (int) ($kunjungan[$satu->Id] ?? 0),
            ])->all(),
            'pilihan' => [
                'Status' => array_column(StatusKampanye::cases(), 'value'),
                'Objective' => array_column(ObjectiveKampanye::cases(), 'value'),
                'Channel' => array_column(ChannelKampanye::cases(), 'value'),
            ],
        ]);
    }

    public function store(SimpanKampanyeRequest $request): RedirectResponse
    {
        $kampanye = $this->simpan(null, $request->validated());

        $this->audit->catat('Kampanye.Dibuat', 'Kampanye', $kampanye->Id, dataSesudah: [
            'Kode' => $kampanye->Kode,
            'Nama' => $kampanye->Nama,
        ]);

        return back()->with('sukses', 'Kampanye berhasil dibuat.');
    }

    public function update(SimpanKampanyeRequest $request, Kampanye $kampanye): RedirectResponse
    {
        $sebelum = ['Nama' => $kampanye->Nama, 'Status' => $kampanye->Status];
        $kampanye = $this->simpan($kampanye, $request->validated());

        $this->audit->catat(
            'Kampanye.Diubah',
            'Kampanye',
            $kampanye->Id,
            dataSebelum: $sebelum,
            dataSesudah: ['Nama' => $kampanye->Nama, 'Status' => $kampanye->Status],
        );

        return back()->with('sukses', 'Kampanye berhasil diperbarui.');
    }

    /** @param array<string, mixed> $data */
    private function simpan(?Kampanye $kampanye, array $data): Kampanye
    {
        return $this->transaksi->jalankan(function () use ($kampanye, $data): Kampanye {
            $atribut = [
                'Kode' => $data['Kode'],
                'Nama' => $data['Nama'],
                'Objective' => $data['Objective'],
                'Status' => $data['Status'],
                'MulaiPada' => $data['MulaiPada'] ?? null,
                'SelesaiPada' => $data['SelesaiPada'] ?? null,
                'Catatan' => $data['Catatan'] ?? null,
            ];

            if ($kampanye === null) {
                $kampanye = Kampanye::create($atribut);
            } else {
                $kampanye->update($atribut);
            }

            /** @var list<string> $channel */
            $channel = $data['Channel'] ?? [];

            KampanyeChannel::query()->where('KampanyeId', $kampanye->Id)->delete();
            foreach ($channel as $satu) {
                KampanyeChannel::create(['KampanyeId' => $kampanye->Id, 'Channel' => $satu]);
            }

            return $kampanye;
        });
    }
}
