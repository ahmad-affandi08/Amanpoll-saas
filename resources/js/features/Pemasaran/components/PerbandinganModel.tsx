import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { formatAngka } from '@/lib/angka';
import type { Attribution } from '@/features/Pemasaran/types';

/** Model lain ditampilkan berdampingan supaya pilihan setelan dapat ditimbang, bukan ditebak. */
export function PerbandinganModel({ attribution }: { attribution: Attribution }) {
  const channel = Array.from(
    new Set(attribution.Perbandingan.flatMap((satu) => Object.keys(satu.PerChannel))),
  ).sort();

  if (channel.length === 0) {
    return null;
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Revenue menurut tiap model</CardTitle>
        <p className="text-xs text-muted-foreground">
          Angka yang dipakai kartu di atas adalah kolom {attribution.Label.toLowerCase()}.
        </p>
      </CardHeader>
      <CardContent className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b text-left text-xs text-muted-foreground">
              <th className="py-2 pr-4 font-medium">Channel</th>
              {attribution.Perbandingan.map((satu) => (
                <th key={satu.Model} className="py-2 pr-4 text-right font-medium">
                  {satu.Label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {channel.map((nama) => (
              <tr key={nama} className="border-b last:border-0">
                <td className="py-2 pr-4">{nama}</td>
                {attribution.Perbandingan.map((satu) => (
                  <td
                    key={satu.Model}
                    className={`py-2 pr-4 text-right font-mono ${satu.Model === attribution.Model ? 'font-semibold text-foreground' : 'text-muted-foreground'}`}
                  >
                    {formatAngka(satu.PerChannel[nama] ?? 0)}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </CardContent>
    </Card>
  );
}
