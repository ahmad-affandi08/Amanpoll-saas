import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
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
  { id: 'wajib', judul: 'Pola bawaan' },
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

      <Bagian id="wajib" judul="Pola bawaan">
        <P>
          Jenis dokumen di bawah ini tidak perlu diatur lebih dulu. Bila polanya belum ada, sistem memasang
          pola bawaan saat dokumen pertama dibuat: <Kode>{'{Awalan}/{Tahun}/{Nomor:4}'}</Kode>, dihitung ulang
          tiap tahun, mis. <Kode>PO/2026/0001</Kode>. Pola itu boleh diubah kapan saja di halaman Nomor
          Dokumen; nomor yang sudah terbit tidak ikut berubah.
        </P>
        <Tabel
          kepala={['Jenis Dokumen', 'Awalan', 'Dipakai saat']}
          baris={[
            [<Kode key="k">Keluhan</Kode>, 'KLH', 'Keluhan baru dibuat.'],
            [<Kode key="pk">PerintahKerja</Kode>, 'PK', 'Perintah kerja dibuat.'],
            [<Kode key="i">Inspeksi</Kode>, 'INS', 'Inspeksi berkala dibuat.'],
            [<Kode key="kal">Kalibrasi</Kode>, 'KAL', 'Pelaksanaan kalibrasi dicatat.'],
            [
              <Kode key="ms">MutasiStok</Kode>,
              'MS',
              'Mutasi stok dibuat, termasuk saldo awal dan penerimaan barang.',
            ],
            [<Kode key="rsv">ReservasiSukuCadang</Kode>, 'RSV', 'Suku cadang dipesan untuk perintah kerja.'],
            [<Kode key="ua">UsulanAset</Kode>, 'UA', 'Usulan aset diajukan.'],
            [<Kode key="rp">RencanaPengadaan</Kode>, 'RP', 'Rencana pengadaan disusun.'],
            [<Kode key="pp">PermintaanPembelian</Kode>, 'PP', 'Permintaan pembelian dibuat.'],
            [<Kode key="rfq">PermintaanPenawaran</Kode>, 'RFQ', 'Permintaan penawaran dibuka.'],
            [<Kode key="po">PesananPembelian</Kode>, 'PO', 'Pesanan pembelian dibuat.'],
            [
              <Kode key="grn">PenerimaanPembelian</Kode>,
              'GRN',
              'Barang pesanan diterima (bila nomornya dikosongkan).',
            ],
            [<Kode key="ktr">Kontrak</Kode>, 'KTR', 'Kontrak dicatat.'],
            [<Kode key="mut">PermintaanMutasiAset</Kode>, 'MUT', 'Mutasi aset diminta.'],
            [<Kode key="pha">PengajuanPenghapusanAset</Kode>, 'PHA', 'Penghapusan aset diajukan.'],
            [<Kode key="sta">SerahTerimaAset</Kode>, 'STA', 'Serah terima aset dibuat.'],
          ]}
        />
        <Catatan>
          Ubah polanya sebelum dokumen pertama dibuat bila organisasi sudah punya kebiasaan penomoran sendiri,
          supaya nomor pertama langsung mengikuti kebiasaan itu.
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
