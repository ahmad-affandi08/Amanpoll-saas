import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { KpiGrowth } from '@/features/Pemasaran/types';

/** Rumus tiap KPI terbaca di layar, sehingga angkanya dapat ditelusuri tanpa membuka kode. */
export function TabelDefinisi({ kpi }: { kpi: KpiGrowth[] }) {
  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>KPI</TableHead>
          <TableHead>Rumus</TableHead>
          <TableHead>Sumber</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {kpi.map((satu) => (
          <TableRow key={satu.Kunci}>
            <TableCell>
              <div className="font-medium text-foreground">{satu.Nama}</div>
              <div className="font-mono text-xs text-muted-foreground">{satu.Kunci}</div>
            </TableCell>
            <TableCell className="text-sm text-muted-foreground">
              {satu.Formula}
              {satu.BelumTersedia ? (
                <span className="mt-1 block text-destructive">{satu.BelumTersedia}</span>
              ) : null}
            </TableCell>
            <TableCell className="font-mono text-xs text-muted-foreground">{satu.Sumber}</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
