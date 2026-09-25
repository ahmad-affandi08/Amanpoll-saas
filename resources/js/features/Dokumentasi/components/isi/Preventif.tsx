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
        <P>
          Untuk butir Ya/Tidak, tentukan jawaban mana yang menandakan masalah. Pertanyaan positif seperti
          &quot;Level oli cukup?&quot; bermasalah bila dijawab <Ui>Tidak</Ui>; pertanyaan negatif seperti
          &quot;Ada kebocoran?&quot; bermasalah bila dijawab <Ui>Ya</Ui>. Skor daftar periksa dihitung dari
          pilihan ini.
        </P>
      </Bagian>

      <Bagian id="rencana" judul="Rencana preventif">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Preventif & Inspeksi', 'Rencana Preventif']} />
        </P>
        <P>
          Rencana menetapkan pekerjaan berulang atas sebuah aset: kapan jatuh tempo, memakai daftar periksa
          yang mana, dan siapa yang mengerjakan. Pemicunya salah satu dari tiga:
        </P>
        <Daftar>
          <Butir>
            <Tegas>Kalender</Tegas>: tiap sekian hari, minggu, bulan, atau tahun.
          </Butir>
          <Butir>
            <Tegas>Pemakaian meter</Tegas>: tiap sekian jam atau kilometer sejak servis terakhir, dibaca dari
            meter kumulatif aset. Aset harus punya meter sebelum bisa didaftarkan.
          </Butir>
          <Butir>
            <Tegas>Kalender atau meter</Tegas>: mana yang lebih dulu tercapai, misalnya servis kompresor tiap
            3 bulan atau 2.000 jam. Setelah servis, keduanya dihitung ulang dari hari itu.
          </Butir>
        </Daftar>
        <Catatan>
          Pemicu meter hanya secepat pembacaannya. Catat pembacaan meter secara rutin agar servis tidak
          terlambat terdeteksi.
        </Catatan>
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
