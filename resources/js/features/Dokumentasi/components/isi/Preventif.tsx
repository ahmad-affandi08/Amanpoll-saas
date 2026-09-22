import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import { Bagian, Butir, Catatan, Daftar, Jalur, P, Tegas, Ui } from '@/features/Dokumentasi/components/Prosa';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'daftar-periksa', judul: 'Daftar periksa' },
  { id: 'rencana', judul: 'Rencana preventif' },
  { id: 'inspeksi', judul: 'Inspeksi berkala' },
];

export function Preventif() {
  return (
    <>
      <Bagian id="daftar-periksa" judul="Daftar periksa">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Preventif & Inspeksi', 'Daftar Periksa']} />
        </P>
        <P>
          Buat templat daftar periksanya lebih dulu. Rencana preventif dan inspeksi merujuk templat ini, jadi
          tanpa templat keduanya hanya menghasilkan pekerjaan tanpa isi.
        </P>
        <P>
          Satu templat berisi butir-butir yang harus diperiksa, masing-masing dengan jenis jawaban:
          lulus/gagal, angka, atau teks. Butir bertipe angka yang punya batas bawah dan atas akan menandai
          sendiri bila hasilnya di luar rentang.
        </P>
      </Bagian>

      <Bagian id="rencana" judul="Rencana preventif">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Preventif & Inspeksi', 'Rencana Preventif']} />
        </P>
        <P>
          Rencana menetapkan pekerjaan berulang atas sebuah aset: tiap berapa lama, memakai daftar periksa
          yang mana, dan siapa yang mengerjakan.
        </P>
        <Daftar>
          <Butir>
            Sistem membuatkan perintah kerja sendiri saat jadwalnya tiba — tidak perlu dibuat manual.
          </Butir>
          <Butir>
            Aset yang kategorinya bertanda <Ui>Memerlukan Pemeliharaan</Ui> muncul di laporan sebagai belum
            tercakup bila belum punya rencana.
          </Butir>
          <Butir>
            Rencana yang dinonaktifkan berhenti menerbitkan pekerjaan baru, tetapi pekerjaan yang sudah terbit
            tetap berjalan.
          </Butir>
        </Daftar>
        <Catatan>
          Penjadwalan berjalan di server sekali sehari. Rencana yang baru dibuat hari ini tidak langsung
          menerbitkan perintah kerja pada detik itu juga.
        </Catatan>
      </Bagian>

      <Bagian id="inspeksi" judul="Inspeksi berkala">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Preventif & Inspeksi', 'Inspeksi Berkala']} />
        </P>
        <P>
          Inspeksi adalah pemeriksaan tanpa pekerjaan: memastikan keadaan, bukan memperbaiki. Hasilnya berupa
          daftar periksa yang terisi.
        </P>
        <P>
          Inspeksi yang menemukan masalah <Tegas>tidak</Tegas> memperbaikinya sendiri. Dari temuan itu dibuat
          keluhan atau perintah kerja tersendiri, sehingga perbaikannya punya tenggat dan pelaksananya
          sendiri.
        </P>
      </Bagian>
    </>
  );
}
