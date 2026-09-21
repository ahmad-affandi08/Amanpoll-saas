<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Application\Services;

use App\Domain\Kontrak\Domain\Enums\StatusKontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\KontrakAset;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Menjawab pertanyaan Gate 17: pekerjaan vendor pada sebuah aset ditelusuri ke
 * kontrak aktif mana, pada tanggal berapa, dan milik penyedia yang mana.
 */
final class LayananCakupanKontrak
{
    /**
     * Kontrak aktif yang mencakup aset pada tanggal tertentu.
     *
     * @return Collection<int, Kontrak>
     */
    public function kontrakAktifUntukAset(string $asetId, ?CarbonImmutable $tanggal = null, ?string $penyediaId = null): Collection
    {
        $pada = ($tanggal ?? CarbonImmutable::today())->toDateString();

        return Kontrak::query()
            ->with(['penyedia', 'tingkatLayanan'])
            ->where('Status', StatusKontrak::Aktif->value)
            ->where('MulaiPada', '<=', $pada)
            ->where('BerakhirPada', '>=', $pada)
            ->when($penyediaId !== null, fn ($query) => $query->where('PenyediaId', $penyediaId))
            ->whereHas('kontrakAset', fn ($query) => $query
                ->where('AsetId', $asetId)
                ->where(fn ($sub) => $sub->whereNull('MulaiPada')->orWhere('MulaiPada', '<=', $pada))
                ->where(fn ($sub) => $sub->whereNull('BerakhirPada')->orWhere('BerakhirPada', '>=', $pada)))
            ->orderBy('BerakhirPada')
            ->get();
    }

    /**
     * Penelusuran satu biaya vendor pada perintah kerja ke kontrak yang menaunginya.
     *
     * @return array{tercakup: bool, kontrak: Kontrak|null}
     */
    public function telusuriPekerjaanVendor(string $asetId, string $penyediaId, CarbonImmutable $tanggalKerja): array
    {
        $kontrak = $this->kontrakAktifUntukAset($asetId, $tanggalKerja, $penyediaId)->first();

        return ['tercakup' => $kontrak instanceof Kontrak, 'kontrak' => $kontrak];
    }

    /**
     * Aset yang tercakup kontrak beserta periodenya.
     *
     * @return Collection<int, KontrakAset>
     */
    public function asetTercakup(Kontrak $kontrak): Collection
    {
        return $kontrak->kontrakAset()->with('aset')->orderBy('DibuatPada')->get();
    }
}
