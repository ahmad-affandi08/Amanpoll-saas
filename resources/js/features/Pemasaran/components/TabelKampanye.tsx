import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatAngka } from '@/lib/angka';
import type { BarisKampanye } from '@/features/Pemasaran/types';

export function TabelKampanye({ kampanye }: { kampanye: BarisKampanye[] }) {
  if (kampanye.length === 0) {
    return (
      <p className="text-sm text-muted-foreground">
        Belum ada metrik kampanye pada rentang ini. Metrik dihitung pekerjaan harian
        <span className="font-mono"> pemasaran:hitung-metrik</span>.
      </p>
    );
  }

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Kampanye</TableHead>
          <TableHead className="text-right">Visitor</TableHead>
          <TableHead className="text-right">Lead</TableHead>
          <TableHead className="text-right">Trial</TableHead>
          <TableHead className="text-right">Bayar</TableHead>
          <TableHead className="text-right">Biaya</TableHead>
          <TableHead className="text-right">Revenue</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {kampanye.map((satu) => (
          <TableRow key={satu.KampanyeId}>
            <TableCell>
              <div className="font-medium text-foreground">{satu.Nama}</div>
              <div className="font-mono text-xs text-muted-foreground">{satu.Kode}</div>
            </TableCell>
            <TableCell className="text-right font-mono">{satu.Visitor}</TableCell>
            <TableCell className="text-right font-mono">{satu.Lead}</TableCell>
            <TableCell className="text-right font-mono">{satu.Trial}</TableCell>
            <TableCell className="text-right font-mono">{satu.Bayar}</TableCell>
            <TableCell className="text-right font-mono">{formatAngka(satu.Biaya)}</TableCell>
            <TableCell className="text-right font-mono">{formatAngka(satu.Revenue)}</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
