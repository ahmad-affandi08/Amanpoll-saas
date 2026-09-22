<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Domain\Enums\ChannelKampanye;
use App\Domain\Pemasaran\Domain\Enums\ObjectiveKampanye;
use App\Domain\Pemasaran\Domain\Enums\StatusKampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeBiaya;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeChannel;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Menyimpan kampanye beserta channelnya, menjaga peta transisi statusnya (MARKETING.md 13). */
final class SimpanKampanye
{
    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    /** @param array<string, mixed> $data */
    public function jalankan(?Kampanye $kampanye, array $data): Kampanye
    {
        $status = StatusKampanye::from((string) $data['Status']);
        $channel = $this->channelDari($data);

        if ($kampanye !== null) {
            $this->pastikanTransisiSah($kampanye->Status, $status);
        } elseif ($status !== StatusKampanye::Draf) {
            throw new AturanBisnisDilanggar('Kampanye baru selalu lahir sebagai draf.');
        }

        return $this->transaksi->jalankan(function () use ($kampanye, $data, $status, $channel): Kampanye {
            $atribut = [
                'Kode' => $data['Kode'] ?? null,
                'Nama' => $data['Nama'],
                'Objective' => ObjectiveKampanye::from((string) $data['Objective']),
                'Status' => $status,
                'Budget' => $data['Budget'] ?? null,
                'Audience' => $data['Audience'] ?? null,
                'Offer' => $data['Offer'] ?? null,
                'HalamanId' => $data['HalamanId'] ?? null,
                'FormulirId' => $data['FormulirId'] ?? null,
                'UtmSource' => $data['UtmSource'] ?? null,
                'UtmMedium' => $data['UtmMedium'] ?? null,
                'UtmTerm' => $data['UtmTerm'] ?? null,
                'UtmContent' => $data['UtmContent'] ?? null,
                'MulaiPada' => $data['MulaiPada'] ?? null,
                'SelesaiPada' => $data['SelesaiPada'] ?? null,
                'Catatan' => $data['Catatan'] ?? null,
            ];

            $kampanye = $kampanye === null ? Kampanye::create($atribut) : tap($kampanye)->update($atribut);

            $this->tetapkanChannel($kampanye, $channel);

            return $kampanye;
        });
    }

    /**
     * Channel yang sudah dibelanjai tidak boleh dicabut: biayanya akan tergantung tanpa induk
     * dan CAC channel itu ikut hilang dari laporan.
     *
     * @param  list<ChannelKampanye>  $channel
     */
    private function tetapkanChannel(Kampanye $kampanye, array $channel): void
    {
        $nilai = array_map(fn (ChannelKampanye $satu): string => $satu->value, $channel);

        $dibelanjai = KampanyeBiaya::query()
            ->where('KampanyeId', $kampanye->Id)
            ->distinct()
            ->pluck('Channel')
            ->map(fn (ChannelKampanye $satu): string => $satu->value)
            ->all();

        $hilang = array_diff($dibelanjai, $nilai);

        if ($hilang !== []) {
            throw new AturanBisnisDilanggar(
                'Channel '.implode(', ', $hilang).' sudah punya biaya tercatat dan tidak dapat dilepas.',
            );
        }

        KampanyeChannel::query()->where('KampanyeId', $kampanye->Id)->whereNotIn('Channel', $nilai)->delete();

        foreach ($nilai as $satu) {
            KampanyeChannel::query()->firstOrCreate(['KampanyeId' => $kampanye->Id, 'Channel' => $satu]);
        }
    }

    private function pastikanTransisiSah(StatusKampanye $asal, StatusKampanye $tujuan): void
    {
        if ($asal === $tujuan || $asal->bolehPindahKe($tujuan)) {
            return;
        }

        $sah = implode(', ', array_map(
            fn (StatusKampanye $satu): string => $satu->value,
            $asal->tujuanSah(),
        ));

        throw new AturanBisnisDilanggar(
            "Kampanye berstatus {$asal->value} hanya dapat berpindah ke {$sah}.",
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<ChannelKampanye>
     */
    private function channelDari(array $data): array
    {
        $mentah = is_array($data['Channel'] ?? null) ? $data['Channel'] : [];
        $channel = [];

        foreach ($mentah as $satu) {
            $channel[] = ChannelKampanye::from((string) $satu);
        }

        return array_values(array_unique($channel, SORT_REGULAR));
    }
}
