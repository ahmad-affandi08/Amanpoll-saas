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
  { id: 'cara-kerja', judul: 'Cara kerja' },
  { id: 'menyusun', judul: 'Menyusun alur' },
  { id: 'menjalankan', judul: 'Saat berjalan' },
  { id: 'notifikasi', judul: 'Notifikasi' },
];

export function Persetujuan() {
  return (
    <>
      <Bagian id="cara-kerja" judul="Cara kerja">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Alur Persetujuan']} />
        </P>
        <P>
          Satu alur mengikat satu jenis dokumen — mutasi aset, penghapusan, pesanan pembelian — ke rangkaian
          tahap yang harus dilewati sebelum dokumen itu berlaku.
        </P>
        <P>
          Selama belum ada alur untuk sebuah jenis dokumen, dokumen jenis itu berjalan tanpa persetujuan.
          Menambahkan alur tidak menahan dokumen yang sudah terlanjur berjalan.
        </P>
      </Bagian>

      <Bagian id="menyusun" judul="Menyusun alur">
        <Daftar urut>
          <Butir>
            Buat alurnya, pilih <Ui>Jenis Entitas</Ui> yang hendak dikawal.
          </Butir>
          <Butir>
            Tambahkan tahap berurutan. Tiap tahap menetapkan penyetujunya: seorang <Ui>Pengguna</Ui> tertentu,
            pemegang sebuah <Ui>Peran</Ui>, atau atasan di sebuah <Ui>Unit</Ui>.
          </Butir>
          <Butir>
            Bila perlu, isi <Ui>Berlaku bila nilai minimal</Ui> pada tahap lanjutan. Dokumen bernilai di bawah
            ambang itu melewati tahap tersebut, misalnya direktur hanya memeriksa pembelian di atas Rp 25
            juta.
          </Butir>
          <Butir>Aktifkan alurnya. Alur yang nonaktif diabaikan seolah tidak ada.</Butir>
        </Daftar>
        <P>
          Ambang dibaca dari nilai dokumen: total estimasi permintaan pembelian, total pesanan pembelian,
          nilai anggaran, atau jumlah kali harga satuan usulan aset. Tahap pertama selalu berlaku untuk semua
          nilai, dan dokumen tanpa nilai (mutasi, penghapusan) melalui semua tahap.
        </P>
        <Catatan>
          Menunjuk <Tegas>peran</Tegas> lebih tahan banting daripada menunjuk orang. Kalau orangnya resign,
          alur yang menunjuk peran tetap jalan; alur yang menunjuk namanya akan macet.
        </Catatan>
      </Bagian>

      <Bagian id="menjalankan" judul="Saat berjalan">
        <P>
          Dokumen yang masuk alur berhenti di tahap pertama sampai disetujui, lalu bergerak ke tahap
          berikutnya. Penolakan di tahap mana pun menghentikan seluruh rangkaian.
        </P>
        <P>
          Penyetuju menemukan antreannya di <Jalur ruas={['Platform', 'Persetujuan Saya']} />.
        </P>
        <Awas>
          Alur dengan tahap yang penyetujunya tidak ada — peran yang tidak dipegang siapa pun, atau pengguna
          yang sudah dinonaktifkan — membuat dokumen tertahan tanpa ada yang bisa meneruskannya. Periksa ini
          tiap kali ada perubahan struktur organisasi.
        </Awas>
      </Bagian>

      <Bagian id="notifikasi" judul="Notifikasi">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Templat Notifikasi']} />
        </P>
        <P>
          Templat mengatur isi pesan yang dikirim sistem: permintaan persetujuan, penugasan perintah kerja,
          peringatan jatuh tempo. Tiap templat punya kode dan kanal — dalam aplikasi atau surel.
        </P>
        <P>
          Tiap pengguna mengatur sendiri apa yang ingin diterimanya lewat <Ui>Preferensi Notifikasi</Ui> di
          menu akunnya.
        </P>
      </Bagian>
    </>
  );
}
