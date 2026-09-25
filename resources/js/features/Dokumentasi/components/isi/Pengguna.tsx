import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Awas,
  Bagian,
  Butir,
  Catatan,
  Daftar,
  Jalur,
  Kode,
  P,
  SubJudul,
  Tabel,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';
import { Tangkapan } from '@/features/Dokumentasi/components/Tangkapan';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'cara-kerja', judul: 'Cara kerja izin' },
  { id: 'peran', judul: 'Membuat peran' },
  { id: 'pengguna', judul: 'Menambahkan pengguna' },
  { id: 'saran', judul: 'Saran pembagian peran' },
];

export function Pengguna() {
  return (
    <>
      <Bagian id="cara-kerja" judul="Cara kerja izin">
        <P>
          Izin tidak pernah ditempelkan langsung ke pengguna. Izin dimiliki peran, dan pengguna memegang satu
          atau beberapa peran. Pengguna tanpa peran dapat masuk tetapi tidak dapat membuka apa pun.
        </P>
        <P>
          Kode izin berbentuk <Kode>Modul.Tindakan</Kode>, mis. <Kode>Aset.Buat</Kode>,{' '}
          <Kode>Stok.Kelola</Kode>, <Kode>Pengguna.Kelola</Kode>. Menu di sidebar menyembunyikan dirinya
          sendiri bila izinnya tidak dipegang, jadi tampilan tiap orang berbeda-beda.
        </P>
      </Bagian>

      <Bagian id="peran" judul="Membuat peran">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Peran & Izin']} /> lalu <Ui>Tambah Peran</Ui>
          .
        </P>
        <Tangkapan
          gambar="pengguna/peran"
          alt="Halaman Peran & Izin dengan tombol Tambah Peran dan Kelola Izin"
          langkah={[
            {
              penanda: 'tambah',
              isi: (
                <>
                  Klik <Ui>Tambah Peran</Ui>. Beri nama yang menyebut pekerjaannya, bukan orangnya: Teknisi,
                  Penyelia Gudang, Admin.
                </>
              ),
            },
            {
              penanda: 'izin',
              isi: (
                <>
                  Klik <Ui>Kelola Izin</Ui> pada baris peran itu.
                </>
              ),
            },
          ]}
        />
        <Tangkapan
          gambar="pengguna/izin"
          alt="Dialog izin sebuah peran berisi kotak centang yang dikelompokkan per modul"
          langkah={[
            {
              penanda: 'centang',
              isi: (
                <>
                  Centang izin yang diperlukan. Daftarnya dikelompokkan per modul, mis. Aset, Audit,
                  Kalibrasi.
                </>
              ),
            },
            {
              isi: (
                <>
                  Gulir ke bawah dan klik <Ui>Simpan Izin</Ui>. Perubahan langsung berlaku pada pengguna yang
                  memegang peran itu.
                </>
              ),
            },
          ]}
        />
        <Awas>
          Peran bawaan sistem tidak dapat dihapus. Peran yang masih dipegang pengguna juga tidak — lepaskan
          dulu penugasannya.
        </Awas>
      </Bagian>

      <Bagian id="pengguna" judul="Menambahkan pengguna">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Pengguna']} /> lalu <Ui>Tambah Pengguna</Ui>.
        </P>
        <Daftar>
          <Butir>
            <Ui>Email</Ui> harus unik dalam satu organisasi dan dipakai untuk masuk. Pengguna masuk cukup
            dengan email dan kata sandi, tanpa kode organisasi. Bila email yang sama terdaftar di beberapa
            organisasi dengan kata sandi yang sama, ia diminta memilih organisasinya sesudah masuk.
          </Butir>
          <Butir>
            <Ui>Jenis Pengguna</Ui> membedakan pengguna internal dari pihak luar seperti teknisi vendor.
          </Butir>
          <Butir>
            Setelah tersimpan, buka <Ui>Kelola Peran</Ui> pada barisnya untuk menetapkan peran.
          </Butir>
        </Daftar>
        <Tangkapan
          gambar="pengguna/formulir"
          alt="Formulir Tambah Pengguna"
          langkah={[
            {
              isi: (
                <>
                  Klik <Ui>Tambah Pengguna</Ui>, lalu isi nama dan kata sandi awalnya.
                </>
              ),
            },
            {
              penanda: 'email',
              isi: (
                <>
                  Isi <Ui>Email</Ui>; alamat ini dipakai untuk masuk.
                </>
              ),
            },
            {
              penanda: 'jenis',
              isi: (
                <>
                  Pilih <Ui>Jenis Pengguna</Ui>.
                </>
              ),
            },
            {
              penanda: 'simpan',
              isi: (
                <>
                  Klik <Ui>Simpan</Ui>.
                </>
              ),
            },
          ]}
        />
        <Tangkapan
          gambar="pengguna/kelola-peran"
          alt="Dialog Kelola Peran berisi peran yang dipegang, pemilih peran, dan pembatas unit serta ruangan"
          langkah={[
            {
              isi: (
                <>
                  Klik <Ui>Kelola Peran</Ui> pada baris pengguna. Peran yang sudah dipegang tampil paling atas
                  dan bisa dicabut.
                </>
              ),
            },
            { penanda: 'peran', isi: <>Pilih peran yang akan ditambahkan.</> },
            {
              penanda: 'lingkup',
              isi: (
                <>
                  Batasi ke unit atau ruangan tertentu bila pengguna hanya boleh melihat data di sana. Biarkan{' '}
                  <Ui>Semua unit</Ui> untuk seluruh organisasi.
                </>
              ),
            },
            {
              penanda: 'tetapkan',
              isi: (
                <>
                  Klik <Ui>Tetapkan</Ui>.
                </>
              ),
            },
          ]}
        />
        <Catatan>
          Pengguna yang keluar dari organisasi sebaiknya <Tegas>dinonaktifkan</Tegas>, bukan dihapus.
          Menonaktifkan menutup aksesnya seketika sambil menjaga jejaknya di riwayat perintah kerja dan log
          audit tetap terbaca.
        </Catatan>
      </Bagian>

      <Bagian id="saran" judul="Saran pembagian peran">
        <P>Titik awal yang biasanya cukup untuk organisasi yang baru mulai:</P>
        <Tabel
          kepala={['Peran', 'Kira-kira memegang']}
          baris={[
            ['Administrator', 'Seluruh izin, termasuk Pengaturan.Kelola dan Pengguna.Kelola.'],
            ['Penyelia Pemeliharaan', 'Pemeliharaan.Kelola, PerintahKerja.Kelola, Aset.Ubah.'],
            ['Teknisi', 'Melihat aset, menerima dan mengerjakan perintah kerja, memakai suku cadang.'],
            ['Penyelia Gudang', 'Stok.Kelola dan Penyedia.Kelola.'],
            ['Pelapor', 'Hanya membuat keluhan dan melihat status keluhannya sendiri.'],
          ]}
        />
      </Bagian>
    </>
  );
}
