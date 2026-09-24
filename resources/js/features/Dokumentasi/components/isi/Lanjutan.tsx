import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Awas,
  Bagian,
  Butir,
  Catatan,
  Daftar,
  Jalur,
  P,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'laporan', judul: 'Laporan dan dasbor' },
  { id: 'kepatuhan', judul: 'Kepatuhan' },
  { id: 'integrasi', judul: 'Integrasi dan kunci API' },
  { id: 'offline', judul: 'Mode Lapangan' },
  { id: 'audit', judul: 'Log audit' },
];

export function Lanjutan() {
  return (
    <>
      <Bagian id="laporan" judul="Laporan dan dasbor">
        <P>
          <Jalur ruas={['Platform', 'Laporan & Dasbor']} />
        </P>
        <Daftar>
          <Butir>
            <Ui>Laporan Tersimpan</Ui> menyimpan susunan penyaring yang sering dipakai, sehingga tidak perlu
            disusun ulang tiap bulan.
          </Butir>
          <Butir>
            <Ui>Dasbor Kustom</Ui> menyusun kartu-kartu angka menjadi satu layar pantau.
          </Butir>
        </Daftar>
        <P>Keduanya bagian dari paket langganan tertentu; menunya tidak muncul bila paketnya tidak memuat.</P>
      </Bagian>

      <Bagian id="kepatuhan" judul="Kepatuhan">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Kepatuhan']} />
        </P>
        <P>
          Mencatat standar yang harus dipenuhi aset beserta persyaratannya, dan sertifikasi yang
          membuktikannya. Sistem memperingatkan menjelang sertifikat kedaluwarsa.
        </P>
      </Bagian>

      <Bagian id="integrasi" judul="Integrasi dan kunci API">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Kunci API']} />
        </P>
        <Awas>
          Token kunci API hanya ditampilkan <Tegas>satu kali</Tegas>, tepat setelah dibuat. Sistem menyimpan
          sidik jarinya, bukan tokennya, jadi token yang hilang tidak dapat dilihat lagi — hanya dapat dicabut
          lalu dibuat baru.
        </Awas>
        <Daftar>
          <Butir>
            Batasi <Ui>Cakupan</Ui> seperlunya; kunci untuk membaca tidak perlu izin menulis.
          </Butir>
          <Butir>
            <Ui>Alamat IP Diizinkan</Ui> mempersempit dari mana kunci boleh dipakai.
          </Butir>
          <Butir>Cabut kunci yang sudah tidak dipakai; kunci menganggur adalah pintu yang tak dijaga.</Butir>
        </Daftar>
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Integrasi']} /> mengatur webhook ke
          sistem luar. Pengiriman yang gagal dicoba ulang sendiri secara berkala.
        </P>
      </Bagian>

      <Bagian id="offline" judul="Mode Lapangan">
        <P>
          Tampilan aplikasi HP untuk teknisi dan pelapor. Pengguna yang seluruh perannya bertanda{' '}
          <Ui>Tampilan Lapangan</Ui> langsung dibawa ke sini setelah masuk dan tidak melihat dasbor ini.
          Tandanya diatur per peran di{' '}
          <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Peran & Izin']} />: <Ui>Teknisi</Ui> untuk
          yang mengerjakan tiket kerja, <Ui>Pelapor</Ui> untuk staf lokasi yang melaporkan kerusakan.
        </P>
        <P>
          Pengguna yang juga memegang peran lain tetap masuk dasbor dan dapat beralih lewat menu akun,{' '}
          <Ui>Buka Mode Lapangan</Ui>. Pilihannya diingat di perangkat itu.
        </P>
        <P>
          Mode Lapangan tetap berjalan di area tanpa sinyal — ruang mesin, basement, lapangan. Pekerjaan yang
          dicatat disimpan di perangkat dan dikirim saat jaringan kembali ada.
        </P>
        <Catatan>
          Pengiriman ulang aman diulang: satu catatan yang terkirim dua kali tidak menjadi dua perintah kerja.
          Karena itu, bila ragu apakah data sudah terkirim, mengirim ulang lebih baik daripada mengetik ulang.
        </Catatan>
      </Bagian>

      <Bagian id="audit" judul="Log audit">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Log Audit']} />
        </P>
        <P>
          Mencatat siapa mengubah apa dan kapan, lengkap dengan nilai sebelum dan sesudah. Isinya tidak dapat
          disunting maupun dihapus oleh siapa pun, termasuk administrator — itulah yang membuatnya berguna
          saat ada yang perlu ditelusuri.
        </P>
      </Bagian>
    </>
  );
}
