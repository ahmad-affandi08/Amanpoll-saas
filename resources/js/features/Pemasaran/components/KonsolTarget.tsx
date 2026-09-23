import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { HUE_UTAMA } from '@/components/grafik/palet';
import { Button } from '@/components/ui/button';
import { InputUang } from '@/components/shared/InputUang';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TargetKampanye } from '@/features/Pemasaran/types';
import { formatUang } from '@/lib/uang';

/** Target dikirim utuh: metrik yang dikosongkan berarti dicabut. */
export function KonsolTarget({
  akar,
  target,
  metrik,
  metrikUang,
}: {
  akar: string;
  target: TargetKampanye[];
  metrik: string[];
  metrikUang: string[];
}) {
  const [nilai, setNilai] = useState<Record<string, string>>(
    Object.fromEntries(target.map((satu) => [satu.Metrik, String(satu.Nilai)])),
  );

  const realisasi = Object.fromEntries(target.map((satu) => [satu.Metrik, satu.Realisasi]));
  const tampil = (satu: string, angka: number) =>
    metrikUang.includes(satu) ? formatUang(angka) : angka.toLocaleString('id-ID');

  const simpan = (e: FormEvent) => {
    e.preventDefault();

    const Target = Object.entries(nilai)
      .filter(([, isi]) => isi !== '')
      .map(([Metrik, isi]) => ({ Metrik, Nilai: Number(isi) }));

    router.put(`${akar}/target`, { Target }, { preserveScroll: true });
  };

  return (
    <form onSubmit={simpan} className="space-y-4">
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {metrik.map((satu) => {
          const dicapai = realisasi[satu] ?? 0;
          const sasaran = Number(nilai[satu] ?? 0);
          const persen = sasaran > 0 ? Math.min((dicapai / sasaran) * 100, 100) : 0;

          return (
            <div key={satu} className="rounded-lg border p-3">
              <Label htmlFor={`target-${satu}`}>{satu}</Label>
              {metrikUang.includes(satu) ? (
                <InputUang
                  id={`target-${satu}`}
                  className="mt-1.5"
                  value={nilai[satu] ?? ''}
                  onChange={(isi) => setNilai((lama) => ({ ...lama, [satu]: isi }))}
                  placeholder="Tanpa target"
                />
              ) : (
                <Input
                  id={`target-${satu}`}
                  type="number"
                  min="0"
                  className="mt-1.5"
                  value={nilai[satu] ?? ''}
                  onChange={(e) => setNilai((lama) => ({ ...lama, [satu]: e.target.value }))}
                  placeholder="Tanpa target"
                />
              )}
              <div className="mt-2 h-2 rounded-sm bg-muted">
                <div className="h-2 rounded-sm" style={{ width: `${persen}%`, backgroundColor: HUE_UTAMA }} />
              </div>
              <p className="mt-1 text-xs text-muted-foreground">
                Realisasi {tampil(satu, dicapai)}
                {sasaran > 0 ? ` dari ${tampil(satu, sasaran)}` : ' (belum ada target)'}
              </p>
            </div>
          );
        })}
      </div>

      <p className="text-sm text-muted-foreground">
        Realisasi dibaca dari metrik harian yang dihitung pekerjaan
        <span className="font-mono"> pemasaran:hitung-metrik</span>, bukan dari tabel mentah.
      </p>

      <Button type="submit">Simpan target</Button>
    </form>
  );
}
