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
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';
import { Tangkapan } from '@/features/Dokumentasi/components/Tangkapan';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'membuat', judul: 'Membuat perintah kerja' },
  { id: 'status', judul: 'Status' },
  { id: 'suku-cadang', judul: 'Suku cadang terpakai' },
  { id: 'kode-kegagalan', judul: 'Kode kegagalan' },
];

export function PerintahKerja() {
  return (
    <>
      <Bagian id="membuat" judul="Membuat perintah kerja">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Pemeliharaan', 'Perintah Kerja']} />
        </P>
        <P>Perintah kerja lahir dari tiga sumber:</P>
        <Daftar>
          <Butir>Dari keluhan yang sudah diterima — ini yang paling sering.</Butir>
          <Butir>Dari rencana pemeliharaan preventif, dibuatkan sistem sesuai jadwalnya.</Butir>
          <Butir>Dibuat langsung, untuk pekerjaan yang tidak berawal dari laporan siapa pun.</Butir>
        </Daftar>
        <Tangkapan
          gambar="perintah-kerja/daftar"
          alt="Halaman daftar perintah kerja dengan menu Perintah Kerja dan tombol Buat Perintah Kerja"
          langkah={[
            {
              penanda: 'menu',
              isi: (
                <>
                  Buka menu <Ui>Pemeliharaan</Ui> › <Ui>Perintah Kerja</Ui>.
                </>
              ),
            },
            {
              penanda: 'buat',
              isi: (
                <>
                  Klik <Ui>Buat Perintah Kerja</Ui> untuk pekerjaan baru.
                </>
              ),
            },
            {
              penanda: 'buka',
              isi: (
                <>
                  Klik nomor perintah kerja (mis. <Ui>PK/2026/0067</Ui>) untuk melihat dan mengerjakannya.
                </>
              ),
            },
          ]}
        />
        <Tangkapan
          gambar="perintah-kerja/formulir"
          alt="Formulir Buat Perintah Kerja"
          langkah={[
            {
              isi: (
                <>
                  Bila pekerjaan berasal dari keluhan, pilih keluhannya di bagian paling atas; judul dan aset
                  terisi sendiri.
                </>
              ),
            },
            {
              penanda: 'jenis',
              isi: (
                <>
                  Pilih <Ui>Jenis Pekerjaan</Ui>: korektif untuk perbaikan, preventif untuk perawatan rutin.
                </>
              ),
            },
            {
              penanda: 'prioritas',
              isi: (
                <>
                  Tentukan <Ui>Prioritas</Ui>, lalu centang aset yang ditangani di daftar di bawahnya.
                </>
              ),
            },
            {
              penanda: 'simpan',
              isi: (
                <>
                  Klik <Ui>Simpan Perintah Kerja</Ui>.
                </>
              ),
            },
          ]}
        />
        <SubJudul>Mengerjakan perintah kerja</SubJudul>
        <Tangkapan
          gambar="perintah-kerja/detail"
          alt="Halaman detail perintah kerja dengan tombol Tugaskan Teknisi, Mulai Kerja, dan Reservasi Suku Cadang"
          langkah={[
            {
              penanda: 'tugaskan',
              isi: (
                <>
                  Klik <Ui>Tugaskan Teknisi</Ui> untuk memilih pelaksana.
                </>
              ),
            },
            {
              penanda: 'mulai',
              isi: (
                <>
                  Teknisi mengeklik <Ui>Mulai Kerja</Ui> saat tiba di lokasi; jam kerjanya mulai dihitung.
                </>
              ),
            },
            {
              penanda: 'suku',
              isi: (
                <>
                  Klik <Ui>Reservasi Suku Cadang</Ui> untuk menahan barang yang akan dipakai.
                </>
              ),
            },
          ]}
        />
      </Bagian>

      <Bagian id="status" judul="Status">
        <P>
          Statusnya lebih rinci daripada keluhan, karena pekerjaan lapangan sering berhenti bukan karena
          selesai.
        </P>
        <Tabel
          kepala={['Status', 'Artinya']}
          baris={[
            ['Draf', 'Belum siap ditugaskan.'],
            ['Terjadwal', 'Sudah punya tanggal, belum ada pelaksananya.'],
            ['Ditugaskan', 'Sudah ada teknisinya, belum diterima.'],
            ['Diterima', 'Teknisi menyanggupi.'],
            ['Dikerjakan', 'Sedang berjalan.'],
            ['Menunggu Suku Cadang', 'Berhenti karena barangnya belum ada.'],
            ['Menunggu Penyedia', 'Berhenti karena menunggu pihak luar.'],
            ['Dijeda', 'Berhenti karena alasan lain.'],
            ['Menunggu Verifikasi', 'Pekerjaan diklaim selesai, menunggu diperiksa.'],
            ['Selesai', 'Sudah diverifikasi.'],
            ['Ditutup', 'Final, tidak dapat diubah.'],
            ['Dibatalkan', 'Tidak jadi dikerjakan.'],
          ]}
        />
        <Catatan>
          Tiga status &ldquo;menunggu&rdquo; itu yang membuat laporan berguna: keduanya memisahkan pekerjaan
          yang lambat karena teknisinya sibuk dari yang lambat karena stok atau vendor. Pakailah, jangan
          dibiarkan berstatus Dikerjakan terus.
        </Catatan>
      </Bagian>

      <Bagian id="suku-cadang" judul="Suku cadang terpakai">
        <P>
          Suku cadang yang dipakai dicatat di perintah kerjanya. Pencatatan itu <Tegas>mengurangi stok</Tegas>{' '}
          lewat mutasi stok yang dibuat sistem, jadi tidak perlu — dan tidak boleh — dicatat dua kali di menu
          persediaan.
        </P>
        <P>
          Bila barangnya belum tersedia, buat reservasi lebih dulu. Reservasi menahan stok supaya tidak
          terpakai perintah kerja lain sementara barangnya menunggu diambil.
        </P>
      </Bagian>

      <Bagian id="kode-kegagalan" judul="Kode kegagalan">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Pemeliharaan', 'Kode Kegagalan']} />
        </P>
        <P>
          Daftar sebab kerusakan yang baku, mis. &ldquo;Aus normal&rdquo;, &ldquo;Kesalahan
          pengoperasian&rdquo;, &ldquo;Cacat produksi&rdquo;. Teknisi memilih salah satunya saat menutup
          pekerjaan.
        </P>
        <SubJudul>Kenapa perlu</SubJudul>
        <P>
          Tanpa kode kegagalan, satu-satunya cara mengetahui aset mana yang paling merepotkan adalah membaca
          catatan bebas satu per satu. Dengan kode, pola berulang terlihat di laporan — dan itulah yang
          mengubah pemeliharaan dari menambal jadi mencegah.
        </P>
      </Bagian>
    </>
  );
}
