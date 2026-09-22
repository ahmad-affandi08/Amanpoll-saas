<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\MenuWhatsAppPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;

/** Menjawab pesan masuk dari menu yang tersimpan sebagai data, bukan ditulis di kode (MARKETING.md 16). */
final class PenjawabWhatsAppMasuk
{
    public function __construct(
        private readonly LayananKonsen $konsen,
        private readonly LayananKonfigurasiPemasaran $konfigurasi,
    ) {}

    /** @return array{Balasan: string, Berhenti: bool} */
    public function jawab(string $nomor, string $pesan): array
    {
        $isi = trim($pesan);

        if ($this->mintaBerhenti($isi)) {
            $this->konsen->cabut(
                $nomor,
                AlasanSupresi::Unsubscribe,
                $this->cariProspek($nomor),
                'Permintaan berhenti lewat WhatsApp.',
                KanalPesan::WhatsApp,
            );

            return ['Balasan' => $this->teks(KatalogKonfigurasiPemasaran::WHATSAPP_BALASAN_BERHENTI), 'Berhenti' => true];
        }

        $butir = MenuWhatsAppPemasaran::query()
            ->where('Aktif', true)
            ->where('Kunci', $isi)
            ->first();

        if ($butir !== null) {
            return ['Balasan' => $butir->Balasan, 'Berhenti' => false];
        }

        return ['Balasan' => $this->menu($isi === ''), 'Berhenti' => false];
    }

    /** Sapaan lalu daftar butir aktif; keduanya dapat diubah tanpa rilis. */
    public function menu(bool $sapaanSaja = true): string
    {
        $baris = MenuWhatsAppPemasaran::query()
            ->where('Aktif', true)
            ->orderBy('Urutan')
            ->get()
            ->map(fn (MenuWhatsAppPemasaran $satu): string => "{$satu->Kunci}. {$satu->Label}")
            ->all();

        $kepala = $sapaanSaja
            ? $this->teks(KatalogKonfigurasiPemasaran::WHATSAPP_SAPAAN_MENU)
            : $this->teks(KatalogKonfigurasiPemasaran::WHATSAPP_BALASAN_TIDAK_DIKENAL);

        return trim($kepala."\n\n".implode("\n", $baris));
    }

    /** @return list<string> */
    public function kataBerhenti(): array
    {
        $mentah = explode(',', $this->teks(KatalogKonfigurasiPemasaran::WHATSAPP_KATA_BERHENTI));

        $kata = [];

        foreach ($mentah as $satu) {
            $bersih = mb_strtoupper(trim($satu));

            if ($bersih !== '') {
                $kata[] = $bersih;
            }
        }

        return $kata;
    }

    private function mintaBerhenti(string $pesan): bool
    {
        return in_array(mb_strtoupper($pesan), $this->kataBerhenti(), true);
    }

    /** Nomor disimpan apa adanya di Prospek, jadi pencariannya mencoba beberapa bentuk tulis yang lazim. */
    private function cariProspek(string $nomor): ?Prospek
    {
        $baku = KanalPesan::WhatsApp->normalkan($nomor);

        if ($baku === '') {
            return null;
        }

        $bentuk = array_values(array_unique([
            $baku,
            '+'.$baku,
            str_starts_with($baku, '62') ? '0'.mb_substr($baku, 2) : $baku,
            str_starts_with($baku, '62') ? mb_substr($baku, 2) : $baku,
        ]));

        return Prospek::query()
            ->whereIn('WhatsApp', $bentuk)
            ->orWhereIn('Telepon', $bentuk)
            ->first();
    }

    private function teks(string $kunci): string
    {
        return (string) $this->konfigurasi->ambil($kunci);
    }
}
