<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\Infrastructure\Persistence\PenghapusBertahap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Retensi CatatanAkses (FASE 45).
 *
 * Tabel ini bertambah tiap login, jadi setelah berbulan-bulan satu `DELETE`
 * tunggal menyapu jutaan baris dalam satu transaksi -- persis yang dibunuh
 * shared hosting di tengah jalan. Penghapusannya kini per potongan
 * (`amanpoll.retensi.potongan`) di atas indeks `IdxCatatanAksesDibuatPada`,
 * dengan jeda kecil dan batas waktu total; sisa yang belum terhapus diambil
 * jalan terjadwal berikutnya.
 */
final class BersihkanCatatanAkses extends Command
{
    protected $signature = 'catatan-akses:bersihkan';

    protected $description = 'Hapus CatatanAkses yang lebih tua dari retention policy (config amanpoll.retensi_catatan_akses_hari), per potongan';

    public function handle(): int
    {
        $hariRetensi = (int) config('amanpoll.retensi_catatan_akses_hari', 90);
        $batasWaktu = now()->subDays($hariRetensi);

        // Retensi berlaku lintas seluruh organisasi (operasi sistem terjadwal, bukan permintaan tenant).
        $hasil = PenghapusBertahap::dariKonfigurasi()->hapus(
            DB::table('CatatanAkses')->where('DibuatPada', '<', $batasWaktu),
        );

        $this->info("Menghapus {$hasil['Dihapus']} CatatanAkses lebih tua dari {$hariRetensi} hari.");

        if (! $hasil['Tuntas']) {
            $this->warn('Batas waktu tercapai; sisanya dihapus pada jalan berikutnya.');
        }

        return self::SUCCESS;
    }
}
