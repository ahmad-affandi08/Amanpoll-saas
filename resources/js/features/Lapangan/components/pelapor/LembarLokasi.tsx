import { Check, Search } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { IlustrasiMomen } from '@/features/Lapangan/components/IlustrasiMomen';
import { IsianTiket, MasukanTiket } from '@/features/Lapangan/components/IsianTiket';
import { LembarBawah } from '@/features/Lapangan/components/LembarBawah';
import type { LokasiPelapor } from '@/features/Lapangan/types';

interface PropsLembarLokasi {
  buka: boolean;
  onBukaBerubah: (buka: boolean) => void;
  pilihan: LokasiPelapor[];
  terpilihId: string | null;
  onPilih: (lokasi: LokasiPelapor) => void;
}

/** Lembar bawah "Ganti lokasi": hanya lokasi di lingkup pelapor, dengan pencarian. */
export function LembarLokasi({ buka, onBukaBerubah, pilihan, terpilihId, onPilih }: PropsLembarLokasi) {
  const [cari, setCari] = useState('');
  const kata = cari.trim().toLowerCase();
  const tersaring = kata ? pilihan.filter((satu) => satu.Label.toLowerCase().includes(kata)) : pilihan;

  return (
    <LembarBawah
      buka={buka}
      onBukaBerubah={onBukaBerubah}
      judul="Pilih lokasi"
      deskripsi="Gedung, lantai, atau ruangan tempat kerusakan."
    >
      <IsianTiket label="Cari lokasi" ikon={Search}>
        <MasukanTiket
          value={cari}
          onChange={(event) => setCari(event.target.value)}
          placeholder="Contoh: Lt. 12, gudang"
          autoComplete="off"
          enterKeyHint="search"
        />
      </IsianTiket>
      {tersaring.length === 0 ? (
        <IlustrasiMomen
          ringkas
          ikon="round_pushpin"
          judul={pilihan.length === 0 ? 'Belum ada lokasi untukmu' : 'Lokasi tidak ditemukan'}
          teks={
            pilihan.length === 0
              ? 'Minta admin gedung menetapkan lokasi kerjamu.'
              : 'Coba kata lain, mis. nama gedung atau lantai.'
          }
        />
      ) : (
        <ul className="-mx-1 max-h-[46dvh] overflow-y-auto">
          {tersaring.map((satu) => {
            const terpilih = satu.Id === terpilihId;
            return (
              <li key={satu.Id}>
                <button
                  type="button"
                  onClick={() => onPilih(satu)}
                  aria-pressed={terpilih}
                  className={cn(
                    'flex min-h-12 w-full items-center gap-3 rounded-xl px-3 text-left text-[15px] font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lapangan-biru-500',
                    terpilih
                      ? 'bg-lapangan-biru-50 text-lapangan-navy-800'
                      : 'text-lapangan-teks hover:bg-lapangan-latar',
                  )}
                >
                  <span className="min-w-0 flex-1 truncate">{satu.Label}</span>
                  {terpilih && <Check aria-hidden className="size-5 shrink-0 text-lapangan-biru-600" />}
                </button>
              </li>
            );
          })}
        </ul>
      )}
    </LembarBawah>
  );
}
