import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Awas,
  Bagian,
  Butir,
  Catatan,
  Daftar,
  Jalur,
  P,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';
import { Tangkapan } from '@/features/Dokumentasi/components/Tangkapan';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'gudang', judul: 'Gudang dan lokasi rak' },
  { id: 'kategori', judul: 'Kategori suku cadang' },
  { id: 'suku-cadang', judul: 'Suku cadang' },
  { id: 'saldo', judul: 'Membaca saldo stok' },
];

export function Persediaan() {
  return (
    <>
      <Bagian id="gudang" judul="Gudang dan lokasi rak">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Persediaan', 'Gudang']} />
        </P>
        <P>
          Gudang adalah tempat penyimpanan. Di dalam tiap gudang dapat dibuat lokasi rak lewat tombol{' '}
          <Ui>Lokasi</Ui> pada barisnya — rak, lemari, bin. Lokasi rak boleh bertingkat.
        </P>
        <P>
          Membagi gudang ke lokasi rak tidak wajib, tetapi tanpa itu sistem hanya dapat menjawab &ldquo;ada di
          gudang mana&rdquo;, bukan &ldquo;ada di rak mana&rdquo;.
        </P>
      </Bagian>

      <Bagian id="kategori" judul="Kategori suku cadang">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Persediaan', 'Kategori Suku Cadang']} />
        </P>
        <P>
          Pengelompokan sederhana dan boleh bertingkat, mis. Kelistrikan › Sekring. Dipakai menyaring daftar
          suku cadang yang biasanya panjang.
        </P>
      </Bagian>

      <Bagian id="suku-cadang" judul="Suku cadang">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Persediaan', 'Suku Cadang']} />
        </P>
        <Tangkapan
          gambar="persediaan/suku-cadang"
          alt="Halaman daftar suku cadang dengan tombol Tambah Suku Cadang"
          langkah={[
            {
              penanda: 'menu',
              isi: (
                <>
                  Buka menu <Ui>Persediaan</Ui> › <Ui>Suku Cadang</Ui>.
                </>
              ),
            },
            {
              penanda: 'tambah',
              isi: (
                <>
                  Klik <Ui>Tambah Suku Cadang</Ui>, lalu isi nama, satuan dasar, dan stok minimum.
                </>
              ),
            },
          ]}
        />
        <Daftar>
          <Butir>
            <Ui>Satuan Dasar</Ui> wajib dan tidak boleh berubah setelah ada stok, karena seluruh saldo
            dihitung dalam satuan itu.
          </Butir>
          <Butir>
            <Ui>Stok Minimum</Ui> menjadi ambang peringatan. Baris yang saldonya di bawah atau sama dengan
            angka ini ditandai merah dan dihitung di spanduk peringatan halaman.
          </Butir>
          <Butir>
            <Ui>Titik Pesan Ulang</Ui> adalah ambang untuk mulai membeli, biasanya di atas stok minimum.
          </Butir>
          <Butir>
            <Ui>Memakai Batch</Ui> dan <Ui>Memakai Kadaluarsa</Ui> dinyalakan untuk barang yang harus dilacak
            per kelompok produksi, mis. pelumas dan bahan kimia.
          </Butir>
        </Daftar>
        <Awas>
          <Ui>Memakai Batch</Ui> sebaiknya ditetapkan sebelum ada stok. Menyalakannya belakangan membuat saldo
          lama tidak punya nomor batch, sehingga laporan per batch tidak lengkap.
        </Awas>
      </Bagian>

      <Bagian id="saldo" judul="Membaca saldo stok">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Persediaan', 'Stok Gudang']} />
        </P>
        <Tangkapan
          gambar="persediaan/stok"
          alt="Halaman stok gudang dengan kolom fisik, ditahan, dan tersedia bersih"
          langkah={[
            { penanda: 'gudang', isi: <>Pilih gudang untuk melihat saldo satu gudang saja.</> },
            {
              penanda: 'tersedia',
              isi: (
                <>
                  Baca kolom <Ui>Tersedia Bersih</Ui> untuk tahu berapa yang benar-benar boleh diambil.
                </>
              ),
            },
          ]}
        />
        <P>Ada tiga angka dan ketiganya berbeda:</P>
        <Daftar>
          <Butir>
            <Tegas>Fisik</Tegas> — jumlah yang benar-benar ada di rak.
          </Butir>
          <Butir>
            <Tegas>Ditahan</Tegas> — bagian dari fisik yang sudah direservasi pekerjaan lain.
          </Butir>
          <Butir>
            <Tegas>Tersedia Bersih</Tegas> — fisik dikurangi ditahan; inilah yang benar-benar boleh diambil.
          </Butir>
        </Daftar>
        <Catatan>
          Halaman ini hanya baca. Saldo tidak pernah diketik langsung — lihat halaman berikutnya.
        </Catatan>
      </Bagian>
    </>
  );
}
