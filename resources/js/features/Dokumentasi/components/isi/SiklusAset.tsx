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
  { id: 'mutasi', judul: 'Mutasi aset' },
  { id: 'serah-terima', judul: 'Serah terima' },
  { id: 'penghapusan', judul: 'Penghapusan' },
  { id: 'jejak', judul: 'Jejak riwayat' },
];

export function SiklusAset() {
  return (
    <>
      <Bagian id="mutasi" judul="Mutasi aset">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Manajemen Aset', 'Mutasi Aset']} />
        </P>
        <P>
          Dipakai saat aset berpindah lokasi atau berpindah unit organisasi. Mutasi dicatat sebagai
          permintaan, bukan perubahan langsung, supaya perpindahannya dapat ditelusuri dan — bila alur
          persetujuan dipasang — disetujui lebih dulu.
        </P>
        <Awas>
          Jangan memindahkan aset dengan menyunting kolom <Ui>Lokasi</Ui> di halaman aset. Cara itu mengubah
          lokasinya tanpa meninggalkan alasan dan tanggal perpindahan.
        </Awas>
      </Bagian>

      <Bagian id="serah-terima" judul="Serah terima">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Manajemen Aset', 'Serah Terima']} />
        </P>
        <P>
          Dipakai saat penguasaan aset berpindah orang: dari gudang ke pengguna, dari pengguna lama ke
          pengguna baru, atau kembali ke gudang. Berita acaranya tersimpan lengkap dengan siapa menyerahkan,
          siapa menerima, dan kondisi aset saat itu.
        </P>
      </Bagian>

      <Bagian id="penghapusan" judul="Penghapusan">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Manajemen Aset', 'Penghapusan']} />
        </P>
        <P>
          Untuk aset yang dilepas: dijual, dimusnahkan, hilang, atau habis umur manfaatnya. Pengajuan
          penghapusan memuat alasan dan nilai sisa, dan biasanya melewati persetujuan.
        </P>
        <P>
          Aset yang dihapus <Tegas>tidak hilang dari sistem</Tegas>. Ia keluar dari daftar aktif tetapi
          seluruh riwayat pemeliharaan, kalibrasi, dan biayanya tetap terbaca di laporan.
        </P>
      </Bagian>

      <Bagian id="jejak" judul="Jejak riwayat">
        <P>Halaman detail aset saat ini menyimpan riwayat ini, masing-masing di tabnya sendiri:</P>
        <Daftar>
          <Butir>
            <Ui>Lokasi</Ui> — seluruh perpindahan lokasi beserta tanggalnya.
          </Butir>
          <Butir>
            <Ui>Penanggung Jawab</Ui> — pergantian pemegang aset.
          </Butir>
          <Butir>
            <Ui>Nilai</Ui>, <Ui>Garansi</Ui>, <Ui>Meter</Ui> — riwayat penilaian, masa garansi, dan pembacaan
            meter.
          </Butir>
          <Butir>
            <Ui>Kolaborasi</Ui> — lampiran berkas dan komentar.
          </Butir>
        </Daftar>
        <Catatan>
          Riwayat pemeliharaan dan kalibrasi <Tegas>belum</Tegas> ditampilkan di halaman aset. Datanya
          tercatat lengkap, tetapi untuk saat ini dibaca dari menu modulnya: keluhan dan perintah kerja di{' '}
          <Jalur ruas={['Operasional & Aset', 'Pemeliharaan']} />, dan pelaksanaan kalibrasi di{' '}
          <Jalur ruas={['Operasional & Aset', 'Kalibrasi']} />, disaring per aset.
        </Catatan>
        <P>
          Inilah alasan seluruh perubahan sebaiknya dilakukan lewat menu yang semestinya, bukan lewat
          penyuntingan langsung: yang tidak dicatat tidak akan pernah muncul di mana pun.
        </P>
      </Bagian>
    </>
  );
}
