import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export type StrategiJadwal = 'Interval' | 'PenggunaanMeter' | 'Kombinasi';

export const LABEL_STRATEGI: Record<StrategiJadwal, string> = {
  Interval: 'Kalender',
  PenggunaanMeter: 'Pemakaian meter',
  Kombinasi: 'Kalender atau meter, mana lebih dulu',
};

export interface NilaiStrategiJadwal {
  StrategiJadwal: StrategiJadwal;
  IntervalNilai: number | null;
  IntervalSatuan: string | null;
  AmbangMeter: number | string | null;
}

export const memakaiKalender = (strategi: string) => strategi !== 'PenggunaanMeter';
export const memakaiMeter = (strategi: string) => strategi === 'PenggunaanMeter' || strategi === 'Kombinasi';

/** Ringkasan pemicu rencana untuk kartu dan detail, mis. "Setiap 3 Bulan atau 500 satuan meter". */
export function ringkasPemicu(rencana: {
  StrategiJadwal: string;
  IntervalNilai: number | null;
  IntervalSatuan: string | null;
  AmbangMeter?: number | string | null;
}): string {
  const kalender =
    memakaiKalender(rencana.StrategiJadwal) && rencana.IntervalNilai
      ? `Setiap ${rencana.IntervalNilai} ${rencana.IntervalSatuan}`
      : null;
  const meter =
    memakaiMeter(rencana.StrategiJadwal) && rencana.AmbangMeter
      ? `setiap ${Number(rencana.AmbangMeter).toLocaleString('id-ID')} satuan meter`
      : null;

  if (kalender && meter) {
    return `${kalender} atau ${meter}`;
  }

  return kalender ?? (meter ? meter.charAt(0).toUpperCase() + meter.slice(1) : '-');
}

/**
 * Pemicu rencana preventif: kalender, pemakaian meter, atau keduanya. Ambang meter dihitung
 * dari pembacaan saat servis terakhir pada meter kumulatif aset.
 */
export function BidangStrategiJadwal({
  nilai,
  ubah,
  galat,
}: {
  nilai: NilaiStrategiJadwal;
  ubah: (perubahan: Partial<NilaiStrategiJadwal>) => void;
  galat: Partial<Record<keyof NilaiStrategiJadwal, string>>;
}) {
  return (
    <div className="space-y-3">
      <div className="space-y-1.5">
        <Label nama="StrategiJadwal" htmlFor="StrategiJadwal">
          Pemicu
        </Label>
        <Select
          value={nilai.StrategiJadwal}
          onValueChange={(isi) =>
            ubah({
              StrategiJadwal: isi as StrategiJadwal,
              ...(memakaiKalender(isi) && !nilai.IntervalNilai
                ? { IntervalNilai: 30, IntervalSatuan: 'Hari' }
                : {}),
            })
          }
        >
          <SelectTrigger id="StrategiJadwal" className="cursor-pointer">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {(Object.keys(LABEL_STRATEGI) as StrategiJadwal[]).map((kunci) => (
              <SelectItem key={kunci} value={kunci}>
                {LABEL_STRATEGI[kunci]}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {memakaiKalender(nilai.StrategiJadwal) && (
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5">
            <Label nama="IntervalNilai" htmlFor="IntervalNilai">
              Interval
            </Label>
            <Input
              id="IntervalNilai"
              type="number"
              min={1}
              value={nilai.IntervalNilai ?? ''}
              onChange={(e) => ubah({ IntervalNilai: e.target.value === '' ? null : Number(e.target.value) })}
              required
            />
            {galat.IntervalNilai && <p className="text-xs text-destructive">{galat.IntervalNilai}</p>}
          </div>
          <div className="space-y-1.5">
            <Label nama="IntervalSatuan" htmlFor="IntervalSatuan">
              Satuan Waktu
            </Label>
            <Select
              value={nilai.IntervalSatuan ?? 'Hari'}
              onValueChange={(isi) => ubah({ IntervalSatuan: isi })}
            >
              <SelectTrigger id="IntervalSatuan" className="cursor-pointer">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Hari">Hari</SelectItem>
                <SelectItem value="Minggu">Minggu</SelectItem>
                <SelectItem value="Bulan">Bulan</SelectItem>
                <SelectItem value="Tahun">Tahun</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      )}

      {memakaiMeter(nilai.StrategiJadwal) && (
        <div className="space-y-1.5">
          <Label nama="AmbangMeter" htmlFor="AmbangMeter">
            Setiap pemakaian meter sebesar
          </Label>
          <Input
            id="AmbangMeter"
            type="number"
            min={0}
            step="any"
            placeholder="Mis. 500 (jam) atau 5000 (km)"
            value={nilai.AmbangMeter ?? ''}
            onChange={(e) => ubah({ AmbangMeter: e.target.value === '' ? null : e.target.value })}
            required
          />
          <p className="text-xs text-muted-foreground">
            Dihitung dari pembacaan saat servis terakhir, memakai satuan meter kumulatif aset.
          </p>
          {galat.AmbangMeter && <p className="text-xs text-destructive">{galat.AmbangMeter}</p>}
        </div>
      )}
    </div>
  );
}
