import { CircleAlert, Smartphone, X } from 'lucide-react';
import { useEffect, useRef, useState, type ChangeEvent } from 'react';
import { toast } from 'sonner';
import { useSinkronisasiOffline } from '@/hooks/use-sinkronisasi-offline';
import { Ikon3D } from '@/components/shared/Ikon3D';
import type { AsetDitemukanTeknisi } from '@/features/Lapangan/types';
import { jamPendek } from '@/features/Lapangan/waktu';
import {
  useFotoAsetTertunda,
  useUrlBlob,
  type FotoAsetTertunda,
} from '@/features/Lapangan/components/teknisi/sesiKerja';

function FotoDiHp({ foto, onBuang }: { foto: FotoAsetTertunda; onBuang: () => void }) {
  const url = useUrlBlob(foto.Berkas);
  const ditolak = Boolean(foto.Ditolak);

  return (
    <figure className="relative h-28 overflow-hidden rounded-[14px] bg-lapangan-navy-800">
      {url && (
        <img
          src={url}
          alt={`Foto aset ${jamPendek(foto.DibuatPada)}`}
          className={ditolak ? 'size-full object-cover opacity-45' : 'size-full object-cover'}
        />
      )}
      <span
        className={
          ditolak
            ? 'absolute top-2 right-2 inline-flex h-[26px] items-center gap-1 rounded-full bg-lapangan-merah-50 px-2 text-[11.5px] font-bold text-lapangan-merah-700'
            : 'absolute top-2 right-2 inline-flex h-[26px] items-center gap-1 rounded-full bg-white/90 px-2 text-[11.5px] font-bold text-lapangan-navy-800'
        }
      >
        {ditolak ? (
          <CircleAlert aria-hidden className="size-[13px]" />
        ) : (
          <Smartphone aria-hidden className="size-[13px]" />
        )}
        {ditolak ? 'Ditolak' : 'Di HP'}
      </span>
      <figcaption className="absolute bottom-2 left-2 rounded-lg bg-lapangan-navy-900/75 px-2 py-0.5 text-xs font-bold text-white tabular-nums">
        {jamPendek(foto.DibuatPada)}
      </figcaption>
      <button
        type="button"
        onClick={onBuang}
        aria-label="Buang foto ini dari HP"
        className="absolute top-1 left-1 flex size-11 items-center justify-center text-white focus-visible:outline-none"
      >
        <span className="flex size-7 items-center justify-center rounded-full bg-lapangan-navy-900/70">
          <X aria-hidden className="size-4" />
        </span>
      </button>
    </figure>
  );
}

/**
 * Tambah foto aset dari HP (PRD 8.4 "Foto Aset"): kamera belakang, dikecilkan di peramban,
 * disimpan di HP lebih dulu, lalu dikirim ke galeri aset begitu ada sinyal. Foto yang ditolak
 * server (hak teknisi sudah berakhir, galeri penuh) tetap tampil dengan alasannya sampai dibuang.
 * Teknisi hanya menambah; menghapus dan memilih foto utama tetap di dasbor.
 */
export function BagianFotoAset({
  aset,
}: {
  aset: Pick<AsetDitemukanTeknisi, 'Id' | 'Nama' | 'BolehTambahFoto'>;
}) {
  const { daring } = useSinkronisasiOffline();
  const { foto, tambah, buang, unggahSemua } = useFotoAsetTertunda(aset.Id);
  const masukan = useRef<HTMLInputElement | null>(null);
  const [memproses, setMemproses] = useState(false);
  const menunggu = foto.filter((satu) => !satu.Ditolak).length;
  const ditolak = foto.filter((satu) => satu.Ditolak);

  // Foto yang diambil tanpa sinyal dikirim begitu layar ini dibuka lagi dalam keadaan online.
  useEffect(() => {
    if (daring && menunggu > 0) void unggahSemua();
  }, [daring, menunggu]);

  if (!aset.BolehTambahFoto && foto.length === 0) return null;

  const ambil = async (event: ChangeEvent<HTMLInputElement>) => {
    const berkas = event.target.files?.[0];
    event.target.value = '';
    if (!berkas) return;
    setMemproses(true);
    try {
      const hasil = await tambah(aset.Id, aset.Nama, berkas);
      if (hasil === 'terkirim') toast.success('Foto aset terkirim.');
      else if (hasil === 'di-hp') toast('Foto tersimpan di HP dan dikirim otomatis saat ada sinyal.');
      else toast.error(hasil);
    } finally {
      setMemproses(false);
    }
  };

  return (
    <section aria-label="Foto aset" className="rounded-[18px] bg-lapangan-latar p-3.5">
      <div className="flex items-baseline justify-between gap-2">
        <h3 className="text-[15px] font-bold">Foto aset</h3>
        {menunggu > 0 && (
          <span className="text-[13px] font-semibold text-lapangan-teks-3">
            {menunggu} menunggu {daring ? 'dikirim' : 'sinyal'}
          </span>
        )}
      </div>
      {ditolak.length > 0 && (
        <ul className="mt-2 flex flex-col gap-1.5">
          {[...new Set(ditolak.map((satu) => satu.Ditolak))].map((pesan) => (
            <li
              key={pesan}
              role="alert"
              className="rounded-xl bg-lapangan-merah-50 px-3 py-2 text-[13px] font-semibold text-lapangan-merah-700"
            >
              Foto tidak terkirim: {pesan}
            </li>
          ))}
        </ul>
      )}
      <div className="mt-2.5 grid grid-cols-3 gap-2">
        {foto.map((satu) => (
          <FotoDiHp key={satu.Kunci} foto={satu} onBuang={() => void buang(satu.Kunci)} />
        ))}
        {aset.BolehTambahFoto && (
          <button
            type="button"
            disabled={memproses}
            onClick={() => masukan.current?.click()}
            className="flex h-28 flex-col items-center justify-center gap-1 rounded-[14px] border-2 border-dashed border-lapangan-teks-3/35 bg-white text-[13px] font-bold text-lapangan-navy-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500 disabled:opacity-60"
          >
            <Ikon3D nama="camera_with_flash" ukuran={40} />
            {memproses ? 'Menyimpan…' : 'Tambah foto'}
          </button>
        )}
      </div>
      <input
        ref={masukan}
        type="file"
        accept="image/*"
        capture="environment"
        className="sr-only"
        tabIndex={-1}
        aria-label={`Ambil foto ${aset.Nama}`}
        onChange={(event) => void ambil(event)}
      />
    </section>
  );
}
