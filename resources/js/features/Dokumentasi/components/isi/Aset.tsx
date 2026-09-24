import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Bagian,
  Butir,
  Catatan,
  Daftar,
  Jalur,
  Kode,
  Langkah,
  P,
  SubJudul,
  Tabel,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'membuat', judul: 'Membuat aset' },
  { id: 'impor', judul: 'Impor banyak aset sekaligus' },
  { id: 'status', judul: 'Status dan kondisi' },
  { id: 'qr', judul: 'Kode QR' },
  { id: 'tambahan', judul: 'Tag dan kolom kustom' },
];

export function Aset() {
  return (
    <>
      <Bagian id="membuat" judul="Membuat aset">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Manajemen Aset', 'Daftar Aset']} /> lalu <Ui>Tambah Aset</Ui>.
        </P>
        <P>Yang menentukan perilaku aset di modul lain:</P>
        <Daftar>
          <Butir>
            <Ui>Kategori</Ui> menentukan apakah aset ini masuk hitungan kalibrasi dan pemeliharaan.
          </Butir>
          <Butir>
            <Ui>Lokasi</Ui> menentukan di mana ia dicari dan siapa yang bertanggung jawab atasnya.
          </Butir>
          <Butir>
            <Ui>Unit Organisasi</Ui> menentukan pemiliknya untuk keperluan laporan dan anggaran.
          </Butir>
          <Butir>
            <Ui>Nomor Seri</Ui> ikut dicari oleh kotak pencarian, jadi mengisinya mempermudah teknisi
            menemukan aset dari barangnya langsung.
          </Butir>
          <Butir>
            <Ui>Kode Aset</Ui> boleh dikosongkan; sistem mengisinya sendiri.
          </Butir>
        </Daftar>
      </Bagian>

      <Bagian id="impor" judul="Impor banyak aset sekaligus">
        <P>
          Bila aset sudah tercatat di Excel, tidak perlu diketik ulang satu per satu. Buka{' '}
          <Jalur ruas={['Operasional & Aset', 'Manajemen Aset', 'Daftar Aset']} /> lalu <Ui>Impor</Ui>. Tombol
          ini hanya tampil bagi yang boleh mendaftarkan aset.
        </P>
        <Langkah
          daftar={[
            {
              judul: 'Unduh templat',
              isi: (
                <P>
                  Pilih <Ui>Templat Excel (XLSX)</Ui> atau <Ui>Templat CSV</Ui>. Templat Excel punya lembar{' '}
                  <Ui>Petunjuk</Ui> yang menjelaskan tiap kolom dan memuat daftar kode kategori, lokasi, dan
                  unit yang bisa dipakai.
                </P>
              ),
            },
            {
              judul: 'Isi satu aset per baris',
              isi: (
                <>
                  <P>
                    Hanya <Ui>Nama</Ui> dan <Ui>Kode Kategori</Ui> yang wajib. Kategori, lokasi, unit
                    organisasi, unit pengelola, model, dan penyedia diisi dengan <Tegas>kode</Tegas>-nya,
                    bukan namanya. Hapus baris contoh sebelum mengunggah.
                  </P>
                  <P>
                    <Ui>Kode Aset</Ui> boleh dikosongkan supaya sistem yang mengisinya. Status, kondisi, dan
                    tingkat kritis yang kosong dianggap Aktif, Baik, dan Normal.
                  </P>
                </>
              ),
            },
            {
              judul: 'Periksa hasil pratinjau',
              isi: (
                <P>
                  Pilih berkasnya lalu <Ui>Periksa Berkas</Ui>. Belum ada yang disimpan pada langkah ini.
                  Sistem menampilkan jumlah baris yang siap dibuat dan daftar masalah per baris, lengkap
                  dengan kolom dan isi selnya. Bila masalahnya banyak, unduh daftarnya lewat{' '}
                  <Ui>Unduh Daftar Galat</Ui>.
                </P>
              ),
            },
            {
              judul: 'Perbaiki, unggah ulang, lalu impor',
              isi: (
                <P>
                  Perbaiki berkas di Excel, simpan, lalu pilih ulang. Setelah semua baris bersih, tekan{' '}
                  <Ui>Impor N Aset</Ui>. Kode QR dan riwayat lokasi awal setiap aset langsung terbentuk, sama
                  seperti mendaftarkan lewat formulir.
                </P>
              ),
            },
          ]}
        />
        <Tabel
          kepala={['Aturan', 'Artinya']}
          baris={[
            ['Semua atau tidak sama sekali', 'Satu baris saja salah, tidak ada aset yang dibuat.'],
            ['Hanya aset baru', 'Kode aset yang sudah dipakai, termasuk aset yang diarsipkan, ditolak.'],
            ['Tidak boleh kembar', 'Dua baris dengan kode aset yang sama di satu berkas ditolak.'],
            ['Batas berkas', 'CSV atau XLSX, paling banyak 1.000 baris dan 5 MB.'],
            [
              'Lingkup akses',
              'Pengguna yang aksesnya dibatasi hanya bisa mengimpor ke lokasi, unit, atau unit pengelola dalam lingkupnya.',
            ],
          ]}
        />
        <Catatan>
          Tanggal boleh ditulis <Kode>2024-05-31</Kode> atau <Kode>31/05/2024</Kode>. Harga ditulis tanpa
          titik ribuan, mis. <Kode>15000000</Kode>. Kolom <Ui>Kode Unit Pengelola</Ui> hanya muncul di templat
          bila organisasi Anda memakai unit pengelola.
        </Catatan>
      </Bagian>

      <Bagian id="status" judul="Status dan kondisi">
        <P>
          Keduanya berbeda dan keduanya dipakai. Status menjawab &ldquo;boleh dipakai atau tidak&rdquo;,
          kondisi menjawab &ldquo;seberapa baik keadaannya&rdquo;.
        </P>
        <Tabel
          kepala={['Kolom', 'Pilihan']}
          baris={[
            ['Status', 'Aktif, Nonaktif, Dipinjam, Rusak, Diarsipkan'],
            ['Kondisi', 'Baik, Perlu Perhatian, Rusak'],
            ['Tingkat Kritis', 'Normal, Tinggi, Sangat Tinggi'],
          ]}
        />
        <Catatan>
          <Ui>Tingkat Kritis</Ui> dipakai memilah mana yang didahulukan saat banyak keluhan masuk bersamaan.
          Isilah dengan jujur — kalau semua aset ditandai Sangat Tinggi, kolom ini berhenti berguna.
        </Catatan>
      </Bagian>

      <Bagian id="qr" judul="Kode QR">
        <P>
          Tiap aset memiliki kode QR yang dapat dicetak dan ditempel di barangnya. Memindainya membuka halaman
          aset itu tanpa perlu mencari di daftar — inilah cara tercepat teknisi di lapangan membuat keluhan
          atas aset yang tepat.
        </P>
      </Bagian>

      <Bagian id="tambahan" judul="Tag dan kolom kustom">
        <SubJudul>Tag</SubJudul>
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Tag Kolaborasi']} />. Label bebas
          berwarna untuk menandai apa saja lintas modul, mis. &ldquo;Sewa&rdquo; atau &ldquo;Perlu
          Diganti&rdquo;.
        </P>
        <SubJudul>Kolom kustom</SubJudul>
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Kolom Kustom']} />. Dipakai bila ada
          data yang perlu disimpan tetapi tidak tersedia kolomnya — mis. nomor polis asuransi. Gunakan
          seperlunya; kolom kustom tidak ikut di semua laporan bawaan.
        </P>
      </Bagian>
    </>
  );
}
