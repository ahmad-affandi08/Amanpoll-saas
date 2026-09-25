import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Awas,
  Bagian,
  Butir,
  Catatan,
  Daftar,
  Jalur,
  Kode,
  P,
  SubJudul,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';
import { Tangkapan } from '@/features/Dokumentasi/components/Tangkapan';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'laporan', judul: 'Laporan dan dasbor' },
  { id: 'kepatuhan', judul: 'Kepatuhan' },
  { id: 'integrasi', judul: 'Integrasi dan kunci API' },
  { id: 'email-whatsapp', judul: 'Email dan WhatsApp sendiri' },
  { id: 'offline', judul: 'Mode Lapangan' },
  { id: 'audit', judul: 'Log audit' },
];

export function Lanjutan() {
  return (
    <>
      <Bagian id="laporan" judul="Laporan dan dasbor">
        <P>
          <Jalur ruas={['Platform', 'Laporan & Dasbor']} />
        </P>
        <Daftar>
          <Butir>
            <Ui>Laporan Tersimpan</Ui> menyimpan susunan penyaring yang sering dipakai, sehingga tidak perlu
            disusun ulang tiap bulan.
          </Butir>
          <Butir>
            <Ui>Dasbor Kustom</Ui> menyusun kartu-kartu angka menjadi satu layar pantau.
          </Butir>
        </Daftar>
        <Tangkapan
          gambar="lanjutan/laporan"
          alt="Halaman Laporan Tersimpan dengan tombol Laporan baru dan penyaring"
          langkah={[
            {
              penanda: 'baru',
              isi: (
                <>
                  Klik <Ui>Laporan baru</Ui> dan pilih KPI yang ingin dilihat.
                </>
              ),
            },
            {
              penanda: 'rentang',
              isi: <>Pilih rentang tanggal: 7, 30, atau 90 hari, atau tanggal sendiri.</>,
            },
            {
              penanda: 'unit',
              isi: <>Persempit menurut unit, lokasi, atau unit pengelola, lalu simpan susunannya.</>,
            },
          ]}
        />
        <P>Keduanya bagian dari paket langganan tertentu; menunya tidak muncul bila paketnya tidak memuat.</P>
      </Bagian>

      <Bagian id="kepatuhan" judul="Kepatuhan">
        <P>
          <Jalur ruas={['Persediaan & Rekanan', 'Kepatuhan']} />
        </P>
        <P>
          Mencatat standar yang harus dipenuhi aset beserta persyaratannya, dan sertifikasi yang
          membuktikannya. Sistem memperingatkan menjelang sertifikat kedaluwarsa.
        </P>
      </Bagian>

      <Bagian id="integrasi" judul="Integrasi dan kunci API">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Kunci API']} />
        </P>
        <Awas>
          Token kunci API hanya ditampilkan <Tegas>satu kali</Tegas>, tepat setelah dibuat. Sistem menyimpan
          sidik jarinya, bukan tokennya, jadi token yang hilang tidak dapat dilihat lagi — hanya dapat dicabut
          lalu dibuat baru.
        </Awas>
        <Daftar>
          <Butir>
            Batasi <Ui>Cakupan</Ui> seperlunya; kunci untuk membaca tidak perlu izin menulis.
          </Butir>
          <Butir>
            <Ui>Alamat IP Diizinkan</Ui> mempersempit dari mana kunci boleh dipakai.
          </Butir>
          <Butir>Cabut kunci yang sudah tidak dipakai; kunci menganggur adalah pintu yang tak dijaga.</Butir>
        </Daftar>
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Integrasi']} /> mengatur webhook ke
          sistem luar. Pengiriman yang gagal dicoba ulang sendiri secara berkala.
        </P>
      </Bagian>

      <Bagian id="email-whatsapp" judul="Email dan WhatsApp sendiri">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Email & WhatsApp']} />
        </P>
        <P>
          Notifikasi ke staf (perintah kerja ditugaskan, keluhan baru, SLA, persetujuan) secara bawaan dikirim
          Amanpoll. Email tampil dengan nama pengirim <Ui>nama organisasi via Amanpoll</Ui> dan balasannya
          diarahkan ke email di profil organisasi. WhatsApp lewat nomor Amanpoll memakai kuota bulanan paket.
        </P>
        <Tangkapan
          gambar="lanjutan/email-whatsapp"
          alt="Halaman Email & WhatsApp dengan ringkasan pengirim, kuota WhatsApp, dan daftar penyedia email"
          langkah={[
            {
              penanda: 'ringkasan',
              isi: (
                <>
                  Kartu <Ui>Yang dipakai sekarang</Ui> menunjukkan dari mana email dan WhatsApp notifikasi
                  dikirim saat ini.
                </>
              ),
            },
            {
              penanda: 'kuota',
              isi: (
                <>
                  Bilah ini menunjukkan kuota WhatsApp bawaan yang terpakai bulan ini. Saat habis, WhatsApp
                  berhenti sampai bulan depan; notifikasi in-app tetap berjalan.
                </>
              ),
            },
            {
              penanda: 'atur',
              isi: (
                <>
                  Untuk mengirim dari email atau nomor sendiri, klik <Ui>Atur</Ui> pada penyedianya, isi
                  kredensial, aktifkan, lalu coba <Ui>Kirim email uji ke saya</Ui> atau{' '}
                  <Ui>Kirim WhatsApp uji ke saya</Ui>.
                </>
              ),
            },
          ]}
        />
        <Daftar>
          <Butir>
            Memakai email dan nomor sendiri butuh paket yang memuat{' '}
            <Ui>Email &amp; WhatsApp Milik Sendiri</Ui>. Halamannya tetap terbuka di paket lain untuk melihat
            kuota.
          </Butir>
          <Butir>
            Bila email organisasi gagal, email tetap dikirim lewat email Amanpoll supaya kabar penting tidak
            hilang. Bila nomor WhatsApp organisasi gagal, pesannya <Tegas>tidak</Tegas> dialihkan ke nomor
            Amanpoll, karena itu memakan kuota paket.
          </Butir>
          <Butir>
            Pemegang izin <Kode>Integrasi.Kelola</Kode> mendapat kabar sekali saat penyedianya mulai gagal dan
            saat kuota WhatsApp bawaan habis. Galat terakhirnya tampil di bagian atas halaman ini.
          </Butir>
        </Daftar>
        <Catatan>
          WhatsApp resmi (Meta) butuh template notifikasi yang sudah disetujui Meta, berisi dua parameter:
          judul dan isi. Penyedia tidak resmi bisa langsung dipakai, tetapi nomornya bisa diblokir WhatsApp;
          pakai nomor khusus, bukan nomor utama rumah sakit.
        </Catatan>
      </Bagian>

      <Bagian id="offline" judul="Mode Lapangan">
        <P>
          Tampilan aplikasi HP untuk teknisi dan pelapor. Pengguna yang seluruh perannya bertanda{' '}
          <Ui>Tampilan Lapangan</Ui> langsung dibawa ke sini setelah masuk dan tidak melihat dasbor ini.
          Tandanya diatur per peran di{' '}
          <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Peran & Izin']} />: <Ui>Teknisi</Ui> untuk
          yang mengerjakan tiket kerja, <Ui>Pelapor</Ui> untuk staf lokasi yang melaporkan kerusakan.
        </P>
        <P>
          Pengguna yang juga memegang peran lain tetap masuk dasbor dan dapat beralih lewat menu akun,{' '}
          <Ui>Buka Mode Lapangan</Ui>. Pilihannya diingat di perangkat itu.
        </P>
        <P>
          Mode Lapangan tetap berjalan di area tanpa sinyal — ruang mesin, basement, lapangan. Pekerjaan yang
          dicatat disimpan di perangkat dan dikirim saat jaringan kembali ada.
        </P>
        <SubJudul>Layar teknisi</SubJudul>
        <Tangkapan
          gambar="lapangan/teknisi-tugas"
          alt="Layar Tiket Saya di ponsel teknisi"
          langkah={[
            {
              penanda: 'tab',
              isi: (
                <>
                  Buka <Ui>Tugas</Ui>. Tab <Ui>Hari ini</Ui> memuat tiket yang harus dikerjakan;{' '}
                  <Ui>Terlambat</Ui> yang lewat target.
                </>
              ),
            },
            {
              penanda: 'kartu',
              isi: <>Ketuk kartu tiket untuk mulai, mencatat pekerjaan, dan menyelesaikannya.</>,
            },
            {
              penanda: 'pindai',
              isi: (
                <>
                  Ketuk <Ui>Pindai</Ui> untuk membuka aset langsung dari stiker QR-nya.
                </>
              ),
            },
          ]}
        />
        <SubJudul>Layar pelapor</SubJudul>
        <Tangkapan
          gambar="lapangan/pelapor-beranda"
          alt="Beranda pelapor di ponsel"
          langkah={[
            {
              penanda: 'lapor',
              isi: (
                <>
                  Ketuk <Ui>Laporkan Kerusakan</Ui>.
                </>
              ),
            },
            { penanda: 'jenis', isi: <>Atau langsung pilih jenis masalahnya.</> },
            {
              penanda: 'konfirmasi',
              isi: (
                <>
                  Setelah teknisi selesai, ketuk <Ui>Konfirmasi</Ui> bila perbaikannya sudah beres.
                </>
              ),
            },
          ]}
        />
        <Tangkapan
          gambar="lapangan/pelapor-lapor"
          alt="Langkah pertama Lapor kerusakan: memilih alat"
          langkah={[
            { penanda: 'pindai', isi: <>Cara paling cepat: pindai stiker QR di badan alat.</> },
            { penanda: 'lokasi', isi: <>Atau pilih lokasi dulu, lalu pilih alatnya dari daftar.</> },
            { penanda: 'tanpa', isi: <>Tidak tahu alatnya? Laporkan lokasinya saja.</> },
          ]}
        />
        <Catatan>
          Pengiriman ulang aman diulang: satu catatan yang terkirim dua kali tidak menjadi dua perintah kerja.
          Karena itu, bila ragu apakah data sudah terkirim, mengirim ulang lebih baik daripada mengetik ulang.
        </Catatan>
      </Bagian>

      <Bagian id="audit" judul="Log audit">
        <P>
          <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Log Audit']} />
        </P>
        <P>
          Mencatat siapa mengubah apa dan kapan, lengkap dengan nilai sebelum dan sesudah. Isinya tidak dapat
          disunting maupun dihapus oleh siapa pun, termasuk administrator — itulah yang membuatnya berguna
          saat ada yang perlu ditelusuri.
        </P>
      </Bagian>
    </>
  );
}
