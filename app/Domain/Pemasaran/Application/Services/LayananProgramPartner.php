<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\JenisKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\JenisPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanKomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramPartner;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Database\UniqueConstraintViolationException;

/** Menyusun program partner, partnernya, dan aturan komisinya dari konsol platform (MARKETING.md 21). */
final class LayananProgramPartner
{
    public const PANJANG_KODE = 8;

    private const PERCOBAAN_MAKS = 5;

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array{Kode: string, Nama: string, Keterangan: string|null, HariAtribusi: int, Aktif: bool} $data */
    public function simpanProgram(?ProgramPartner $program, array $data): ProgramPartner
    {
        return $this->transaksi->jalankan(function () use ($program, $data): ProgramPartner {
            $sebelum = $program?->only(['Kode', 'Nama', 'HariAtribusi', 'Aktif']);

            if ($program === null) {
                $program = ProgramPartner::create($data);
            } else {
                $program->fill($data);
                $program->save();
            }

            $this->audit->catat(
                $sebelum === null ? 'ProgramPartner.Dibuat' : 'ProgramPartner.Diubah',
                'ProgramPartner',
                $program->Id,
                dataSebelum: $sebelum,
                dataSesudah: $program->only(['Kode', 'Nama', 'HariAtribusi', 'Aktif']),
            );

            return $program;
        });
    }

    /**
     * Kata sandi hanya ditulis bila diisi, supaya menyunting data partner tidak mengunci pemiliknya keluar.
     *
     * @param  array{ProgramPartnerId: string, NamaPerusahaan: string, Jenis: string, NamaPic: string, EmailPic: string, TeleponPic: string|null, Status: string, ReferensiPerjanjian: string|null, ReferensiPayout: string|null, KataSandi?: string|null}  $data
     */
    public function simpanPartner(?Partner $partner, array $data): Partner
    {
        $jenis = JenisPartner::tryFrom($data['Jenis']);

        if ($jenis === null) {
            throw new AturanBisnisDilanggar("Jenis partner {$data['Jenis']} tidak ada di daftar yang diakui.");
        }

        $status = StatusPartner::tryFrom($data['Status']);

        if ($status === null) {
            throw new AturanBisnisDilanggar("Status partner {$data['Status']} tidak dikenal.");
        }

        $sandi = $data['KataSandi'] ?? null;

        if ($partner === null && ($sandi === null || $sandi === '')) {
            throw new AturanBisnisDilanggar('Partner baru wajib diberi kata sandi untuk masuk portalnya.');
        }

        return $this->transaksi->jalankan(function () use ($partner, $data, $jenis, $status, $sandi): Partner {
            $atribut = [
                'ProgramPartnerId' => $data['ProgramPartnerId'],
                'NamaPerusahaan' => $data['NamaPerusahaan'],
                'Jenis' => $jenis->value,
                'NamaPic' => $data['NamaPic'],
                'EmailPic' => mb_strtolower(trim($data['EmailPic'])),
                'TeleponPic' => $data['TeleponPic'],
                'Status' => $status->value,
                'ReferensiPerjanjian' => $data['ReferensiPerjanjian'],
                'ReferensiPayout' => $data['ReferensiPayout'],
            ];

            if (is_string($sandi) && $sandi !== '') {
                $atribut['KataSandi'] = $sandi;
            }

            $sebelum = $partner?->only(['NamaPerusahaan', 'Jenis', 'EmailPic', 'Status']);

            if ($partner === null) {
                $partner = $this->buatDenganKodeUnik($atribut);
            } else {
                $partner->fill($atribut);
                $partner->save();
            }

            $this->audit->catat(
                $sebelum === null ? 'Partner.Dibuat' : 'Partner.Diubah',
                'Partner',
                $partner->Id,
                dataSebelum: $sebelum,
                dataSesudah: $partner->only(['NamaPerusahaan', 'Jenis', 'EmailPic', 'Status']),
            );

            return $partner;
        });
    }

    /** @param array{ProgramPartnerId: string, PartnerId: string|null, Nama: string, Jenis: string, Nilai: float, MaksPembayaran: int|null, Aktif: bool, BerlakuDari: string|null, BerlakuSampai: string|null} $data */
    public function simpanAturan(?AturanKomisiPartner $aturan, array $data): AturanKomisiPartner
    {
        $jenis = JenisKomisiPartner::tryFrom($data['Jenis']);

        if ($jenis === null) {
            throw new AturanBisnisDilanggar("Jenis komisi {$data['Jenis']} tidak dikenal.");
        }

        if ($jenis === JenisKomisiPartner::Persentase && $data['Nilai'] > 100) {
            throw new AturanBisnisDilanggar('Komisi persentase tidak boleh melampaui seluruh pembayaran.');
        }

        if ($data['Nilai'] <= 0) {
            throw new AturanBisnisDilanggar('Nilai komisi harus lebih besar dari nol.');
        }

        $this->pastikanPartnerSeprogram($data['ProgramPartnerId'], $data['PartnerId']);

        return $this->transaksi->jalankan(function () use ($aturan, $data): AturanKomisiPartner {
            $sebelum = $aturan?->only(['Nama', 'Jenis', 'Nilai', 'Aktif']);

            if ($aturan === null) {
                $aturan = AturanKomisiPartner::create($data);
            } else {
                $aturan->fill($data);
                $aturan->save();
            }

            $this->audit->catat(
                $sebelum === null ? 'AturanKomisiPartner.Dibuat' : 'AturanKomisiPartner.Diubah',
                'AturanKomisiPartner',
                $aturan->Id,
                dataSebelum: $sebelum,
                dataSesudah: $aturan->only(['Nama', 'Jenis', 'Nilai', 'Aktif']),
            );

            return $aturan;
        });
    }

    /** Aturan khusus partner hanya masuk akal bila partnernya memang ada di program itu. */
    private function pastikanPartnerSeprogram(string $programId, ?string $partnerId): void
    {
        if ($partnerId === null) {
            return;
        }

        $cocok = Partner::query()
            ->where('Id', $partnerId)
            ->where('ProgramPartnerId', $programId)
            ->exists();

        if (! $cocok) {
            throw new AturanBisnisDilanggar('Partner yang dituju bukan anggota program aturan ini.');
        }
    }

    /** @param array<string, mixed> $atribut */
    private function buatDenganKodeUnik(array $atribut): Partner
    {
        for ($percobaan = 0; $percobaan < self::PERCOBAAN_MAKS; $percobaan++) {
            try {
                return Partner::create([...$atribut, 'Kode' => $this->kodeAcak()]);
            } catch (UniqueConstraintViolationException $bentrok) {
                // Hanya tabrakan kode acak yang layak diulang; email ganda adalah kesalahan pemakai.
                if (Partner::query()->where('EmailPic', $atribut['EmailPic'])->exists()) {
                    throw new AturanBisnisDilanggar('Alamat email PIC ini sudah dipakai partner lain.', previous: $bentrok);
                }
            }
        }

        throw new AturanBisnisDilanggar('Gagal menerbitkan kode partner yang unik.');
    }

    /** Huruf dan angka saja, tanpa yang mudah tertukar saat dibacakan lewat telepon. */
    private function kodeAcak(): string
    {
        $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $kode = '';

        for ($i = 0; $i < self::PANJANG_KODE; $i++) {
            $kode .= $abjad[random_int(0, mb_strlen($abjad) - 1)];
        }

        return $kode;
    }
}
