import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { BarisHalaman } from '@/features/Pemasaran/types';

export function TabelHalaman({ halaman }: { halaman: BarisHalaman[] }) {
  if (halaman.length === 0) {
    return <p className="text-sm text-muted-foreground">Belum ada kunjungan pada rentang ini.</p>;
  }

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Landing page</TableHead>
          <TableHead className="text-right">Pengunjung</TableHead>
          <TableHead className="text-right">Lead</TableHead>
          <TableHead className="text-right">Konversi</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {halaman.map((satu) => (
          <TableRow key={satu.Landing}>
            <TableCell className="break-all font-mono text-xs">{satu.Landing}</TableCell>
            <TableCell className="text-right font-mono">{satu.Pengunjung}</TableCell>
            <TableCell className="text-right font-mono">{satu.Lead}</TableCell>
            <TableCell className="text-right font-mono">{satu.Konversi}%</TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
