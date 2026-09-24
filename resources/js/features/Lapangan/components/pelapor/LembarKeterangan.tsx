import { router } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { useState } from 'react';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { ruteLapangan } from '@/features/Lapangan/api';
import { AreaTiket, IsianTiket } from '@/features/Lapangan/components/IsianTiket';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import { TombolLapangan } from '@/features/Lapangan/components/Tombol';
import { FotoPilihan } from '@/features/Lapangan/components/pelapor/FotoPilihan';
import { PilihanChip } from '@/features/Lapangan/components/pelapor/PilihanChip';

/** Kabar cepat yang paling sering disampaikan pelapor sesudah melapor. */
const KABAR_CEPAT = ['Makin parah', 'Ada jadwal pemakaian', 'Saya tidak di tempat', 'Sudah normal lagi'];

const MAKS_FOTO = 2;

interface PropsLembarKeterangan {
  buka: boolean;
  onBukaBerubah: (buka: boolean) => void;
  keluhanId: string;
  /** Nama depan teknisi, bila sudah ada. */
  namaTeknisi: string | null;
}

/**
 * Tambah keterangan (DESIGN.md 36.7 layar 11): kabar cepat, catatan, dan foto. Memakai
 * endpoint Kolaborasi yang sudah ada — komentar dan unggah berkas pada keluhan terbuka
 * bagi pelapornya lewat `KeluhanPolicy::view`.
 */
export function LembarKeterangan({ buka, onBukaBerubah, keluhanId, namaTeknisi }: PropsLembarKeterangan) {
  const { daring } = useSinkronisasiOffline();
  const [kabar, setKabar] = useState<string | null>(null);
  const [isi, setIsi] = useState('');
  const [foto, setFoto] = useState<File[]>([]);
  const [mengirim, setMengirim] = useState(false);
  const [galat, setGalat] = useState<string | null>(null);

  const teks = [kabar, isi.trim()].filter(Boolean).join('. ');
  const bisaKirim = daring && (teks !== '' || foto.length > 0) && !mengirim;

  const ubahBuka = (terbuka: boolean) => {
    if (terbuka) {
      setKabar(null);
      setIsi('');
      setFoto([]);
      setGalat(null);
    }
    onBukaBerubah(terbuka);
  };

  /** Unggah foto satu per satu: kunjungan Inertia berikutnya membatalkan yang masih berjalan. */
  const unggahFoto = (sisa: File[]) => {
    const [pertama, ...lainnya] = sisa;
    if (!pertama) {
      setMengirim(false);
      onBukaBerubah(false);
      return;
    }

    router.post(
      ruteLapangan.pelapor.berkas,
      { Berkas: pertama, JenisEntitas: 'Keluhan', EntitasId: keluhanId, Kategori: 'Keterangan' },
      {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => unggahFoto(lainnya),
        onError: (errors) => {
          setGalat(Object.values(errors)[0] ?? 'Foto gagal dikirim.');
          setMengirim(false);
        },
        onNetworkError: () => {
          setGalat('Sinyal terputus. Foto belum terkirim, coba lagi.');
          setMengirim(false);
          return false;
        },
      },
    );
  };

  const kirim = () => {
    if (!bisaKirim) return;
    setMengirim(true);
    setGalat(null);

    if (teks === '') {
      unggahFoto(foto);
      return;
    }

    router.post(
      ruteLapangan.pelapor.komentar,
      { JenisEntitas: 'Keluhan', EntitasId: keluhanId, Isi: teks },
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => unggahFoto(foto),
        onError: (errors) => {
          setGalat(Object.values(errors)[0] ?? 'Keterangan gagal dikirim.');
          setMengirim(false);
        },
        onNetworkError: () => {
          setGalat('Sinyal terputus. Keterangan belum terkirim, coba lagi.');
          setMengirim(false);
          return false;
        },
      },
    );
  };

  return (
    <LembarBawah
      buka={buka}
      onBukaBerubah={ubahBuka}
      judul="Tambah keterangan"
      deskripsi={
        namaTeknisi
          ? `${namaTeknisi} dan tim teknik akan membaca pesan ini.`
          : 'Tim teknik akan membaca pesan ini.'
      }
      kaki={
        <TombolLapangan penuh onClick={kirim} disabled={!bisaKirim}>
          <Send aria-hidden />
          {mengirim ? 'Mengirim…' : 'Kirim'}
        </TombolLapangan>
      }
    >
      <PilihanChip
        label="Kabar cepat"
        terpilih={kabar}
        onPilih={(pilihan) => setKabar((sekarang) => (sekarang === pilihan ? null : pilihan))}
        item={KABAR_CEPAT.map((satu) => ({ kunci: satu, label: satu }))}
      />
      <IsianTiket label="Keterangan" galat={galat}>
        <AreaTiket
          value={isi}
          onChange={(event) => setIsi(event.target.value)}
          rows={3}
          maxLength={4000}
          placeholder="Contoh: ruangan dipakai jam 13.00, mohon selesai sebelum itu."
        />
      </IsianTiket>
      <div className="flex items-center gap-2.5">
        <FotoPilihan foto={foto} onUbah={setFoto} maks={MAKS_FOTO} labelTambah="Foto" ukuran={72} />
        <span className="text-[13px] leading-[1.4] text-lapangan-teks-3">
          Tambahkan foto bila kondisinya berubah. Tidak wajib.
        </span>
      </div>
      {!daring && (
        <p role="status" className="text-[13px] font-semibold text-lapangan-kuning-700">
          Butuh sinyal untuk mengirim keterangan. Tulis dulu, kirim saat sinyal kembali.
        </p>
      )}
    </LembarBawah>
  );
}
