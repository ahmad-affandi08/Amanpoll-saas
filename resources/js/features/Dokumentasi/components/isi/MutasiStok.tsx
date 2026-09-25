import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Awas,
  Bagian,
  Butir,
  Catatan,
  Daftar,
  Jalur,
  Langkah,
  P,
  SubJudul,
  Tabel,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';
import { Tangkapan } from '@/features/Dokumentasi/components/Tangkapan';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'aturan', judul: 'Aturan dasar' },
  { id: 'jenis', judul: 'Jenis mutasi' },
  { id: 'saldo-awal', judul: 'Memasukkan saldo awal' },
  { id: 'reservasi', judul: 'Reservasi' },
];

export function MutasiStok() {
  return (
    <>
      <Bagian id="aturan" judul="Aturan dasar">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Persediaan', 'Mutasi Stok']} />
        </P>
        <Awas>
          Mutasi stok adalah <Tegas>satu-satunya</Tegas> cara saldo berubah. Tidak ada kolom saldo yang dapat
          diketik langsung, dan itu disengaja: setiap perubahan selalu punya tanggal, alasan, dan penanggung
          jawab.
        </Awas>
        <P>
          Konsekuensinya, selisih antara catatan dan kenyataan tidak diperbaiki dengan menimpa angka,
          melainkan dengan mencatat mutasi penyesuaian beserta alasannya.
        </P>
      </Bagian>

      <Bagian id="jenis" judul="Jenis mutasi">
        <Tabel
          kepala={['Jenis', 'Dipakai saat']}
          baris={[
            ['Penerimaan', 'Barang masuk: pembelian, hibah, atau saldo awal.'],
            ['Pengeluaran', 'Barang keluar untuk dipakai pekerjaan.'],
            ['Transfer', 'Pindah antar gudang atau antar rak; saldo total tidak berubah.'],
            ['Adjustment', 'Penyesuaian setelah stok opname. Wajib beralasan.'],
            ['Return', 'Barang kembali ke gudang karena tidak jadi dipakai atau salah ambil.'],
          ]}
        />
        <Catatan>
          Pengeluaran untuk perintah kerja tidak dicatat di sini. Cukup catat suku cadang terpakai di perintah
          kerjanya; mutasinya dibuat sistem.
        </Catatan>
      </Bagian>

      <Bagian id="saldo-awal" judul="Memasukkan saldo awal">
        <P>Saat pertama kali memakai Amanpoll, stok yang sudah ada dimasukkan seperti ini:</P>
        <Langkah
          daftar={[
            {
              judul: 'Atur pola nomor bila perlu',
              isi: (
                <P>
                  Mutasi langsung bernomor dengan pola bawaan, mis. MS/2026/0001. Ubah dulu di halaman
                  Penomoran bila organisasi punya format sendiri.
                </P>
              ),
            },
            {
              judul: 'Hitung fisik dulu, baru catat',
              isi: (
                <P>
                  Masukkan angka hasil hitungan sebenarnya, bukan angka dari sistem lama yang belum
                  dicocokkan. Selisih yang terbawa masuk akan terus menghantui sampai stok opname berikutnya.
                </P>
              ),
            },
            {
              judul: 'Buat satu mutasi Penerimaan per gudang',
              isi: (
                <P>
                  Satu dokumen boleh memuat banyak baris suku cadang, jadi tidak perlu satu dokumen per
                  barang. Beri catatan &ldquo;Saldo awal&rdquo; supaya mudah ditemukan kelak.
                </P>
              ),
            },
            {
              judul: 'Posting dokumennya',
              isi: (
                <P>Saldo baru berubah setelah diposting. Mutasi yang masih draf belum memengaruhi apa pun.</P>
              ),
            },
          ]}
        />
        <SubJudul>Membuat mutasi di layar</SubJudul>
        <Tangkapan
          gambar="mutasi-stok/formulir"
          alt="Formulir Buat Mutasi Stok"
          langkah={[
            {
              isi: (
                <>
                  Buka menu <Ui>Persediaan</Ui> › <Ui>Mutasi Stok</Ui>, lalu klik <Ui>Buat Mutasi Stok</Ui>.
                </>
              ),
            },
            {
              penanda: 'jenis',
              isi: (
                <>
                  Pilih <Ui>Jenis</Ui>; untuk saldo awal pilih Penerimaan.
                </>
              ),
            },
            {
              penanda: 'gudang',
              isi: (
                <>
                  Pilih <Ui>Gudang Tujuan</Ui>.
                </>
              ),
            },
            {
              penanda: 'draft',
              isi: (
                <>
                  Klik <Ui>Buat Draft</Ui>. Dokumennya terbuka dan siap diisi barisnya.
                </>
              ),
            },
          ]}
        />
        <Tangkapan
          gambar="mutasi-stok/detail"
          alt="Halaman detail mutasi stok dengan tombol Tambah Baris dan Posting"
          langkah={[
            {
              penanda: 'baris',
              isi: (
                <>
                  Klik <Ui>Tambah Baris</Ui> untuk setiap suku cadang beserta jumlahnya.
                </>
              ),
            },
            {
              penanda: 'posting',
              isi: (
                <>
                  Setelah semua baris benar, klik <Ui>Posting</Ui>. Saldo berubah saat itu juga.
                </>
              ),
            },
          ]}
        />
      </Bagian>

      <Bagian id="reservasi" judul="Reservasi">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Persediaan', 'Reservasi']} />
        </P>
        <P>
          Reservasi menahan sejumlah stok untuk pekerjaan tertentu. Barangnya masih di rak — fisik tidak
          berubah — tetapi tersedia bersihnya berkurang, sehingga tidak ikut dihitung sebagai bisa diambil
          pekerjaan lain.
        </P>
        <Tabel
          kepala={['Status', 'Artinya']}
          baris={[
            ['Aktif', 'Sedang menahan stok.'],
            ['Dipakai', 'Barangnya sudah diambil; menjadi pengeluaran.'],
            ['Dilepas', 'Dibatalkan; stok kembali tersedia.'],
            ['Kadaluarsa', 'Lewat masa berlaku dan dilepas sendiri oleh sistem.'],
          ]}
        />
        <Daftar>
          <Butir>Reservasi tidak boleh melebihi tersedia bersih; sistem menolaknya.</Butir>
          <Butir>
            Reservasi yang tidak pernah dipakai sebaiknya dilepas, bukan dibiarkan. Selama aktif, ia
            menyembunyikan stok yang sebenarnya menganggur.
          </Butir>
        </Daftar>
      </Bagian>
    </>
  );
}
