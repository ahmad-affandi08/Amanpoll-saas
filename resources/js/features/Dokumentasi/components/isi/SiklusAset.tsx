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
import { Tangkapan } from '@/features/Dokumentasi/components/Tangkapan';

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
        <Tangkapan
          gambar="siklus-aset/mutasi"
          alt="Halaman Mutasi Aset dengan tombol Buat Permintaan Mutasi dan kolom Status"
          langkah={[
            {
              penanda: 'buat',
              isi: (
                <>
                  Klik <Ui>Buat Permintaan Mutasi</Ui>.
                </>
              ),
            },
            {
              penanda: 'status',
              isi: (
                <>
                  Pantau kolom <Ui>Status</Ui>: Draft, Menunggu persetujuan, Disetujui, lalu Selesai setelah
                  aset dipindahkan.
                </>
              ),
            },
          ]}
        />
        <Tangkapan
          gambar="siklus-aset/mutasi-formulir"
          alt="Formulir Buat Permintaan Mutasi"
          langkah={[
            {
              penanda: 'jenis',
              isi: (
                <>
                  Pilih <Ui>Jenis Mutasi</Ui>, mis. Antar Lokasi, Antar Unit, atau Peminjaman.
                </>
              ),
            },
            {
              penanda: 'lokasi',
              isi: (
                <>
                  Isi <Ui>Lokasi Tujuan</Ui>, <Ui>Unit Tujuan</Ui>, atau keduanya; minimal salah satu.
                </>
              ),
            },
            {
              penanda: 'unit',
              isi: (
                <>
                  Biarkan <Ui>Tidak diubah</Ui> untuk tujuan yang tetap sama.
                </>
              ),
            },
            {
              penanda: 'draft',
              isi: (
                <>
                  Klik <Ui>Buat Draft</Ui>, lalu lengkapi daftar asetnya di halaman draft sebelum diajukan.
                </>
              ),
            },
          ]}
        />
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
        <Tangkapan
          gambar="siklus-aset/penghapusan-formulir"
          alt="Formulir Ajukan Penghapusan Aset"
          langkah={[
            {
              isi: (
                <>
                  Klik <Ui>Ajukan Penghapusan</Ui> di halaman Penghapusan.
                </>
              ),
            },
            {
              penanda: 'alasan',
              isi: (
                <>
                  Tulis <Ui>Alasan</Ui> pelepasannya.
                </>
              ),
            },
            {
              penanda: 'metode',
              isi: (
                <>
                  Pilih <Ui>Metode Penghapusan</Ui>: Dijual, Dimusnahkan, Hibah, Hilang, atau Lainnya.
                </>
              ),
            },
            {
              penanda: 'draft',
              isi: (
                <>
                  Klik <Ui>Buat Draft</Ui>, lalu tambahkan aset yang dihapus di halaman draft.
                </>
              ),
            },
          ]}
        />
        <P>
          Aset yang dihapus <Tegas>tidak hilang dari sistem</Tegas>. Ia keluar dari daftar aktif tetapi
          seluruh riwayat pemeliharaan, kalibrasi, dan biayanya tetap terbaca di laporan.
        </P>
      </Bagian>

      <Bagian id="jejak" judul="Jejak riwayat">
        <P>Halaman detail aset saat ini menyimpan riwayat ini, masing-masing di tabnya sendiri:</P>
        <Daftar>
          <Butir>
            <Ui>Pemeliharaan</Ui> — keluhan, perintah kerja, inspeksi, dan waktu henti yang pernah menyentuh
            aset ini, dengan ringkasan jumlah perbaikan dan total waktu hentinya.
          </Butir>
          <Butir>
            <Ui>Kalibrasi</Ui> — rencana kalibrasi beserta seluruh pelaksanaannya, ditambah hasil terakhir dan
            jatuh tempo berikutnya.
          </Butir>
          <Butir>
            <Ui>Lokasi</Ui> dan <Ui>Penanggung Jawab</Ui> — perpindahan lokasi dan pergantian pemegang.
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
          Tiap tab memuat datanya sendiri saat dibuka, jadi membuka halaman aset tidak menarik seluruh
          riwayatnya sekaligus. Daftar yang panjang dipotong pada 50 terbaru, dan jumlah seluruhnya tetap
          disebutkan di kanan judul bagian.
        </Catatan>
        <P>
          Inilah alasan seluruh perubahan sebaiknya dilakukan lewat menu yang semestinya, bukan lewat
          penyuntingan langsung: yang tidak dicatat tidak akan pernah muncul di mana pun.
        </P>
      </Bagian>
    </>
  );
}
