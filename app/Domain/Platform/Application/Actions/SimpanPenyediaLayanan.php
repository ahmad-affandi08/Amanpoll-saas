<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;

/**
 * Menyimpan setelan dan kredensial satu penyedia dari konsol platform (PRD 8.23).
 *
 * Isian rahasia yang dikirim kosong mempertahankan nilai lama, sebab peramban tidak
 * pernah menerima nilainya. Penyedia hanya bisa diaktifkan bila seluruh isian wajibnya
 * terisi. WhatsApp hanya punya satu penyedia aktif; pembayaran boleh banyak, dengan
 * satu yang utama.
 */
final class SimpanPenyediaLayanan
{
    public function __construct(
        private readonly KatalogPenyediaLayanan $katalog,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  array{Aktif?: bool, Utama?: bool, ModeUji?: bool, Kredensial?: array<string, mixed>}  $data
     */
    public function jalankan(
        KategoriPenyediaLayanan $kategori,
        string $kode,
        array $data,
        ?string $adminId = null,
    ): PenyediaLayananPlatform {
        $deskripsi = $this->katalog->untuk($kategori, $kode)
            ?? throw new DataTidakDitemukan("Penyedia {$kode} tidak dikenal.");

        return $this->transaksi->jalankan(function () use ($kategori, $kode, $data, $adminId, $deskripsi): PenyediaLayananPlatform {
            $baris = PenyediaLayananPlatform::query()
                ->where('Kategori', $kategori->value)
                ->where('Kode', $kode)
                ->lockForUpdate()
                ->first() ?? new PenyediaLayananPlatform(['Kategori' => $kategori, 'Kode' => $kode]);

            $sidikLama = $baris->SidikKredensial;
            $nilai = $this->gabungkanKredensial($deskripsi, $baris->nilaiKredensial(), (array) ($data['Kredensial'] ?? []));
            $aktif = (bool) ($data['Aktif'] ?? $baris->Aktif);

            if ($aktif) {
                $this->pastikanIsianWajibLengkap($deskripsi, $nilai);
            }

            $baris->fill([
                'Aktif' => $aktif,
                'Utama' => $aktif && (bool) ($data['Utama'] ?? $baris->Utama),
                'ModeUji' => $deskripsi->mendukungModeUji() && (bool) ($data['ModeUji'] ?? $baris->ModeUji),
                'KredensialTerenkripsi' => $nilai === [] ? null : $nilai,
                'SidikKredensial' => $nilai === [] ? null : $this->sidik($nilai),
                'DiperbaruiOleh' => $adminId,
            ]);
            $baris->save();

            $this->rapikanKategori($kategori, $baris);
            $baris->refresh();

            $this->audit->catat(
                'PenyediaLayanan.Diubah',
                'PenyediaLayananPlatform',
                $baris->Id,
                dataSesudah: [
                    'Kategori' => $kategori->value,
                    'Kode' => $kode,
                    'Aktif' => $baris->Aktif,
                    'Utama' => $baris->Utama,
                    'ModeUji' => $baris->ModeUji,
                    // Hanya penanda bahwa kredensial berganti; nilainya tidak pernah masuk audit.
                    'KredensialBerubah' => $sidikLama !== $baris->SidikKredensial,
                ],
            );

            return $baris;
        });
    }

    /**
     * @param  array<string, string>  $lama
     * @param  array<string, mixed>  $masukan
     * @return array<string, string>
     */
    private function gabungkanKredensial(DeskripsiPenyediaLayanan $deskripsi, array $lama, array $masukan): array
    {
        $hasil = [];

        foreach ($deskripsi->isian() as $isian) {
            $dikirim = array_key_exists($isian->kunci, $masukan);
            $baru = trim((string) (is_scalar($masukan[$isian->kunci] ?? null) ? $masukan[$isian->kunci] : ''));

            $nilai = match (true) {
                $isian->rahasia && $baru === '' => $lama[$isian->kunci] ?? '',
                $dikirim => $baru,
                default => $lama[$isian->kunci] ?? (string) $isian->bawaan,
            };

            if ($nilai !== '' && $isian->pilihan !== [] && ! in_array($nilai, $isian->pilihan, true)) {
                throw new AturanBisnisDilanggar("Pilihan {$isian->label} tidak dikenal.");
            }

            if ($nilai !== '') {
                $hasil[$isian->kunci] = $nilai;
            }
        }

        return $hasil;
    }

    /** @param  array<string, string>  $nilai */
    private function pastikanIsianWajibLengkap(DeskripsiPenyediaLayanan $deskripsi, array $nilai): void
    {
        $kosong = array_map(
            fn (IsianKredensial $isian): string => $isian->label,
            array_filter(
                $deskripsi->isian(),
                fn (IsianKredensial $isian): bool => $isian->wajib && ($nilai[$isian->kunci] ?? '') === '',
            ),
        );

        if ($kosong !== []) {
            throw new AturanBisnisDilanggar(
                "{$deskripsi->nama()} belum bisa diaktifkan. Lengkapi dulu: ".implode(', ', $kosong).'.',
            );
        }
    }

    /**
     * Menjaga dua aturan kategori: satu penyedia utama, dan untuk WhatsApp hanya satu
     * yang aktif. Penyedia aktif pertama otomatis menjadi utama supaya pemakai selalu
     * punya pilihan bawaan.
     */
    private function rapikanKategori(KategoriPenyediaLayanan $kategori, PenyediaLayananPlatform $disimpan): void
    {
        $lain = PenyediaLayananPlatform::query()
            ->where('Kategori', $kategori->value)
            ->whereKeyNot($disimpan->Id);

        if ($disimpan->Aktif && ! $kategori->bolehBanyakAktif()) {
            (clone $lain)->update(['Aktif' => false, 'Utama' => false]);
            $disimpan->forceFill(['Utama' => true])->save();

            return;
        }

        if ($disimpan->Utama) {
            (clone $lain)->update(['Utama' => false]);

            return;
        }

        $adaUtama = PenyediaLayananPlatform::query()
            ->where('Kategori', $kategori->value)
            ->where('Aktif', true)
            ->where('Utama', true)
            ->exists();

        if (! $adaUtama) {
            PenyediaLayananPlatform::query()
                ->where('Kategori', $kategori->value)
                ->where('Aktif', true)
                ->orderByRaw('Id = ? DESC', [$disimpan->Id])
                ->orderBy('Kode')
                ->first()
                ?->forceFill(['Utama' => true])
                ->save();
        }
    }

    /** @param  array<string, string>  $nilai */
    private function sidik(array $nilai): string
    {
        ksort($nilai);

        // HMAC berkunci APP_KEY: sidik tidak bisa dipakai menebak nilai kredensial yang pendek.
        return hash_hmac(
            'sha256',
            (string) json_encode($nilai, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            (string) config('app.key'),
        );
    }
}
