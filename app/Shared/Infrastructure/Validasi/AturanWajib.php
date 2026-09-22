<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validasi;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionMethod;

/**
 * Membaca FormRequest lalu memberitahu frontend field mana yang wajib diisi.
 *
 * Sebelum ini tanda bintang merah ditulis tangan di tiap halaman, jadi ada
 * formulir yang menandai field opsional dan ada field wajib yang tidak
 * bertanda sama sekali. Sumbernya sekarang satu: aturan validasi yang memang
 * dipakai server saat menolak kiriman.
 */
final class AturanWajib
{
    /**
     * FormRequest sengaja dibuat dengan `new`, bukan lewat container.
     *
     * Container memasang callback afterResolving untuk ValidatesWhenResolved,
     * jadi me-resolve FormRequest di tengah render halaman GET akan langsung
     * menjalankan authorize() dan validasinya terhadap permintaan yang salah.
     *
     * @param  class-string<FormRequest>  $kelas
     * @return array<string, bool>
     */
    public static function untuk(string $kelas): array
    {
        $asal = request();

        $permintaan = new $kelas;
        $permintaan->setContainer(app());
        // Rute dan pengguna ikut dibawa karena banyak rules() memakainya, mis.
        // Rule::unique()->ignore($this->user('web')->Id) pada formulir profil.
        // Tanpa keduanya pembacaan aturan meledak, bukan sekadar meleset.
        $permintaan->setRouteResolver(fn () => $asal->route());
        $permintaan->setUserResolver(fn (?string $penjaga = null) => auth()->guard($penjaga)->user());

        // rules() tidak dideklarasikan di FormRequest, hanya dipanggil Laravel
        // bila ada, jadi ia dijangkau lewat refleksi. Tidak ada rules() di repo
        // ini yang meminta parameter, sehingga pemanggilan langsung sudah cukup.
        if (! method_exists($permintaan, 'rules')) {
            return [];
        }

        $aturan = (new ReflectionMethod($permintaan, 'rules'))->invoke($permintaan);

        return is_array($aturan) ? self::dariAturan($aturan) : [];
    }

    /**
     * @param  array<string, mixed>  $aturan
     * @return array<string, bool>
     */
    public static function dariAturan(array $aturan): array
    {
        $wajib = [];

        foreach ($aturan as $field => $daftar) {
            // Field bersarang seperti 'Detail.*.Jumlah' tidak punya satu label
            // di layar, jadi tidak ada yang bisa ditandai.
            if (str_contains((string) $field, '.')) {
                continue;
            }

            $wajib[(string) $field] = self::wajibDiisi($daftar);
        }

        return $wajib;
    }

    /**
     * Hanya 'required' polos yang dihitung wajib.
     *
     * 'required_if' dan saudaranya bergantung pada isi field lain, sehingga
     * bintangnya akan salah separuh waktu. Menandainya wajib padahal belum
     * tentu lebih menyesatkan daripada tidak menandainya sama sekali, karena
     * pengguna akan mengisi sesuatu hanya untuk memuaskan tanda itu.
     */
    private static function wajibDiisi(mixed $daftar): bool
    {
        foreach (self::keDaftarString($daftar) as $satu) {
            if ($satu === 'required') {
                return true;
            }
        }

        return false;
    }

    /**
     * Aturan boleh berupa string berpisah pipa, array campuran, atau objek Rule.
     * Hanya token stringnya yang menarik; objek Rule tidak pernah menyatakan wajib.
     *
     * @return list<string>
     */
    private static function keDaftarString(mixed $daftar): array
    {
        if (is_string($daftar)) {
            return explode('|', $daftar);
        }

        if (! is_array($daftar)) {
            return [];
        }

        $token = [];

        foreach ($daftar as $satu) {
            if (is_string($satu)) {
                $token[] = $satu;
            }
        }

        return $token;
    }
}
