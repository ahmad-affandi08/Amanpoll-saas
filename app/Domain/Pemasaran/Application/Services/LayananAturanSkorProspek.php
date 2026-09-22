<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaSkor;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanSkorProspek;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Pembaca dan penulis aturan bobot skor (MARKETING.md 5.4).
 *
 * Kode peristiwa divalidasi saat disimpan, bukan diabaikan saat dihitung. Itu
 * perbedaan pentingnya dengan bentuk lama: bobot bagi peristiwa yang salah ketik
 * dulu tersimpan diam-diam dan tidak pernah menyumbang apa pun, dan tidak ada
 * yang tahu sampai seseorang bertanya mengapa skornya tidak naik.
 *
 * Dibaca pada setiap perhitungan skor, jadi disimpan di cache dan dibuang pada
 * setiap penyuntingan.
 */
final class LayananAturanSkorProspek
{
    private const KUNCI_CACHE = 'pemasaran:aturan-skor';

    private const UMUR_DETIK = 300;

    public function __construct(
        private readonly Cache $cache,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * Bobot yang benar-benar berlaku: aktif, dan sinyalnya punya penghasil.
     *
     * @return array<string, int>
     */
    public function bobotBerlaku(): array
    {
        /** @var array<string, int> $bobot */
        $bobot = $this->cache->remember(
            self::KUNCI_CACHE,
            self::UMUR_DETIK,
            fn (): array => AturanSkorProspek::query()
                ->where('Aktif', true)
                ->get()
                ->filter(fn (AturanSkorProspek $aturan): bool => $aturan->berlaku())
                ->mapWithKeys(fn (AturanSkorProspek $aturan): array => [$aturan->Peristiwa => $aturan->Bobot])
                ->all(),
        );

        return $bobot;
    }

    /** @return array<string, string> peristiwa => Id */
    public function petaId(): array
    {
        /** @var array<string, string> $peta */
        $peta = AturanSkorProspek::query()->pluck('Id', 'Peristiwa')->all();

        return $peta;
    }

    /** @param array<string, mixed> $data */
    public function simpan(?AturanSkorProspek $aturan, array $data): AturanSkorProspek
    {
        $peristiwa = (string) $data['Peristiwa'];

        if (! KatalogPeristiwaSkor::dikenal($peristiwa)) {
            throw new AturanBisnisDilanggar("Sinyal skor {$peristiwa} tidak dikenal.");
        }

        $atribut = [
            'Peristiwa' => $peristiwa,
            'Bobot' => (int) $data['Bobot'],
            'Aktif' => (bool) ($data['Aktif'] ?? true),
            'Keterangan' => $data['Keterangan'] ?? null,
        ];

        if ($aturan === null) {
            $aturan = AturanSkorProspek::create($atribut);
            $this->audit->catat('AturanSkorProspek.Dibuat', 'AturanSkorProspek', $aturan->Id, dataSesudah: $atribut);
        } else {
            $sebelum = [
                'Peristiwa' => $aturan->Peristiwa,
                'Bobot' => $aturan->Bobot,
                'Aktif' => $aturan->Aktif,
            ];
            $aturan->update($atribut);
            $this->audit->catat(
                'AturanSkorProspek.Diubah',
                'AturanSkorProspek',
                $aturan->Id,
                dataSebelum: $sebelum,
                dataSesudah: $atribut,
            );
        }

        $this->buangCache();

        return $aturan;
    }

    public function hapus(AturanSkorProspek $aturan): void
    {
        $this->audit->catat(
            'AturanSkorProspek.Dihapus',
            'AturanSkorProspek',
            $aturan->Id,
            dataSebelum: ['Peristiwa' => $aturan->Peristiwa, 'Bobot' => $aturan->Bobot],
        );

        $aturan->delete();
        $this->buangCache();
    }

    public function buangCache(): void
    {
        $this->cache->forget(self::KUNCI_CACHE);
    }
}
