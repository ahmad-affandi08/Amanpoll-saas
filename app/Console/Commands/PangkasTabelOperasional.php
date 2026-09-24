<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\IntegrasiAudit\Domain\Enums\StatusKotakKeluarPeristiwa;
use App\Domain\IntegrasiAudit\Domain\Enums\StatusPengirimanPanggilanBalikWeb;
use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
use App\Domain\Notifikasi\Domain\Enums\StatusNotifikasi;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Sinkronisasi\Domain\Enums\StatusAntrianSinkronisasi;
use App\Shared\Infrastructure\Persistence\PenghapusBertahap;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use stdClass;

/**
 * Pemangkasan terjadwal tabel operasional yang tumbuh tanpa batas (FASE 45).
 *
 * Batas basis data hosting 3 GB. Yang dihapus hanya baris berstatus AKHIR yang
 * sudah lewat masa simpannya (`amanpoll.retensi.*`, null mematikan aturan):
 * antrean yang masih menunggu, pengiriman yang masih akan dicoba ulang, dan
 * mutasi offline yang menunggu keputusan tidak pernah disentuh. Data bisnis
 * (aset, tiket, transaksi) tidak ada di daftar ini sama sekali.
 *
 * Yang sengaja TIDAK dipangkas di sini:
 * - CatatanAkses dan KunciIdempotensi: sudah punya perintahnya sendiri
 *   (`catatan-akses:bersihkan`, `idempotensi:bersihkan`).
 * - CatatanAudit: append-only. Hanya bila `catatan_audit.arsip_setelah_hari`
 *   diisi, baris yang lebih tua diarsipkan ke berkas gzip lalu dihapus;
 *   bawaannya tidak ada yang dihapus.
 * - Sesi dan cache: SESSION_DRIVER/CACHE_STORE `file`, tidak di basis data.
 */
final class PangkasTabelOperasional extends Command
{
    protected $signature = 'retensi:pangkas';

    protected $description = 'Hapus baris operasional berstatus akhir yang lewat masa retensi (notifikasi, outbox, webhook, sinkronisasi, kunjungan anonim), per potongan';

    public function handle(): int
    {
        $penghapus = PenghapusBertahap::dariKonfigurasi();
        $ringkasan = [];

        foreach ($this->aturan() as $nama => [$hari, $kueri]) {
            if ($hari === null) {
                continue;
            }

            if ($penghapus->waktuHabis()) {
                $this->warn('Batas waktu tercapai; aturan berikutnya dijalankan pada jalan berikutnya.');
                break;
            }

            $hasil = $penghapus->hapus($kueri(CarbonImmutable::now()->subDays($hari)));
            $ringkasan[$nama] = $hasil['Dihapus'];
            $this->line("{$nama}: {$hasil['Dihapus']} baris dihapus (retensi {$hari} hari)".($hasil['Tuntas'] ? '.' : ', belum tuntas.'));
        }

        $hariArsipAudit = $this->hari('amanpoll.retensi.catatan_audit.arsip_setelah_hari');
        if ($hariArsipAudit !== null && ! $penghapus->waktuHabis()) {
            $hasil = $this->arsipkanCatatanAudit($penghapus, CarbonImmutable::now()->subDays($hariArsipAudit));
            $ringkasan['CatatanAudit (diarsipkan)'] = $hasil['Dihapus'];
            $this->line("CatatanAudit: {$hasil['Dihapus']} baris diarsipkan lalu dihapus (lebih tua dari {$hariArsipAudit} hari).");
        }

        Log::info('Retensi tabel operasional selesai.', ['Dihapus' => $ringkasan]);

        return self::SUCCESS;
    }

    /**
     * Nama aturan => [hari retensi atau null, pembuat kueri dari batas waktu].
     *
     * @return array<string, array{0: int|null, 1: Closure(CarbonImmutable): Builder}>
     */
    private function aturan(): array
    {
        $statusAkhirNotifikasi = [StatusNotifikasi::Terkirim->value, StatusNotifikasi::Gagal->value];

        return [
            'Notifikasi (dibaca / bukan in-app)' => [
                $this->hari('amanpoll.retensi.notifikasi_hari'),
                fn (CarbonImmutable $batas): Builder => DB::table('Notifikasi')
                    ->whereIn('Status', $statusAkhirNotifikasi)
                    ->where('DibuatPada', '<', $batas)
                    ->where(fn (Builder $syarat) => $syarat
                        ->where('Kanal', '!=', KanalNotifikasi::InApp->value)
                        ->orWhereNotNull('DibacaPada')),
            ],
            'Notifikasi (in-app tidak pernah dibaca)' => [
                $this->hari('amanpoll.retensi.notifikasi_belum_dibaca_hari'),
                fn (CarbonImmutable $batas): Builder => DB::table('Notifikasi')
                    ->whereIn('Status', $statusAkhirNotifikasi)
                    ->where('Kanal', KanalNotifikasi::InApp->value)
                    ->whereNull('DibacaPada')
                    ->where('DibuatPada', '<', $batas),
            ],
            'KotakKeluarPeristiwa (selesai)' => [
                $this->hari('amanpoll.retensi.kotak_keluar_selesai_hari'),
                fn (CarbonImmutable $batas): Builder => $this->kotakKeluar(StatusKotakKeluarPeristiwa::Selesai, $batas),
            ],
            'KotakKeluarPeristiwa (gagal permanen)' => [
                $this->hari('amanpoll.retensi.kotak_keluar_gagal_hari'),
                fn (CarbonImmutable $batas): Builder => $this->kotakKeluar(StatusKotakKeluarPeristiwa::Gagal, $batas),
            ],
            'PengirimanPanggilanBalikWeb (berhasil)' => [
                $this->hari('amanpoll.retensi.panggilan_balik_berhasil_hari'),
                fn (CarbonImmutable $batas): Builder => DB::table('PengirimanPanggilanBalikWeb')
                    ->where('Status', StatusPengirimanPanggilanBalikWeb::Berhasil->value)
                    ->where('DibuatPada', '<', $batas),
            ],
            'PengirimanPanggilanBalikWeb (gagal permanen)' => [
                $this->hari('amanpoll.retensi.panggilan_balik_gagal_hari'),
                fn (CarbonImmutable $batas): Builder => DB::table('PengirimanPanggilanBalikWeb')
                    ->where('Status', StatusPengirimanPanggilanBalikWeb::GagalPermanen->value)
                    ->where('DibuatPada', '<', $batas),
            ],
            'AntrianSinkronisasi (selesai / dibatalkan)' => [
                $this->hari('amanpoll.retensi.sinkronisasi_selesai_hari'),
                fn (CarbonImmutable $batas): Builder => DB::table('AntrianSinkronisasi')
                    ->whereIn('Status', array_values(array_map(
                        fn (StatusAntrianSinkronisasi $status): string => $status->value,
                        array_filter(StatusAntrianSinkronisasi::cases(), fn (StatusAntrianSinkronisasi $status): bool => $status->final()),
                    )))
                    ->where('DiterimaPada', '<', $batas)
                    ->where(fn (Builder $syarat) => $syarat->whereNull('DiprosesPada')->orWhere('DiprosesPada', '<', $batas)),
            ],
            'EventPemasaran (tayangan pengunjung anonim)' => [
                $this->hari('amanpoll.retensi.event_pemasaran_anonim_hari'),
                // Hanya tayangan halaman tanpa organisasi dari pengunjung yang tidak pernah menjadi prospek:
                // linimasa dan skor prospek membaca peristiwa menurut PengenalPengunjung prospeknya.
                fn (CarbonImmutable $batas): Builder => DB::table('EventPemasaran')
                    ->whereNull('OrganisasiId')
                    ->where('Jenis', KatalogPeristiwaPemasaran::HALAMAN_DILIHAT)
                    ->where('TerjadiPada', '<', $batas)
                    ->whereNotExists(fn (Builder $prospek) => $prospek
                        ->selectRaw('1')
                        ->from('Prospek')
                        ->whereColumn('Prospek.PengenalPengunjung', 'EventPemasaran.PengenalPengunjung')),
            ],
        ];
    }

    /** Peristiwa outbox berstatus akhir yang diproses sebelum batas. */
    private function kotakKeluar(StatusKotakKeluarPeristiwa $status, CarbonImmutable $batas): Builder
    {
        return DB::table('KotakKeluarPeristiwa')
            ->where('Status', $status->value)
            ->where('DibuatPada', '<', $batas)
            ->where(fn (Builder $syarat) => $syarat->whereNull('DiprosesPada')->orWhere('DiprosesPada', '<', $batas));
    }

    /**
     * Mengarsipkan CatatanAudit yang lebih tua dari batas ke berkas NDJSON gzip,
     * satu berkas per potongan, dan menghapus potongan itu hanya setelah
     * berkasnya terbukti tertulis.
     *
     * @return array{Dihapus: int, Tuntas: bool}
     */
    private function arsipkanCatatanAudit(PenghapusBertahap $penghapus, CarbonImmutable $batas): array
    {
        $disk = Storage::disk((string) config('amanpoll.retensi.catatan_audit.disk', 'local'));
        $folder = trim((string) config('amanpoll.retensi.catatan_audit.folder', 'arsip/catatan-audit'), '/');
        $urutan = 0;

        return $penghapus->arsipkanLaluHapus(
            DB::table('CatatanAudit')->where('DibuatPada', '<', $batas),
            function (Collection $baris) use ($disk, $folder, &$urutan): void {
                $urutan++;
                // Stempel waktu UTC di nama berkas hanya untuk keunikan dan urutan, bukan tanggal kalender.
                $jalur = sprintf('%s/catatan-audit-%s-%04d.ndjson.gz', $folder, CarbonImmutable::now()->utc()->getTimestamp(), $urutan);
                $isi = $baris
                    ->map(fn (stdClass $satu): string => (string) json_encode($satu, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    ->implode("\n")."\n";
                $terkompres = gzencode($isi, 9);

                if ($terkompres === false || ! $disk->put($jalur, $terkompres) || $disk->size($jalur) !== strlen($terkompres)) {
                    throw new RuntimeException("Arsip CatatanAudit {$jalur} gagal ditulis; potongan ini tidak dihapus.");
                }
            },
        );
    }

    /** Hari retensi dari config; null, kosong, atau kurang dari satu mematikan aturan. */
    private function hari(string $kunci): ?int
    {
        $nilai = config($kunci);

        if ($nilai === null || $nilai === '' || ! is_numeric($nilai) || (int) $nilai < 1) {
            return null;
        }

        return (int) $nilai;
    }
}
