import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import { ruteDokumentasi } from '@/features/Dokumentasi/api';
import {
  Awas,
  Bagian,
  Butir,
  Daftar,
  Jalur,
  Kode,
  Langkah,
  P,
  TautanDoc,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'wajib', judul: 'Enam langkah wajib' },
  { id: 'per-modul', judul: 'Lanjutan per modul' },
  { id: 'siap', judul: 'Tanda sudah siap' },
];

export function Persiapan() {
  return (
    <>
      <Bagian id="wajib" judul="Enam langkah wajib">
        <P>
          Sebelum satu pun aset atau keluhan dibuat, enam hal ini harus ada. Melewatkan salah satunya membuat
          langkah berikutnya gagal atau menghasilkan angka yang salah.
        </P>

        <Langkah
          daftar={[
            {
              judul: 'Periksa zona waktu organisasi',
              isi: (
                <>
                  <P>
                    Buka <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Organisasi']} /> dan
                    pastikan zona waktunya benar. Seluruh tenggat SLA, jadwal preventif, dan jatuh tempo
                    kalibrasi dihitung dari sana.
                  </P>
                  <P>
                    Mengubahnya setelah ada data berjalan tidak menghitung ulang tenggat yang sudah terbit.
                  </P>
                </>
              ),
            },
            {
              judul: 'Susun unit organisasi',
              isi: (
                <P>
                  <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Unit Organisasi']} />. Unit
                  dipakai untuk menandai kepemilikan aset dan menentukan penanggung jawab. Boleh bertingkat.
                </P>
              ),
            },
            {
              judul: 'Daftarkan lokasi',
              isi: (
                <P>
                  <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Lokasi']} />. Setiap aset
                  menempati satu lokasi. Buat kategori lokasinya lebih dulu bila ingin mengelompokkan, mis.
                  Gedung, Lantai, Ruang.
                </P>
              ),
            },
            {
              judul: 'Buat peran dan undang pengguna',
              isi: (
                <P>
                  <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Peran & Izin']} /> lalu{' '}
                  <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Pengguna']} />. Pengguna tanpa peran
                  tidak dapat melakukan apa pun selain masuk.
                </P>
              ),
            },
            {
              judul: 'Atur pola nomor dokumen',
              isi: (
                <>
                  <P>
                    <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Nomor Dokumen']} />. Ini
                    langkah yang paling sering terlewat.
                  </P>
                  <Awas>
                    Delapan jenis dokumen <Tegas>menolak dibuat</Tegas> bila polanya belum ada, dengan pesan
                    &ldquo;Pola nomor dokumen untuk &hellip; belum diatur&rdquo;: <Kode>Keluhan</Kode>,{' '}
                    <Kode>PerintahKerja</Kode>, <Kode>Inspeksi</Kode>, <Kode>Kalibrasi</Kode>,{' '}
                    <Kode>MutasiStok</Kode>, <Kode>UsulanAset</Kode>, <Kode>RencanaPengadaan</Kode>, dan{' '}
                    <Kode>PermintaanPembelian</Kode>.
                  </Awas>
                </>
              ),
            },
            {
              judul: 'Isi kalender hari libur',
              isi: (
                <P>
                  <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Hari Libur']} />. Tenggat SLA
                  melewati hari libur, jadi kalender yang kosong membuat tenggat jatuh lebih cepat daripada
                  seharusnya.
                </P>
              ),
            },
          ]}
        />

        <P>
          Rinciannya ada di{' '}
          <TautanDoc ke={ruteDokumentasi.halaman('organisasi')}>Organisasi &amp; Lokasi</TautanDoc>,{' '}
          <TautanDoc ke={ruteDokumentasi.halaman('pengguna')}>Pengguna, Peran &amp; Izin</TautanDoc>, dan{' '}
          <TautanDoc ke={ruteDokumentasi.halaman('penomoran')}>Penomoran &amp; Hari Libur</TautanDoc>.
        </P>
      </Bagian>

      <Bagian id="per-modul" judul="Lanjutan per modul">
        <P>
          Setelah enam langkah di atas selesai, modul berikut dapat dikerjakan terpisah dan tidak saling
          menunggu — kecuali yang disebutkan.
        </P>
        <Daftar>
          <Butir>
            <Tegas>Aset.</Tegas> Kategori, merek, dan model dulu, baru asetnya. Kategori menentukan apakah
            aset di bawahnya wajib dikalibrasi dan dipelihara.
          </Butir>
          <Butir>
            <Tegas>Pemeliharaan.</Tegas> Tingkat layanan dan kategori keluhan dulu, baru keluhan dapat dibuat
            dengan tenggat yang benar.
          </Butir>
          <Butir>
            <Tegas>Persediaan.</Tegas> Gudang dan kategori suku cadang dulu, lalu suku cadang. Saldo awal
            dimasukkan lewat mutasi stok, bukan diketik langsung.
          </Butir>
          <Butir>
            <Tegas>Pengadaan.</Tegas> Penyedia dulu. Anggaran hanya diperlukan bila ingin membatasi belanja.
          </Butir>
          <Butir>
            <Tegas>Persetujuan.</Tegas> Dapat ditambahkan kapan saja; alur yang dibuat belakangan hanya
            berlaku untuk dokumen baru.
          </Butir>
        </Daftar>
      </Bagian>

      <Bagian id="siap" judul="Tanda sudah siap">
        <P>Sistem siap dipakai operasional ketika keempat hal ini berhasil dilakukan berurutan:</P>
        <Daftar urut>
          <Butir>
            Satu aset dapat dibuat dan muncul di <Ui>Daftar Aset</Ui>.
          </Butir>
          <Butir>Satu keluhan dapat dibuat atas aset itu dan mendapat nomor serta tenggat.</Butir>
          <Butir>Keluhan itu dapat dijadikan perintah kerja dan ditugaskan ke seorang teknisi.</Butir>
          <Butir>Perintah kerja itu dapat ditutup.</Butir>
        </Daftar>
        <P>
          Bila salah satunya gagal, pesan galatnya hampir selalu menunjuk langsung ke langkah persiapan yang
          terlewat.
        </P>
      </Bagian>
    </>
  );
}
