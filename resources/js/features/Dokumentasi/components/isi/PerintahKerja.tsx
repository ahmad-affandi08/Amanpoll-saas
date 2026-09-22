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
