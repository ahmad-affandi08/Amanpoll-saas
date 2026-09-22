import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Awas,
  Bagian,
  Butir,
  Catatan,
  Daftar,
  Jalur,
  P,
  SubJudul,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'organisasi', judul: 'Organisasi' },
  { id: 'unit', judul: 'Unit organisasi' },
  { id: 'lokasi', judul: 'Lokasi' },
  { id: 'konfigurasi', judul: 'Konfigurasi sistem' },
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
    </>
  );
}
