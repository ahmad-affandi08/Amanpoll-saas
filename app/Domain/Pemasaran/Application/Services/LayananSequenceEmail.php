<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\StatusPendaftaranSequence;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LangkahSequenceEmail;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PendaftaranSequence;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SequenceEmailPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Menyusun sequence; langkah terkunci selagi ada pendaftaran berjalan karena kirimannya sudah terjadwal (MARKETING.md 15). */
final class LayananSequenceEmail
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array{Kode: string, Nama: string, Keterangan?: string|null, Aktif: bool} $data */
    public function simpan(?SequenceEmailPemasaran $sequence, array $data): SequenceEmailPemasaran
    {
        return $this->transaksi->jalankan(function () use ($sequence, $data): SequenceEmailPemasaran {
            $sebelum = $sequence?->only(['Kode', 'Nama', 'Aktif']);

            if ($sequence === null) {
                $sequence = SequenceEmailPemasaran::create($data);
            } else {
                $sequence->fill($data);
                $sequence->save();
            }

            $this->audit->catat(
                $sebelum === null ? 'SequenceEmailPemasaran.Dibuat' : 'SequenceEmailPemasaran.Diubah',
                'SequenceEmailPemasaran',
                $sequence->Id,
                dataSebelum: $sebelum,
                dataSesudah: $sequence->only(['Kode', 'Nama', 'Aktif']),
            );

            return $sequence;
        });
    }

    /** @param array{TemplateEmailPemasaranId: string, Urutan: int, HariKe: int, Aktif: bool} $data */
    public function simpanLangkah(
        SequenceEmailPemasaran $sequence,
        ?LangkahSequenceEmail $langkah,
        array $data,
    ): LangkahSequenceEmail {
        $this->pastikanBelumBerjalan($sequence);

        $bentrok = LangkahSequenceEmail::query()
            ->where('SequenceEmailPemasaranId', $sequence->Id)
            ->where('Urutan', $data['Urutan'])
            ->when($langkah !== null, fn ($kueri) => $kueri->where('Id', '!=', $langkah?->Id))
            ->exists();

        if ($bentrok) {
            throw new AturanBisnisDilanggar('Urutan '.$data['Urutan'].' sudah dipakai langkah lain.');
        }

        return $this->transaksi->jalankan(function () use ($sequence, $langkah, $data): LangkahSequenceEmail {
            if ($langkah === null) {
                $langkah = LangkahSequenceEmail::create(
                    $data + ['SequenceEmailPemasaranId' => $sequence->Id],
                );
            } else {
                $langkah->fill($data);
                $langkah->save();
            }

            $this->audit->catat(
                'LangkahSequenceEmail.Disimpan',
                'LangkahSequenceEmail',
                $langkah->Id,
                dataSesudah: $langkah->only(['SequenceEmailPemasaranId', 'Urutan', 'HariKe', 'Aktif']),
            );

            return $langkah;
        });
    }

    public function hapusLangkah(SequenceEmailPemasaran $sequence, LangkahSequenceEmail $langkah): void
    {
        $this->pastikanBelumBerjalan($sequence);

        $this->audit->catat(
            'LangkahSequenceEmail.Dihapus',
            'LangkahSequenceEmail',
            $langkah->Id,
            dataSebelum: $langkah->only(['SequenceEmailPemasaranId', 'Urutan', 'HariKe']),
        );

        $langkah->delete();
    }

    private function pastikanBelumBerjalan(SequenceEmailPemasaran $sequence): void
    {
        $berjalan = PendaftaranSequence::query()
            ->where('SequenceEmailPemasaranId', $sequence->Id)
            ->where('Status', StatusPendaftaranSequence::Berjalan->value)
            ->count();

        if ($berjalan > 0) {
            throw new AturanBisnisDilanggar(
                "Ada {$berjalan} pendaftaran yang masih berjalan; kirimannya sudah terjadwal dan tidak "
                .'akan ikut berubah. Nonaktifkan sequence ini lalu buat versi barunya.',
            );
        }
    }
}
