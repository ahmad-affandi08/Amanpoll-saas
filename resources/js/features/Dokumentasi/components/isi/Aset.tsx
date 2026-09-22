import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Bagian,
  Butir,
  Catatan,
  Daftar,
  Jalur,
  P,
  SubJudul,
  Tabel,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'membuat', judul: 'Membuat aset' },
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
