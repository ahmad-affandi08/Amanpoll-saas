import { Building2, Globe2, MapPin } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { CakupanLingkup, LingkupEfektifPengguna } from '@/features/Pengguna/types';

/** Satu baris cakupan: nama unit/ruangan dan peran yang memberikannya. */
function BarisCakupan({
  jenis,
  cakupan,
  unitPengelola = false,
}: {
  jenis: 'unit' | 'lokasi';
  cakupan: CakupanLingkup;
  unitPengelola?: boolean;
}) {
  const Ikon = jenis === 'unit' ? Building2 : MapPin;

  return (
    <li className="flex items-start gap-3 py-2.5">
      <Ikon aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
      <div className="min-w-0 flex-1">
        <div className="flex flex-wrap items-center gap-2">
          <span className="text-sm font-medium text-foreground">{cakupan.Nama}</span>
          <span className="text-xs text-muted-foreground">{jenis === 'unit' ? 'Unit' : 'Ruangan'}</span>
          {unitPengelola && <Badge variant="info">Unit pengelola</Badge>}
        </div>
        <p className="mt-0.5 text-xs text-muted-foreground">Dari peran {cakupan.Peran.join(', ')}</p>
      </div>
    </li>
  );
}

/**
 * Lingkup data yang berlaku bagi pengguna (PRD 8.21).
 *
 * Putusannya datang dari server (LingkupAkses); kartu ini hanya menjelaskan sumbernya supaya admin
 * tahu penetapan mana yang harus diubah bila hasilnya terlalu lebar atau terlalu sempit.
 */
export function KartuLingkupEfektif({ lingkup }: { lingkup: LingkupEfektifPengguna }) {
  return (
    <section
      aria-labelledby="judul-lingkup"
      className="mb-6 rounded-[9px] border border-border bg-card p-4 sm:p-5"
    >
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h2 id="judul-lingkup" className="text-sm font-semibold text-foreground">
          Lingkup data
        </h2>
        {lingkup.TanpaPeran ? (
          <Badge variant="netral">Belum ada peran</Badge>
        ) : lingkup.SeluruhOrganisasi ? (
          <Badge variant="perhatian">
            <Globe2 aria-hidden="true" />
            Seluruh organisasi
          </Badge>
        ) : (
          <Badge variant="sukses">Terbatas</Badge>
        )}
      </div>

      {lingkup.TanpaPeran ? (
        <p className="mt-2 text-sm text-muted-foreground">
          Pengguna ini belum memegang peran yang berlaku, jadi belum dapat membuka data apa pun.
        </p>
      ) : lingkup.SeluruhOrganisasi ? (
        <p className="mt-2 text-sm leading-6 text-grafit-700">
          Melihat data di seluruh unit dan ruangan, karena peran{' '}
          <span className="font-medium text-foreground">{lingkup.PeranTanpaLingkup.join(', ')}</span>{' '}
          ditetapkan tanpa unit atau ruangan. Untuk membatasinya, cabut penetapan itu lalu tetapkan ulang
          dengan unit atau ruangan.
        </p>
      ) : (
        <>
          <p className="mt-2 text-sm leading-6 text-grafit-700">
            Hanya melihat data milik, di dalam, atau dikelola unit dan ruangan berikut, beserta sub-unit dan
            ruangan di bawahnya ({lingkup.JumlahUnit} unit, {lingkup.JumlahLokasi} ruangan).
          </p>
          <ul className="mt-2 divide-y divide-border">
            {lingkup.Unit.map((satu) => (
              <BarisCakupan
                key={`unit-${satu.Id}`}
                jenis="unit"
                cakupan={satu}
                unitPengelola={satu.MengelolaAset}
              />
            ))}
            {lingkup.Lokasi.map((satu) => (
              <BarisCakupan key={`lokasi-${satu.Id}`} jenis="lokasi" cakupan={satu} />
            ))}
          </ul>
        </>
      )}
    </section>
  );
}
