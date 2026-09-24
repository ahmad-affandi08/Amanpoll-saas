import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatAngka } from '@/lib/angka';
import type { BarisCac, CacTakTerpecah } from '@/features/Pemasaran/types';

/** CAC hanya pasti untuk kampanye berchannel tunggal; yang tidak pasti disebut, bukan dibagi rata. */
export function CacChannel({ baris, takTerpecah }: { baris: BarisCac[]; takTerpecah: CacTakTerpecah }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>CAC per Channel</CardTitle>
      </CardHeader>
      <CardContent>
        {baris.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Belum ada biaya kampanye tercatat pada rentang ini. Catat belanjanya di halaman kampanye agar CAC
            punya pembilang.
          </p>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Channel</TableHead>
                <TableHead className="text-right">Biaya</TableHead>
                <TableHead className="text-right">Pelanggan baru</TableHead>
                <TableHead className="text-right">CAC</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {baris.map((satu) => (
                <TableRow key={satu.Channel}>
                  <TableCell className="text-foreground">{satu.Channel}</TableCell>
                  <TableCell className="text-right font-mono">{formatAngka(satu.Biaya)}</TableCell>
                  <TableCell className="text-right font-mono">{satu.Pelanggan}</TableCell>
                  <TableCell className="text-right">
                    {satu.Cac === null ? (
                      <span className="text-xs text-muted-foreground">{satu.Alasan}</span>
                    ) : (
                      <span className="font-mono text-foreground">{formatAngka(satu.Cac)}</span>
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}

        {takTerpecah.Kampanye.length > 0 ? (
          <p className="mt-3 rounded-md border border-dashed p-3 text-xs text-muted-foreground">
            {takTerpecah.Kampanye.length} kampanye berjalan di lebih dari satu channel (
            {takTerpecah.Kampanye.join(', ')}), sehingga {formatAngka(takTerpecah.Biaya)} belanja dan{' '}
            {takTerpecah.Pelanggan} pelanggan barunya tidak dapat dipecah per channel tanpa menebak. Angkanya
            sengaja tidak diselipkan ke tabel di atas.
          </p>
        ) : null}
      </CardContent>
    </Card>
  );
}
