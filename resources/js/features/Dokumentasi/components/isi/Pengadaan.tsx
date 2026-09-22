import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Bagian,
  Butir,
  Catatan,
  Daftar,
  Jalur,
  Langkah,
  P,
  SubJudul,
  Tabel,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'penyedia', judul: 'Penyedia dan kontrak' },
  { id: 'anggaran', judul: 'Anggaran' },
  { id: 'alur', judul: 'Alur pengadaan' },
  { id: 'penerimaan', judul: 'Penerimaan dan tagihan' },
];

export function Pengadaan() {
  return (
    <>
      <Bagian id="penyedia" judul="Penyedia dan kontrak">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Penyedia']} />
        </P>
        <P>
          Penyedia adalah rekanan: pemasok barang, penyedia jasa perbaikan, atau lembaga kalibrasi. Satu
          penyedia boleh masuk beberapa kategori sekaligus.
        </P>
        <SubJudul>Kontrak</SubJudul>
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Kontrak']} />. Mengikat penyedia pada jangka waktu dan nilai
          tertentu. Sistem mengirim peringatan menjelang kontrak berakhir, sekali sehari, sehingga
          perpanjangan tidak terlewat.
        </P>
      </Bagian>

      <Bagian id="anggaran" judul="Anggaran">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Perencanaan & Pengadaan', 'Anggaran']} />
        </P>
        <P>
          Anggaran dibagi ke pos-pos anggaran, dan belanja dibebankan ke pos. Ini opsional: lewati saja bila
          belum diperlukan, pengadaan tetap berjalan.
        </P>
        <Catatan>
          Bila dipakai, buat anggaran dan posnya sebelum usulan masuk. Usulan yang terlanjur dibuat tanpa pos
          harus disunting ulang satu per satu.
        </Catatan>
      </Bagian>

      <Bagian id="alur" judul="Alur pengadaan">
        <P>
          Rantainya panjang karena tiap tahap menjawab pertanyaan berbeda. Tahap yang tidak relevan boleh
          dilewati.
        </P>
        <Langkah
          daftar={[
            {
              judul: 'Usulan Aset',
              isi: <P>Siapa butuh apa, dan kenapa. Ini tahap paling awal, biasanya dari unit pengguna.</P>,
            },
            {
              judul: 'Rencana Pengadaan',
              isi: <P>Usulan yang disetujui dikumpulkan menjadi rencana belanja satu periode.</P>,
            },
            {
              judul: 'Permintaan Pembelian',
              isi: <P>Permintaan resmi ke bagian pengadaan untuk mulai membeli.</P>,
            },
            {
              judul: 'Permintaan Penawaran',
              isi: (
                <P>
                  Meminta harga ke beberapa penyedia. Penawaran yang masuk dibandingkan di sini sebelum salah
                  satunya dipilih.
                </P>
              ),
            },
            {
              judul: 'Pesanan Pembelian',
              isi: <P>Pesanan resmi ke penyedia terpilih, berdasarkan penawaran yang dimenangkan.</P>,
            },
            {
              judul: 'Penerimaan Pembelian',
              isi: <P>Barang datang dan diperiksa. Lihat bagian berikutnya.</P>,
            },
            {
              judul: 'Tagihan Penyedia',
              isi: <P>Faktur dicocokkan dengan pesanan dan penerimaan sebelum dibayar.</P>,
            },
          ]}
        />
        <Tabel
          kepala={['Status pesanan', 'Artinya']}
          baris={[
            ['Draft', 'Belum diajukan.'],
            ['Menunggu Persetujuan', 'Sedang dalam alur persetujuan.'],
            ['Disetujui', 'Boleh dikirim ke penyedia.'],
            ['Ditolak', 'Tidak dilanjutkan.'],
            ['Dikirim', 'Sudah diteruskan ke penyedia.'],
            ['Diterima Sebagian', 'Sebagian barang sudah datang.'],
            ['Diterima Penuh', 'Seluruh barang sudah datang.'],
          ]}
        />
      </Bagian>

      <Bagian id="penerimaan" judul="Penerimaan dan tagihan">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Pengadaan', 'Penerimaan Pembelian']} />
        </P>
        <P>
          Inilah tahap yang menyambungkan pengadaan ke dua modul lain, dan karena itu yang paling penting
          dicatat dengan benar:
        </P>
        <Daftar>
          <Butir>
            Barang habis pakai yang diterima <Tegas>menambah stok</Tegas> lewat mutasi yang dibuat sistem.
          </Butir>
          <Butir>
            Barang modal yang diterima menjadi <Tegas>aset baru</Tegas> di modul aset.
          </Butir>
        </Daftar>
        <Catatan>
          Penerimaan sebagian didukung: catat apa adanya saat barang datang bertahap. Pesanan otomatis
          berstatus Diterima Sebagian sampai seluruh barisnya terpenuhi.
        </Catatan>
      </Bagian>
    </>
  );
}
