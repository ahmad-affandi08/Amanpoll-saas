<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Application\Services\PemeriksaCaptcha;
use App\Domain\Pemasaran\Application\Services\PemvalidasiFormulirPemasaran;
use App\Domain\Pemasaran\Application\Services\PenempelTagProspek;
use App\Domain\Pemasaran\Application\Services\PerangkapSpam;
use App\Domain\Pemasaran\Domain\Enums\JenisFieldFormulir;
use App\Domain\Pemasaran\Domain\Enums\SumberKonsen;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\ValueObjects\HasilPengirimanFormulir;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FieldFormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanFormulir;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Menerima satu pengiriman formulir publik (MARKETING.md 10). */
final class KirimFormulirPemasaran
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PemvalidasiFormulirPemasaran $pemvalidasi,
        private readonly CatatProspek $catatProspek,
        private readonly PenempelTagProspek $tag,
        private readonly PemeriksaCaptcha $captcha,
        private readonly LayananKonsen $konsen,
        private readonly PerangkapSpam $perangkap,
    ) {}

    /**
     * @param  array<string, mixed>  $masukan  jawaban mentah dari pengunjung
     */
    public function jalankan(
        FormulirPemasaran $formulir,
        array $masukan,
        ?string $pengenalPengunjung = null,
        ?string $alamatIp = null,
        ?string $agenPengguna = null,
    ): HasilPengirimanFormulir {
        if (! $formulir->Aktif) {
            throw new AturanBisnisDilanggar('Formulir ini sedang tidak menerima pengiriman.');
        }

        if ($this->perangkap->terperangkap($masukan)) {
            return HasilPengirimanFormulir::spam();
        }

        // Diperiksa sebelum validasi field.
        if ($formulir->CaptchaAktif) {
            $token = $masukan[$this->captcha->namaField()] ?? null;
            $this->captcha->pastikanSah(is_string($token) ? $token : null, $alamatIp);
        }

        $jawaban = $this->pemvalidasi->jalankan($formulir, $masukan);
        $persetujuan = $this->persetujuan($formulir, $jawaban);

        if ($formulir->WajibPersetujuan && ! $persetujuan) {
            throw new AturanBisnisDilanggar('Formulir ini memerlukan persetujuan sebelum dikirim.');
        }

        return $this->transaksi->jalankan(
            function () use (
                $formulir,
                $jawaban,
                $persetujuan,
                $pengenalPengunjung,
                $alamatIp,
                $agenPengguna
            ): HasilPengirimanFormulir {
                $utm = $this->utmDariKunjungan($pengenalPengunjung);

                $prospek = $this->catatProspek->jalankan(
                    $this->dataProspek($formulir, $jawaban),
                    $this->sumber($formulir),
                    $pengenalPengunjung,
                );

                $this->tag->tempel($prospek, $this->tagFormulir($formulir));
                $this->catatKonsen($prospek, $persetujuan, $alamatIp, $agenPengguna);

                $pengiriman = PengirimanFormulir::create([
                    'FormulirPemasaranId' => $formulir->Id,
                    'ProspekId' => $prospek->Id,
                    'PengenalPengunjung' => $pengenalPengunjung,
                    'Data' => [...$jawaban, ...$this->fieldUtm($formulir, $utm)],
                    'Persetujuan' => $persetujuan,
                    'AlamatIp' => $alamatIp,
                    'AgenPengguna' => $agenPengguna,
                    'DikirimPada' => CarbonImmutable::now(),
                ]);

                return HasilPengirimanFormulir::tersimpan($prospek, $pengiriman);
            },
        );
    }

    /** Persetujuan pada formulir adalah consent pemasaran, dan dicatat sebagai bukti. */
    private function catatKonsen(
        Prospek $prospek,
        bool $persetujuan,
        ?string $alamatIp,
        ?string $agenPengguna,
    ): void {
        $email = (string) $prospek->Email;

        if ($email === '') {
            return;
        }

        $this->konsen->catat(
            $email,
            $persetujuan,
            SumberKonsen::Formulir,
            $prospek,
            $alamatIp,
            $agenPengguna,
        );
    }

    /** @param array<string, mixed> $jawaban */
    private function persetujuan(FormulirPemasaran $formulir, array $jawaban): bool
    {
        foreach ($formulir->field as $field) {
            if ($field->Jenis === JenisFieldFormulir::Persetujuan) {
                return (bool) ($jawaban[$field->Kode] ?? false);
            }
        }

        return false;
    }

    /**
     * Memetakan jawaban ke bentuk yang dikenal CatatProspek. Field dengan kode
     * baku dipakai apa adanya; sisanya menjadi catatan, sehingga jawaban khusus
     * satu formulir tidak hilang begitu saja.
     *
     * @param  array<string, mixed>  $jawaban
     * @return array<string, mixed>
     */
    private function dataProspek(FormulirPemasaran $formulir, array $jawaban): array
    {
        $baku = ['Nama', 'Email', 'Telepon', 'WhatsApp', 'Jabatan', 'Perusahaan', 'Industri'];

        $data = [];
        foreach ($baku as $kunci) {
            if (isset($jawaban[$kunci]) && $jawaban[$kunci] !== '') {
                $data[$kunci] = $jawaban[$kunci];
            }
        }

        $data['Nama'] ??= $this->namaCadangan($jawaban);

        $lain = array_diff_key($jawaban, array_flip($baku));

        if ($lain !== []) {
            $data['Catatan'] = $formulir->Nama.': '.json_encode($lain, JSON_UNESCAPED_UNICODE);
        }

        return $data;
    }

    /**
     * Prospek wajib bernama. Formulir yang tidak menanyakan nama tetap boleh
     * ada — unduhan lead magnet sering hanya meminta email — jadi alamat
     * emailnya dipakai sebagai nama sementara alih-alih menolak pengirimannya.
     *
     * @param  array<string, mixed>  $jawaban
     */
    private function namaCadangan(array $jawaban): string
    {
        $email = $jawaban['Email'] ?? null;

        return is_string($email) && $email !== '' ? $email : 'Tanpa Nama';
    }

    private function sumber(FormulirPemasaran $formulir): SumberProspek
    {
        return SumberProspek::tryFrom($formulir->Sumber) ?? SumberProspek::Website;
    }

    /** @return list<string> */
    private function tagFormulir(FormulirPemasaran $formulir): array
    {
        $tag = $formulir->Tag ?? [];

        return array_values(array_filter($tag, is_string(...)));
    }

    /**
     * Nilai UTM diambil dari kunjungan terakhir pengunjung yang bersangkutan.
     *
     * @return array<string, string|null>
     */
    private function utmDariKunjungan(?string $pengenalPengunjung): array
    {
        $kosong = [
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_term' => null,
            'utm_content' => null,
        ];

        if ($pengenalPengunjung === null) {
            return $kosong;
        }

        $sesi = SesiPengunjung::query()
            ->with('utm')
            ->where('PengenalPengunjung', $pengenalPengunjung)
            ->orderByDesc('TerakhirAktifPada')
            ->first();

        $utm = $sesi?->utm;

        if ($utm === null) {
            return $kosong;
        }

        return [
            'utm_source' => $utm->Source,
            'utm_medium' => $utm->Medium,
            'utm_campaign' => $utm->Campaign,
            'utm_term' => $utm->Term,
            'utm_content' => $utm->Content,
        ];
    }

    /**
     * Nilai untuk field `UtmTersembunyi` pada formulir ini. Kode fieldnya yang
     * menentukan parameter mana yang diambil, sehingga satu formulir dapat
     * menyimpan sebagian saja.
     *
     * @param  array<string, string|null>  $utm
     * @return array<string, string|null>
     */
    private function fieldUtm(FormulirPemasaran $formulir, array $utm): array
    {
        $hasil = [];

        foreach ($formulir->field as $field) {
            if ($field->Jenis->terisiOtomatis()) {
                $hasil[$field->Kode] = $this->nilaiUtm($field, $utm);
            }
        }

        return $hasil;
    }

    /** @param array<string, string|null> $utm */
    private function nilaiUtm(FieldFormulirPemasaran $field, array $utm): ?string
    {
        $kode = strtolower($field->Kode);

        return $utm[$kode] ?? $utm['utm_'.$kode] ?? null;
    }
}
