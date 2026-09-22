import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Awas,
  Bagian,
  Blok,
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

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'nomor-dokumen', judul: 'Pola nomor dokumen' },
  { id: 'wajib', judul: 'Yang wajib diisi' },
  { id: 'kode-otomatis', judul: 'Kode otomatis' },
  { id: 'hari-libur', judul: 'Hari libur' },
];

export function Penomoran() {
  return (
    <>
      <Bagian id="nomor-dokumen" judul="Pola nomor dokumen">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Nomor Dokumen']} />
        </P>
        <P>
          Dokumen transaksi menerima nomor berurutan yang terbit sendiri saat dokumen dibuat. Polanya
          ditetapkan per jenis dokumen.
        </P>
        <Tabel
          kepala={['Kolom', 'Arti']}
          baris={[
            ['Jenis Dokumen', 'Nama jenis yang dirujuk kode program. Harus persis, lihat daftar di bawah.'],
            ['Awalan', 'Huruf di depan nomor, mis. KEL untuk keluhan.'],
            ['Format Nomor', 'Susunan nomornya.'],
            ['Reset Periode', 'Kapan hitungan kembali ke 1: Tahunan, Bulanan, atau tidak sama sekali.'],
          ]}
        />
        <SubJudul>Contoh format</SubJudul>
        <Blok>{`{Awalan}-{Tahun}-{Nomor:4}   ->   KEL-2026-0001`}</Blok>
        <P>
          <Kode>{'{Nomor:4}'}</Kode> berarti nomor urut dengan empat digit berpadding nol.
        </P>
      </Bagian>

      <Bagian id="wajib" judul="Yang wajib diisi">
        <Awas>
          Delapan jenis ini <Tegas>menolak dibuat</Tegas> selama polanya belum ada. Pesannya berbunyi
          &ldquo;Pola nomor dokumen untuk &hellip; belum diatur&rdquo;.
        </Awas>
        <Tabel
          kepala={['Jenis Dokumen', 'Dipakai saat']}
          baris={[
            [<Kode key="k">Keluhan</Kode>, 'Keluhan baru dibuat.'],
            [<Kode key="pk">PerintahKerja</Kode>, 'Perintah kerja dibuat.'],
            [<Kode key="i">Inspeksi</Kode>, 'Inspeksi berkala dibuat.'],
            [<Kode key="kal">Kalibrasi</Kode>, 'Pelaksanaan kalibrasi dicatat.'],
            [<Kode key="ms">MutasiStok</Kode>, 'Mutasi stok dibuat, termasuk saldo awal.'],
            [<Kode key="ua">UsulanAset</Kode>, 'Usulan aset diajukan.'],
            [<Kode key="rp">RencanaPengadaan</Kode>, 'Rencana pengadaan disusun.'],
            [<Kode key="pp">PermintaanPembelian</Kode>, 'Permintaan pembelian dibuat.'],
          ]}
        />
        <Catatan>
          <Kode>PesananPembelian</Kode> dan <Kode>PermintaanPenawaran</Kode> tidak wajib: bila polanya belum
          ada, sistem menerbitkan nomor cadangan sendiri. Mengaturnya tetap disarankan supaya penomorannya
          rapi dan dapat ditebak.
        </Catatan>
      </Bagian>

      <Bagian id="kode-otomatis" judul="Kode otomatis">
        <P>
          Berbeda dari nomor dokumen, data induk memakai <Ui>Kode</Ui> — aset, lokasi, gudang, kategori, suku
          cadang, dan puluhan lainnya.
        </P>
        <P>
          Kolom itu <Tegas>boleh dikosongkan</Tegas>. Bila kosong, sistem membuatkan kode dari awalan entitas
          dan nomor urut, mis. <Kode>GDG-0001</Kode> untuk gudang, <Kode>LOK-0001</Kode> untuk lokasi. Kode
          yang sudah terbit tidak pernah berubah sendiri.
        </P>
        <Daftar>
          <Butir>
            Di form, kolom kode tersembunyi di balik tautan <Ui>Atur sendiri</Ui>. Bukalah hanya bila
            organisasi sudah punya penomoran sendiri yang harus dipertahankan.
          </Butir>
          <Butir>
            Kode yang diisi manual harus unik. Mengosongkannya saat menyunting tidak menghapus kode lama,
            melainkan mempertahankannya.
          </Butir>
        </Daftar>
      </Bagian>

      <Bagian id="hari-libur" judul="Hari libur">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Hari Libur']} />
        </P>
        <P>
          Perhitungan tenggat SLA melewati hari yang terdaftar di sini. Kalender yang kosong membuat tenggat
          jatuh lebih cepat daripada seharusnya, dan eskalasi berjalan di hari yang tidak ada orangnya.
        </P>
        <Daftar>
          <Butir>
            <Ui>Berulang Tahunan</Ui> untuk tanggal tetap seperti 17 Agustus; cukup dimasukkan sekali.
          </Butir>
          <Butir>
            Libur nasional yang tanggalnya berubah tiap tahun dimasukkan sebagai tanggal sekali jalan.
          </Butir>
          <Butir>
            <Ui>Lokasi</Ui> dapat diisi bila liburnya hanya berlaku di satu lokasi, mis. libur daerah.
          </Butir>
        </Daftar>
      </Bagian>
    </>
  );
}
