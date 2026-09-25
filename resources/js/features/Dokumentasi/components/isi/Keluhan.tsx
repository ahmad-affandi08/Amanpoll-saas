import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Awas,
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
  { id: 'sla', judul: 'Tingkat layanan' },
  { id: 'kategori', judul: 'Kategori keluhan' },
  { id: 'alur', judul: 'Jalannya keluhan' },
  { id: 'eskalasi', judul: 'Eskalasi' },
];

export function Keluhan() {
  return (
    <>
      <Bagian id="sla" judul="Tingkat layanan">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Pemeliharaan', 'Tingkat Layanan (SLA)']} />
        </P>
        <P>
          Tingkat layanan menetapkan dua tenggat: berapa lama sampai keluhan harus direspons, dan berapa lama
          sampai harus selesai. Keduanya dihitung dalam jam kerja, melewati hari libur yang terdaftar.
        </P>
        <Awas>
          Buat tingkat layanan <Tegas>sebelum</Tegas> kategori keluhan. Kategori merujuknya, dan keluhan tanpa
          tingkat layanan tidak punya tenggat sehingga tidak pernah muncul di laporan keterlambatan.
        </Awas>
      </Bagian>

      <Bagian id="kategori" judul="Kategori keluhan">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Pemeliharaan', 'Kategori Keluhan']} />
        </P>
        <Daftar>
          <Butir>
            <Ui>Tingkat Layanan</Ui> menentukan tenggat keluhan di kategori ini.
          </Butir>
          <Butir>
            <Ui>Prioritas Bawaan</Ui> mengisi prioritas keluhan baru, masih dapat diubah pelapor.
          </Butir>
          <Butir>
            <Ui>Aset Wajib</Ui> memaksa pelapor memilih aset. Nyalakan untuk kategori kerusakan alat; matikan
            untuk keluhan umum seperti kebersihan.
          </Butir>
          <Butir>
            <Ui>Peran Penanggung Jawab</Ui> menentukan siapa yang menerima pemberitahuan saat keluhan masuk.
          </Butir>
        </Daftar>
      </Bagian>

      <Bagian id="alur" judul="Jalannya keluhan">
        <P>
          <Jalur ruas={['Operasional & Aset', 'Pemeliharaan', 'Keluhan']} />
        </P>
        <SubJudul>Mencatat keluhan baru</SubJudul>
        <Tangkapan
          gambar="keluhan/daftar"
          alt="Halaman daftar keluhan dengan menu Keluhan, tombol Buat Keluhan, dan saringan status"
          langkah={[
            {
              penanda: 'menu',
              isi: (
                <>
                  Buka menu <Ui>Pemeliharaan</Ui> › <Ui>Keluhan</Ui>.
                </>
              ),
            },
            {
              penanda: 'buat',
              isi: (
                <>
                  Klik <Ui>Buat Keluhan</Ui> di kanan atas.
                </>
              ),
            },
            {
              penanda: 'saring',
              isi: <>Untuk mencari keluhan lama, saring menurut status, prioritas, atau kategori.</>,
            },
          ]}
        />
        <Tangkapan
          gambar="keluhan/formulir"
          alt="Formulir Buat Keluhan"
          langkah={[
            {
              penanda: 'kategori',
              isi: (
                <>
                  Pilih <Ui>Kategori</Ui>. Tenggat dan prioritas bawaan mengikuti kategori.
                </>
              ),
            },
            {
              penanda: 'lokasi',
              isi: (
                <>
                  Pilih <Ui>Lokasi</Ui> kejadian. <Ui>Aset</Ui> boleh dikosongkan bila alatnya belum jelas.
                </>
              ),
            },
            {
              penanda: 'judul',
              isi: (
                <>
                  Tulis <Ui>Judul</Ui> singkat dan uraian masalahnya. Foto bukti boleh dilampirkan.
                </>
              ),
            },
            {
              penanda: 'kirim',
              isi: (
                <>
                  Klik <Ui>Kirim Keluhan</Ui>. Penanggung jawab kategori menerima pemberitahuan.
                </>
              ),
            },
          ]}
        />
        <SubJudul>Status keluhan</SubJudul>
        <Tabel
          kepala={['Status', 'Artinya']}
          baris={[
            ['Baru', 'Baru masuk, belum disentuh.'],
            ['Ditinjau', 'Sedang diperiksa kebenarannya.'],
            ['Diterima', 'Diakui sebagai pekerjaan yang harus dikerjakan.'],
            ['Diproses', 'Perintah kerjanya sudah berjalan.'],
            ['Selesai', 'Pekerjaannya rampung.'],
            ['Ditutup', 'Sudah dikonfirmasi pelapor dan tidak dapat diubah lagi.'],
            ['Ditolak', 'Bukan kerusakan, atau di luar cakupan.'],
            ['Dibatalkan', 'Dicabut sebelum dikerjakan.'],
          ]}
        />
        <SubJudul>Menindaklanjuti keluhan</SubJudul>
        <Tangkapan
          gambar="keluhan/detail"
          alt="Halaman detail keluhan dengan tombol Ubah Status, Alihkan, dan Ubah Prioritas"
          langkah={[
            { isi: <>Buka keluhan dari daftar dengan mengeklik nomornya.</> },
            {
              penanda: 'status',
              isi: (
                <>
                  Klik <Ui>Ubah Status</Ui> untuk meninjau, menerima, atau menolak keluhan.
                </>
              ),
            },
            {
              penanda: 'alihkan',
              isi: (
                <>
                  Klik <Ui>Alihkan</Ui> bila keluhan salah alamat dan perlu ditangani orang lain.
                </>
              ),
            },
            {
              penanda: 'prioritas',
              isi: (
                <>
                  Klik <Ui>Ubah Prioritas</Ui> bila tingkat urgensinya perlu disesuaikan.
                </>
              ),
            },
          ]}
        />
        <SubJudul>Dari keluhan ke pekerjaan</SubJudul>
        <P>
          Keluhan yang sudah diterima diubah menjadi perintah kerja. Sejak saat itu pekerjaannya diikuti di
          perintah kerja, sementara keluhan menyimpan sisi pelapornya.
        </P>
      </Bagian>

      <Bagian id="eskalasi" judul="Eskalasi">
        <P>
          Keluhan yang melewati tenggat respons atau tenggat selesai dieskalasi otomatis oleh sistem, yang
          memeriksanya tiap jam. Eskalasi mengirim pemberitahuan ke peran atau orang yang ditetapkan pada
          tingkat layanan.
        </P>
        <Catatan>
          Eskalasi bergantung pada penjadwal yang berjalan di server. Bila tidak ada keluhan yang pernah
          tereskalasi padahal ada yang jelas terlambat, yang perlu diperiksa adalah penjadwalnya, bukan
          setelan SLA-nya.
        </Catatan>
      </Bagian>
    </>
  );
}
