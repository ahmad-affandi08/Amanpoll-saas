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
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';
import { Tangkapan } from '@/features/Dokumentasi/components/Tangkapan';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'organisasi', judul: 'Organisasi' },
  { id: 'unit', judul: 'Unit organisasi' },
  { id: 'lokasi', judul: 'Lokasi' },
  { id: 'konfigurasi', judul: 'Konfigurasi sistem' },
  { id: 'berkas', judul: 'Berkas dan lampiran' },
];

export function Organisasi() {
  return (
    <>
      <Bagian id="organisasi" judul="Organisasi">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Organisasi']} />
        </P>
        <P>
          Satu akun Amanpoll melayani satu organisasi. Seluruh data yang dibuat di dalamnya — aset, keluhan,
          stok, dokumen — terikat pada organisasi itu dan tidak pernah terlihat dari organisasi lain.
        </P>
        <Awas>
          Zona waktu organisasi menentukan perhitungan tenggat SLA, jadwal preventif, dan jatuh tempo
          kalibrasi. Aturlah sebelum data operasional masuk: mengubahnya belakangan <Tegas>tidak</Tegas>{' '}
          menghitung ulang tenggat yang sudah terbit.
        </Awas>
        <Tangkapan
          gambar="organisasi/profil"
          alt="Halaman profil organisasi dengan isian Zona Waktu dan tombol Simpan Perubahan"
          langkah={[
            {
              penanda: 'zona',
              isi: (
                <>
                  Pilih <Ui>Zona Waktu</Ui> tempat organisasi beroperasi, mis. Asia/Jakarta untuk WIB.
                </>
              ),
            },
            {
              penanda: 'simpan',
              isi: (
                <>
                  Klik <Ui>Simpan Perubahan</Ui>.
                </>
              ),
            },
          ]}
        />
      </Bagian>

      <Bagian id="unit" judul="Unit organisasi">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Unit Organisasi']} />
        </P>
        <P>
          Unit adalah pembagian internal: divisi, departemen, cabang. Unit dipakai untuk menandai aset mana
          milik siapa, dan menjadi dasar penyaringan di hampir seluruh laporan.
        </P>
        <Daftar>
          <Butir>
            <Ui>Kode</Ui> boleh dikosongkan; sistem mengisinya sendiri. Lihat halaman Penomoran.
          </Butir>
          <Butir>
            <Ui>Induk</Ui> membuat unit bertingkat. Sebuah unit tidak boleh menjadi induk dirinya sendiri atau
            induk dari induknya — sistem menolak lingkaran seperti itu.
          </Butir>
          <Butir>
            <Ui>Urutan</Ui> menentukan posisinya di daftar dan pemilih, bukan hierarki.
          </Butir>
        </Daftar>
        <Tangkapan
          gambar="organisasi/unit-formulir"
          alt="Formulir Tambah Unit Organisasi"
          langkah={[
            {
              isi: (
                <>
                  Klik <Ui>Tambah Unit</Ui> di kanan atas halaman Unit Organisasi.
                </>
              ),
            },
            {
              penanda: 'kode',
              isi: (
                <>
                  Kode terisi sendiri; klik <Ui>Atur sendiri</Ui> hanya bila organisasi sudah punya kode unit.
                </>
              ),
            },
            {
              penanda: 'jenis',
              isi: (
                <>
                  Isi <Ui>Jenis</Ui>, mis. Divisi, Departemen, atau Cabang.
                </>
              ),
            },
            {
              penanda: 'induk',
              isi: (
                <>
                  Pilih <Ui>Induk</Ui> untuk unit bertingkat, atau biarkan <Ui>Tanpa induk</Ui>.
                </>
              ),
            },
            {
              penanda: 'pengelola',
              isi: (
                <>
                  Aktifkan <Ui>Mengelola aset</Ui> bila unit ini memelihara aset, mis. IPSRS atau IT. Lihat
                  halaman Unit Pengelola.
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
      </Bagian>

      <Bagian id="lokasi" judul="Lokasi">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Lokasi']} />
        </P>
        <P>
          Setiap aset menempati satu lokasi. Lokasi juga bertingkat, sehingga Gedung A › Lantai 2 › Ruang
          Server dapat dinyatakan apa adanya.
        </P>
        <SubJudul>Kategori lokasi</SubJudul>
        <P>
          Sebelum membuat lokasi, buat kategorinya lewat tombol <Ui>Kelola Kategori</Ui> di halaman yang sama.
          Kategori dipakai menyaring daftar dan mengelompokkan laporan, mis. Gedung, Lantai, Ruang, Area Luar.
        </P>
        <Tangkapan
          gambar="organisasi/lokasi"
          alt="Halaman Lokasi dengan tombol Kelola Kategori dan Tambah Lokasi"
          langkah={[
            {
              penanda: 'kategori',
              isi: (
                <>
                  Buat kategorinya dulu lewat <Ui>Kelola Kategori</Ui>.
                </>
              ),
            },
            {
              penanda: 'tambah',
              isi: (
                <>
                  Klik <Ui>Tambah Lokasi</Ui>, isi nama, kategori, dan induknya bila lokasi ini bagian dari
                  gedung atau lantai.
                </>
              ),
            },
          ]}
        />
        <Catatan>
          Lokasi yang masih memiliki sub-lokasi atau masih ditempati aset tidak dapat dihapus. Nonaktifkan
          saja bila sudah tidak dipakai — riwayat aset yang pernah menempatinya tetap utuh.
        </Catatan>
      </Bagian>

      <Bagian id="konfigurasi" judul="Konfigurasi sistem">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Konfigurasi Sistem']} />
        </P>
        <P>
          Berisi setelan perilaku yang berlaku seluruh organisasi. Ubah seperlunya saja; nilai bawaannya
          dipilih agar aman untuk sebagian besar organisasi.
        </P>
      </Bagian>

      <Bagian id="berkas" judul="Berkas dan lampiran">
        <P>
          Setiap berkas yang disimpan — lampiran, foto, logo, dan hasil ekspor laporan — dipadatkan otomatis
          supaya hemat ruang dan kuota internet. Foto dan gambar diperkecil di perangkat Anda sebelum dikirim,
          lalu disimpan sebagai <Tegas>WebP</Tegas> dengan data lokasi (GPS) dibuang; karena itu gambar yang
          diunduh kembali bisa bernama <Kode>.webp</Kode>. Berkas CSV, teks, dan PDF dipadatkan di server,
          tetapi yang Anda unduh <Tegas>tetap utuh</Tegas> sama persis dengan aslinya. Excel, Word, dan ZIP
          disimpan apa adanya.
        </P>
        <Catatan>
          Pemakaian ruang berkas dan persentase penghematannya terlihat di kartu <Ui>Penyimpanan berkas</Ui>{' '}
          pada <Jalur ruas={['Administrasi', 'Langganan']} />.
        </Catatan>
      </Bagian>
    </>
  );
}
