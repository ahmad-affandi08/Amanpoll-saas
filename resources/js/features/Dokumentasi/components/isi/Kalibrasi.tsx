import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Awas,
  Bagian,
  Butir,
  Daftar,
  Jalur,
  P,
  Tabel,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'jenis', judul: 'Jenis kalibrasi' },
  { id: 'rencana', judul: 'Rencana kalibrasi' },
  { id: 'pelaksanaan', judul: 'Pelaksanaan' },
  { id: 'kepatuhan', judul: 'Dasbor kepatuhan' },
];

export function Kalibrasi() {
  return (
    <>
      <Bagian id="jenis" judul="Jenis kalibrasi">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Kalibrasi', 'Jenis Kalibrasi']} />
        </P>
        <P>
          Jenis menetapkan apa yang diukur dan titik ukur bawaannya, mis. &ldquo;Kalibrasi Massa&rdquo; dengan
          titik 10 g, 100 g, dan 1000 g beserta toleransi plus-minusnya.
        </P>
        <P>
          Menyiapkan titik ukur di jenis berarti pelaksanaan kalibrasi tinggal mengisi hasil, dan penilaian
          lolos atau gagal dihitung sendiri terhadap toleransinya.
        </P>
      </Bagian>

      <Bagian id="rencana" judul="Rencana kalibrasi">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Kalibrasi', 'Rencana Kalibrasi']} />
        </P>
        <Daftar>
          <Butir>Satu rencana mengikat satu aset ke satu jenis kalibrasi.</Butir>
          <Butir>
            Intervalnya menentukan kapan jatuh tempo berikutnya dihitung dari pelaksanaan terakhir.
          </Butir>
          <Butir>
            <Ui>Penyedia</Ui> diisi bila kalibrasinya dikerjakan lembaga luar.
          </Butir>
        </Daftar>
        <Awas>
          Aset yang kategorinya bertanda <Ui>Memerlukan Kalibrasi</Ui> tetapi belum punya rencana akan muncul
          sebagai <Tegas>tidak patuh</Tegas> di dasbor, bukan sebagai aman.
        </Awas>
      </Bagian>

      <Bagian id="pelaksanaan" judul="Pelaksanaan">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Kalibrasi', 'Pelaksanaan Kalibrasi']} />
        </P>
        <P>
          Pelaksanaan mencatat hasil pengukuran per titik ukur. Setelah difinalisasi, hasilnya dikunci dan
          jatuh tempo berikutnya dihitung sendiri.
        </P>
        <Tabel
          kepala={['Hasil', 'Artinya']}
          baris={[
            ['Belum Diuji', 'Pelaksanaan dibuat, hasil belum diisi.'],
            ['Terjadwal', 'Sudah dijadwalkan, belum dilaksanakan.'],
            ['Lolos', 'Seluruh titik ukur di dalam toleransi.'],
            ['Lolos dengan Catatan', 'Di dalam toleransi setelah penyesuaian, atau ada catatan.'],
            ['Gagal', 'Ada titik ukur di luar toleransi.'],
          ]}
        />
        <P>
          Sertifikat kalibrasi dilampirkan pada pelaksanaannya, sehingga saat audit dokumennya berada tepat di
          sebelah angkanya.
        </P>
      </Bagian>

      <Bagian id="kepatuhan" judul="Dasbor kepatuhan">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Kalibrasi', 'Dasbor & Kepatuhan']} />
        </P>
        <Tabel
          kepala={['Status', 'Artinya']}
          baris={[
            ['Valid', 'Kalibrasi terakhir masih berlaku.'],
            ['Segera Jatuh Tempo', 'Mendekati batas; jadwalkan sekarang.'],
            ['Terlambat', 'Sudah lewat jatuh tempo.'],
            ['Tidak Aktif', 'Rencananya dimatikan atau asetnya tidak aktif.'],
          ]}
        />
        <P>
          Sistem mengirim peringatan jatuh tempo sekali sehari ke penanggung jawab yang ditetapkan pada
          rencananya.
        </P>
      </Bagian>
    </>
  );
}
