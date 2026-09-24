<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Pencarian lintas modul untuk kotak cari di header (Ctrl+K).
 *
 * Tidak ada indeks pencarian terpisah: tiap modul dicari dengan LIKE pada
 * kolom yang juga dipakai kotak cari halaman daftarnya, lewat model Eloquent
 * biasa. Karena itu tenancy dan lingkup unit/ruangan ikut berlaku dari global
 * scope, dan izin dibaca dari policy `viewAny` yang sama dengan halaman
 * daftarnya: modul yang daftarnya tidak boleh dibuka pengguna tidak dicari.
 */
final class LayananPencarianGlobal
{
    public const PANJANG_MINIMUM = 2;

    public const BATAS_PER_KELOMPOK = 5;

    public function __construct(private readonly Gate $gerbang) {}

    /**
     * @return list<array{Kelompok: string, Hasil: list<array{Id: string, Judul: string, Keterangan: string, Url: string}>}>
     */
    public function cari(string $kata, Authenticatable $pengguna): array
    {
        $kata = trim($kata);

        if (mb_strlen($kata) < self::PANJANG_MINIMUM) {
            return [];
        }

        $gerbang = $this->gerbang->forUser($pengguna);
        $kelompok = [];

        foreach ($this->sumber($kata) as $label => [$kelasModel, $temukan]) {
            if (! $gerbang->allows('viewAny', $kelasModel)) {
                continue;
            }

            $hasil = $temukan();

            if ($hasil !== []) {
                $kelompok[] = ['Kelompok' => $label, 'Hasil' => $hasil];
            }
        }

        return $kelompok;
    }

    /**
     * Urutan di sini adalah urutan kelompok di layar.
     *
     * @return array<string, array{class-string<Model>, Closure(): list<array{Id: string, Judul: string, Keterangan: string, Url: string}>}>
     */
    private function sumber(string $kata): array
    {
        return [
            'Aset' => [Aset::class, fn (): array => $this->temukan(
                Aset::query()->with('lokasi:Id,Nama'),
                ['KodeAset', 'Nama', 'NomorSeri', 'NomorInventaris'],
                'KodeAset',
                'Nama',
                $kata,
                fn (Aset $aset): array => [
                    'Judul' => $aset->Nama,
                    'Keterangan' => $this->gabung([
                        $aset->KodeAset,
                        $aset->NomorSeri ? 'SN '.$aset->NomorSeri : null,
                        $aset->lokasi?->Nama,
                    ]),
                    'Url' => route('aset.show', $aset, false),
                ],
            )],
            'Perintah Kerja' => [PerintahKerja::class, fn (): array => $this->temukan(
                PerintahKerja::query(),
                ['Nomor', 'Judul'],
                'Nomor',
                'Nomor',
                $kata,
                fn (PerintahKerja $perintahKerja): array => [
                    'Judul' => $perintahKerja->Judul,
                    'Keterangan' => $this->gabung([$perintahKerja->Nomor, Str::headline($perintahKerja->Status)]),
                    'Url' => route('pemeliharaan.perintah-kerja.show', $perintahKerja, false),
                ],
            )],
            'Keluhan' => [Keluhan::class, fn (): array => $this->temukan(
                Keluhan::query(),
                ['Nomor', 'Judul'],
                'Nomor',
                'Nomor',
                $kata,
                fn (Keluhan $keluhan): array => [
                    'Judul' => $keluhan->Judul,
                    'Keterangan' => $this->gabung([$keluhan->Nomor, Str::headline($keluhan->Status)]),
                    'Url' => route('pemeliharaan.keluhan.show', $keluhan, false),
                ],
            )],
            'Suku Cadang' => [SukuCadang::class, fn (): array => $this->temukan(
                SukuCadang::query(),
                ['Kode', 'Nama', 'NomorBagian', 'KodeBatang'],
                'Kode',
                'Nama',
                $kata,
                fn (SukuCadang $sukuCadang): array => [
                    'Judul' => $sukuCadang->Nama,
                    'Keterangan' => $this->gabung([$sukuCadang->Kode, $sukuCadang->NomorBagian]),
                    'Url' => route('suku-cadang.show', $sukuCadang, false),
                ],
            )],
            'Penyedia' => [Penyedia::class, fn (): array => $this->temukan(
                Penyedia::query(),
                ['Kode', 'Nama', 'NamaLegal'],
                'Kode',
                'Nama',
                $kata,
                fn (Penyedia $penyedia): array => [
                    'Judul' => $penyedia->Nama,
                    'Keterangan' => $this->gabung([$penyedia->Kode, $penyedia->Kota]),
                    'Url' => route('penyedia.show', $penyedia, false),
                ],
            )],
            'Kontrak' => [Kontrak::class, fn (): array => $this->temukan(
                Kontrak::query()->with('penyedia:Id,Nama'),
                ['Nomor', 'Nama'],
                'Nomor',
                'Nomor',
                $kata,
                fn (Kontrak $kontrak): array => [
                    'Judul' => $kontrak->Nama,
                    'Keterangan' => $this->gabung([$kontrak->Nomor, $kontrak->penyedia?->Nama]),
                    'Url' => route('kontrak.show', $kontrak, false),
                ],
            )],
        ];
    }

    /**
     * Kode yang persis sama muncul paling atas, lalu yang berawalan kata itu.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $kueri
     * @param  list<string>  $kolomCari
     * @param  literal-string  $kolomKode  ditulis ke SQL mentah, jadi hanya boleh datang dari kode
     * @param  Closure(TModel): array{Judul: string, Keterangan: string, Url: string}  $baris
     * @return list<array{Id: string, Judul: string, Keterangan: string, Url: string}>
     */
    private function temukan(
        Builder $kueri,
        array $kolomCari,
        string $kolomKode,
        string $kolomUrut,
        string $kata,
        Closure $baris,
    ): array {
        $pola = $this->lolosLike($kata);

        return array_values($kueri
            ->where(function (Builder $dalam) use ($kolomCari, $pola): void {
                foreach ($kolomCari as $kolom) {
                    $dalam->orWhere($kolom, 'like', "%{$pola}%");
                }
            })
            ->orderByRaw("CASE WHEN {$kolomKode} = ? THEN 0 WHEN {$kolomKode} LIKE ? THEN 1 ELSE 2 END", [$kata, "{$pola}%"])
            ->orderBy($kolomUrut)
            ->limit(self::BATAS_PER_KELOMPOK)
            ->get()
            ->map(fn (Model $model): array => ['Id' => (string) $model->getKey(), ...$baris($model)])
            ->all());
    }

    /** @param  list<string|null>  $bagian */
    private function gabung(array $bagian): string
    {
        return implode(' · ', array_filter($bagian, fn (?string $satu): bool => filled($satu)));
    }

    /** Joker dari pengguna dinetralkan; tanpa ini '%' mencocokkan segalanya. */
    private function lolosLike(string $nilai): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $nilai);
    }
}
