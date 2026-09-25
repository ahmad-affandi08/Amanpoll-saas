import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import { Bagian, Butir, Catatan, Daftar, Jalur, P, Tegas, Ui } from '@/features/Dokumentasi/components/Prosa';
import { Tangkapan } from '@/features/Dokumentasi/components/Tangkapan';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'urutan', judul: 'Urutan pembuatan' },
  { id: 'kategori', judul: 'Kategori aset' },
  { id: 'merek', judul: 'Merek' },
  { id: 'model', judul: 'Model aset' },
];

export function MasterAset() {
  return (
    <>
      <Bagian id="urutan" judul="Urutan pembuatan">
        <P>
          Ketiganya dibuat berurutan: kategori dulu, lalu merek, lalu model. Model merujuk keduanya, dan aset
          merujuk model.
        </P>
        <P>
          Ketiganya tidak wajib diisi lengkap sejak awal. Aset dapat dibuat hanya dengan kategori; merek dan
          model boleh menyusul.
        </P>
      </Bagian>

      <Bagian id="kategori" judul="Kategori aset">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Manajemen Aset', 'Kategori Aset']} />
        </P>
        <P>
          Kategori adalah keputusan paling berpengaruh di modul aset, karena ia menentukan perilaku seluruh
          aset di bawahnya.
        </P>
        <Daftar>
          <Butir>
            <Ui>Memerlukan Kalibrasi</Ui> menandai bahwa aset kategori ini masuk hitungan kepatuhan kalibrasi.
          </Butir>
          <Butir>
            <Ui>Memerlukan Pemeliharaan</Ui> menandainya sebagai sasaran rencana pemeliharaan preventif.
          </Butir>
          <Butir>
            <Ui>Umur Manfaat</Ui>, <Ui>Metode Penyusutan</Ui>, dan <Ui>Nilai Residu</Ui> menjadi nilai bawaan
            penyusutan aset di bawahnya.
          </Butir>
          <Butir>
            <Ui>Induk</Ui> membuat kategori bertingkat, mis. Alat Ukur › Timbangan.
          </Butir>
        </Daftar>
        <Tangkapan
          gambar="master-aset/kategori-formulir"
          alt="Formulir Tambah Kategori Aset"
          langkah={[
            {
              isi: (
                <>
                  Klik <Ui>Tambah Kategori</Ui>.
                </>
              ),
            },
            {
              penanda: 'induk',
              isi: (
                <>
                  Pilih <Ui>Kategori Induk</Ui> bila kategori ini bagian dari kategori lain.
                </>
              ),
            },
            {
              penanda: 'umur',
              isi: (
                <>
                  Isi <Ui>Umur Manfaat</Ui> dan metode penyusutan bawaan bila dipakai.
                </>
              ),
            },
            {
              penanda: 'kalibrasi',
              isi: (
                <>
                  Centang <Ui>Memerlukan Kalibrasi</Ui> untuk alat ukur.
                </>
              ),
            },
            {
              penanda: 'pemeliharaan',
              isi: (
                <>
                  Centang <Ui>Memerlukan Pemeliharaan</Ui> untuk aset yang dirawat berkala, lalu simpan.
                </>
              ),
            },
          ]}
        />
        <Catatan>
          Mengubah <Tegas>Memerlukan Kalibrasi</Tegas> pada kategori yang sudah punya aset akan mengubah
          cakupan laporan kepatuhan. Tetapkan sedini mungkin.
        </Catatan>
      </Bagian>

      <Bagian id="merek" judul="Merek">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Manajemen Aset', 'Merek']} />
        </P>
        <P>
          Daftar produsen. Isinya sederhana: nama, negara asal, dan situs web. Merek yang masih dipakai model
          aset tidak dapat dihapus.
        </P>
      </Bagian>

      <Bagian id="model" judul="Model aset">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Manajemen Aset', 'Model Aset']} />
        </P>
        <P>
          Model adalah tipe barang tertentu dari sebuah merek, mis. &ldquo;Mettler Toledo XPR226&rdquo;. Ia
          menyimpan spesifikasi teknis dan interval pemeliharaan serta kalibrasi bawaan, sehingga aset baru
          dari model yang sama tidak perlu diisi ulang satu per satu.
        </P>
        <Tangkapan
          gambar="master-aset/model-formulir"
          alt="Formulir Tambah Model Aset"
          langkah={[
            {
              isi: (
                <>
                  Klik <Ui>Tambah Model</Ui>, lalu pilih kategori dan isi namanya.
                </>
              ),
            },
            {
              penanda: 'merek',
              isi: (
                <>
                  Pilih <Ui>Merek</Ui>; buat mereknya dulu bila belum ada.
                </>
              ),
            },
            {
              penanda: 'interval',
              isi: <>Isi interval pemeliharaan dan kalibrasi bawaan; aset baru dari model ini mewarisinya.</>,
            },
          ]}
        />
        <P>
          Menyiapkan model di depan menghemat banyak pekerjaan bila organisasi memiliki puluhan unit barang
          yang sama.
        </P>
      </Bagian>
    </>
  );
}
