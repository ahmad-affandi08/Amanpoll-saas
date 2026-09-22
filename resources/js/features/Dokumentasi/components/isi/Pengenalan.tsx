import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import { ruteDokumentasi } from '@/features/Dokumentasi/api';
import {
  Bagian,
  Butir,
  Catatan,
  Daftar,
  P,
  Tabel,
  TautanDoc,
  Tegas,
} from '@/features/Dokumentasi/components/Prosa';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'untuk-siapa', judul: 'Untuk siapa' },
  { id: 'modul', judul: 'Modul' },
  { id: 'urutan', judul: 'Kenapa urutannya penting' },
  { id: 'membaca', judul: 'Cara membaca' },
];

export function Pengenalan() {
  return (
    <>
      <Bagian id="untuk-siapa" judul="Untuk siapa">
        <P>
          Amanpoll mencatat aset fisik beserta seluruh yang terjadi padanya: kerusakan, perbaikan,
          pemeliharaan berkala, kalibrasi, suku cadang yang terpakai, dan pembelian penggantinya.
        </P>
        <P>
          Dokumen ini ditujukan kepada orang yang memasang Amanpoll untuk organisasinya, dari akun yang masih
          kosong sampai sistem siap dipakai teknisi sehari-hari. Ia tidak membahas cara memasang Amanpoll di
          server; itu urusan tim teknis.
        </P>
      </Bagian>

      <Bagian id="modul" judul="Modul">
        <Tabel
          kepala={['Modul', 'Mengurus']}
          baris={[
            ['Aset', 'Daftar aset, kategori, merek, model, mutasi, serah terima, penghapusan.'],
            ['Pemeliharaan', 'Keluhan, tingkat layanan, perintah kerja, kode kegagalan.'],
            ['Preventif & Inspeksi', 'Rencana pemeliharaan berkala, inspeksi, daftar periksa.'],
            ['Kalibrasi', 'Jenis, rencana, pelaksanaan, dan sertifikat kalibrasi.'],
            ['Persediaan', 'Gudang, suku cadang, saldo stok, mutasi stok, reservasi.'],
            ['Pengadaan', 'Anggaran, usulan, permintaan pembelian, pesanan, penerimaan, tagihan.'],
            ['Penyedia & Kontrak', 'Data rekanan dan kontrak yang mengikatnya.'],
            ['Kepatuhan', 'Standar, persyaratan, dan sertifikasi aset.'],
            ['Persetujuan', 'Alur persetujuan yang dipakai lintas modul.'],
            ['Pelaporan', 'Laporan tersimpan dan dasbor kustom.'],
          ]}
        />
        <Catatan>
          Sebagian modul hanya muncul bila paket langganan memuatnya. Menu yang tidak ada di sidebar bukan
          berarti belum diatur — bisa jadi memang di luar paket.
        </Catatan>
      </Bagian>

      <Bagian id="urutan" judul="Kenapa urutannya penting">
        <P>
          Modul-modul di atas saling bergantung. Aset butuh lokasi dan kategori sebelum bisa dibuat; keluhan
          butuh tingkat layanan sebelum tenggatnya bisa dihitung; mutasi stok butuh pola nomor dokumen sebelum
          nomornya bisa terbit.
        </P>
        <P>
          Karena itu urutan di sidebar kiri dokumen ini <Tegas>adalah urutan pengerjaan</Tegas>. Mengikutinya
          dari atas ke bawah berarti tidak pernah menemui langkah yang bergantung pada sesuatu yang belum
          dibuat.
        </P>
      </Bagian>

      <Bagian id="membaca" judul="Cara membaca">
        <Daftar>
          <Butir>Nama menu, tombol, dan kolom ditulis persis seperti yang tampil di layar.</Butir>
          <Butir>Jalur menu ditulis berurutan dari grup sidebar sampai halamannya.</Butir>
          <Butir>
            Kotak biru berisi keterangan tambahan; kotak kuning berisi hal yang bila dilewat akan menimbulkan
            masalah yang tidak langsung terlihat.
          </Butir>
        </Daftar>
        <P>
          Kalau ini pemasangan pertama, lanjut ke{' '}
          <TautanDoc ke={ruteDokumentasi.halaman('persiapan')}>Urutan Persiapan</TautanDoc>.
        </P>
      </Bagian>
    </>
  );
}
