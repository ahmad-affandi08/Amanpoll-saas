import type { ButirDaftarIsi } from '@/features/Dokumentasi/components/KerangkaDokumentasi';
import {
  Awas,
  Bagian,
  Blok,
  Butir,
  Catatan,
  Daftar,
  Jalur,
  Kode,
  Langkah,
  P,
  Tabel,
  TautanDoc,
  Tegas,
  Ui,
} from '@/features/Dokumentasi/components/Prosa';
import { ruteDokumentasi } from '@/features/Dokumentasi/api';

export const daftarIsi: ButirDaftarIsi[] = [
  { id: 'kapan', judul: 'Kapan dipakai' },
  { id: 'langkah', judul: 'Langkah penyiapan' },
  { id: 'lingkup', judul: 'Peran dan lingkup' },
  { id: 'hasil', judul: 'Hasil yang diharapkan' },
  { id: 'data-lama', judul: 'Mengisi data lama' },
];

export function UnitPengelola() {
  return (
    <>
      <Bagian id="kapan" judul="Kapan dipakai">
        <P>
          Banyak organisasi punya lebih dari satu bagian yang memelihara aset. Rumah sakit punya IPSRS untuk
          alat dan gedung serta IT untuk komputer dan jaringan; pabrik punya Engineering dan IT; gedung
          perkantoran punya tim ME dan IT. Masing-masing punya teknisi, gudang suku cadang, dan antrian
          perbaikannya sendiri, dan tidak perlu melihat pekerjaan bagian lain.
        </P>
        <P>
          Amanpoll memisahkannya lewat <Tegas>unit pengelola</Tegas>: unit organisasi biasa yang diberi tanda
          bahwa ia memelihara aset. Setiap aset tetap punya <Ui>Unit Organisasi</Ui> (milik atau dipakai
          siapa, mis. ICU) dan kini bisa punya <Ui>Unit Pengelola</Ui> (siapa yang memeliharanya, mis. IT).
          Printer di ruang ICU tetap tercatat milik ICU, tetapi keluhan tentangnya masuk ke antrian IT.
        </P>
        <Catatan>
          Organisasi yang hanya punya satu bagian pemeliharaan tidak perlu melakukan apa pun di halaman ini.
          Selama belum ada unit yang ditandai, seluruh layar bekerja persis seperti sebelumnya.
        </Catatan>
      </Bagian>

      <Bagian id="langkah" judul="Langkah penyiapan">
        <P>Contoh berikut memakai rumah sakit dengan dua bagian: IPSRS dan IT.</P>
        <Langkah
          daftar={[
            {
              judul: 'Tandai unit pengelola',
              isi: (
                <>
                  <p>
                    Buka <Jalur ruas={['Sistem & Konfigurasi', 'Struktur & Platform', 'Unit Organisasi']} />,
                    ubah unit IPSRS, lalu nyalakan <Ui>Mengelola aset (unit pengelola pemeliharaan)</Ui>.
                    Ulangi untuk unit IT. Bila unitnya belum ada, buat dulu seperti unit lainnya.
                  </p>
                  <p>
                    Hanya unit bertanda ini yang muncul sebagai pilihan <Ui>Unit Pengelola</Ui> di formulir
                    lain. Tanda tidak bisa dicabut selama unit itu masih dipakai aset, kategori keluhan,
                    gudang, atau tiket yang belum selesai.
                  </p>
                </>
              ),
            },
            {
              judul: 'Isi unit pengelola aset',
              isi: (
                <>
                  <p>
                    Di <Jalur ruas={['Operasional & Aset', 'Manajemen Aset', 'Daftar Aset']} />, isi{' '}
                    <Ui>Unit Pengelola</Ui> pada formulir aset. Untuk banyak aset sekaligus, centang barisnya
                    lalu pakai <Ui>Atur Unit Pengelola</Ui>, atau isi kolom unit pengelola di berkas impor.
                  </p>
                  <p>
                    Satu aset dikelola paling banyak satu bagian. Aset yang dibiarkan kosong tetap terlihat
                    oleh pengguna yang lingkupnya mencakup unit organisasi atau ruangannya.
                  </p>
                </>
              ),
            },
            {
              judul: 'Isi unit pengelola kategori keluhan',
              isi: (
                <>
                  <p>
                    Di <Jalur ruas={['Operasional & Aset', 'Pemeliharaan', 'Kategori Keluhan']} />, isi{' '}
                    <Ui>Unit Pengelola</Ui> pada kategori induk, mis. <Kode>Komputer & Jaringan</Kode> → IT
                    dan <Kode>Listrik & Gedung</Kode> → IPSRS. Sub-kategori tanpa isian ikut induknya.
                  </p>
                  <p>
                    Kategori inilah yang menentukan antrian mana yang menerima keluhan. Bila kategori tidak
                    menunjuk unit mana pun, keluhan mengikuti unit pengelola asetnya.
                  </p>
                </>
              ),
            },
            {
              judul: 'Isi unit pengelola gudang',
              isi: (
                <p>
                  Di <Jalur ruas={['Persediaan & Rekanan', 'Persediaan', 'Gudang']} />, tetapkan gudang milik
                  masing-masing bagian, mis. <Kode>Gudang IPSRS</Kode> dan <Kode>Gudang IT</Kode>. Stok,
                  mutasi, dan reservasi mengikuti gudangnya. Daftar suku cadang tetap satu katalog bersama.
                </p>
              ),
            },
            {
              judul: 'Tetapkan peran dengan lingkup',
              isi: (
                <p>
                  Di <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Pengguna']} />, buka{' '}
                  <Ui>Kelola Peran</Ui> pada teknisi dan koordinator IT, pilih perannya, lalu pilih unit{' '}
                  <Tegas>IT</Tegas> di isian unit. Lakukan hal yang sama untuk IPSRS. Penjelasan lengkapnya
                  ada di bagian berikut.
                </p>
              ),
            },
          ]}
        />
      </Bagian>

      <Bagian id="lingkup" judul="Peran dan lingkup">
        <P>
          Lingkup menentukan data mana yang terlihat seseorang. Pengguna yang perannya ditetapkan dengan unit
          IT melihat aset, keluhan, perintah kerja, dan gudang yang unit pengelolanya IT, di ruangan mana pun,
          ditambah data yang unit organisasi atau ruangannya memang IT.
        </P>
        <Tabel
          kepala={['Pengguna', 'Peran', 'Unit pada penetapan']}
          baris={[
            ['Koordinator IPSRS', 'Koordinator Pemeliharaan', 'IPSRS'],
            ['Teknisi IPSRS', 'Teknisi', 'IPSRS'],
            ['Koordinator IT', 'Koordinator Pemeliharaan', 'IT'],
            ['Teknisi IT', 'Teknisi', 'IT'],
            ['Perawat ICU', 'Pelapor', 'Ruang ICU (isian ruangan)'],
          ]}
        />
        <Awas>
          <Tegas>Jebakan terbesar: satu peran tanpa lingkup membuka seluruh organisasi.</Tegas> Bila Teknisi
          IT diberi peran tambahan tanpa memilih unit maupun ruangan, ia melihat seluruh data, termasuk
          antrian IPSRS, walau peran lainnya berlingkup IT. Formulir <Ui>Kelola Peran</Ui> memperingatkan hal
          ini dan meminta konfirmasi sebelum menyimpan. Periksa hasilnya di kartu <Ui>Lingkup data</Ui> pada
          halaman detail pengguna.
        </Awas>
        <P>
          Kartu <Ui>Lingkup data</Ui> menampilkan &ldquo;Seluruh organisasi&rdquo; beserta peran penyebabnya,
          atau daftar unit dan ruangan yang tercakup beserta peran yang memberikannya. Untuk mempersempit,
          cabut penetapan yang tanpa lingkup lalu tetapkan ulang dengan unit. Cara kerja peran dan izin
          dijelaskan di <TautanDoc ke={ruteDokumentasi.halaman('pengguna')}>Pengguna, Peran & Izin</TautanDoc>
          .
        </P>
      </Bagian>

      <Bagian id="hasil" judul="Hasil yang diharapkan">
        <P>Setelah langkah di atas, dengan contoh printer yang rusak di ruang ICU:</P>
        <Daftar>
          <Butir>
            Perawat ICU melaporkan &ldquo;printer tidak mencetak&rdquo; dengan kategori Komputer & Jaringan.
            Keluhannya masuk antrian <Tegas>IT</Tegas>, baik dari dasbor, Mode Lapangan, maupun saat dikirim
            tanpa sinyal.
          </Butir>
          <Butir>Koordinator IT melihat keluhan itu; koordinator IPSRS tidak.</Butir>
          <Butir>
            Perintah kerja yang dibuat dari keluhan itu ikut tercatat milik IT. Pilihan teknisi hanya berisi
            teknisi yang dapat melihat tiket itu, dan server menolak penugasan kepada teknisi IPSRS.
          </Butir>
          <Butir>Stok di Gudang IT tidak terlihat oleh petugas IPSRS, dan sebaliknya.</Butir>
          <Butir>
            Laporan dan dasbor bisa disaring menurut <Ui>Unit Pengelola</Ui>, di samping unit organisasi dan
            lokasi.
          </Butir>
          <Butir>
            Perawat ICU yang berlingkup ruangan tetap melihat seluruh keluhan di ruang ICU, milik bagian mana
            pun.
          </Butir>
        </Daftar>
        <Catatan>
          Keluhan yang salah alamat bisa dipindahkan oleh pemegang izin kelola keluhan lewat{' '}
          <Ui>Alihkan Keluhan</Ui>, dengan alasan yang tercatat di riwayat dan log audit.
        </Catatan>
      </Bagian>

      <Bagian id="data-lama" judul="Mengisi data lama">
        <P>
          Keluhan, perintah kerja, dan rencana yang tercatat sebelum unit pengelola disiapkan masih kosong
          unit pengelolanya. Sistem tidak mengisinya diam-diam. Admin sistem yang memegang server menjalankan
          perintah berikut <Tegas>setelah</Tegas> aset, kategori keluhan, dan gudang diberi unit pengelola.
        </P>
        <P>Lihat dulu apa yang akan diisi, tanpa mengubah apa pun:</P>
        <Blok>{'php artisan pemeliharaan:isi-unit-pengelola --pratinjau'}</Blok>
        <P>Jalankan untuk satu organisasi saja, lalu jawab pertanyaan konfirmasinya:</P>
        <Blok>{'php artisan pemeliharaan:isi-unit-pengelola --organisasi=<Id organisasi>'}</Blok>
        <Daftar>
          <Butir>Keluhan diisi dari kategorinya (naik ke induk), lalu dari asetnya.</Butir>
          <Butir>
            Perintah kerja diisi dari keluhan asalnya, lalu aset utamanya, lalu rencana preventif atau
            kalibrasi asalnya.
          </Butir>
          <Butir>
            Rencana kalibrasi diisi dari asetnya. Rencana pemeliharaan hanya diisi bila seluruh asetnya
            dikelola bagian yang sama.
          </Butir>
          <Butir>
            Nilai yang sudah terisi tidak pernah ditimpa, jadi perintah ini aman dijalankan berulang. Baris
            yang tidak bisa diturunkan dibiarkan kosong dan disebutkan jumlahnya.
          </Butir>
          <Butir>
            Setiap pengisian tercatat di{' '}
            <Jalur ruas={['Sistem & Konfigurasi', 'Administrasi', 'Log Audit']} /> dengan aksi{' '}
            <Kode>UnitPengelola.DataLamaDiisi</Kode> beserta daftar barisnya.
          </Butir>
        </Daftar>
        <Catatan>
          Tambahkan <Kode>--paksa</Kode> untuk melewati pertanyaan konfirmasi, mis. saat dijalankan dari
          skrip.
        </Catatan>
      </Bagian>
    </>
  );
}
