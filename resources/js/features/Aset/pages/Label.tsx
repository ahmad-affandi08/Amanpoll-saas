import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ruteAset } from '@/features/Aset/api';

interface LabelAset {
  Id: string;
  KodeAset: string;
  Nama: string;
  Kategori: string | null;
  Lokasi: string | null;
  NomorSeri: string | null;
  Svg: string | null;
}

interface Props {
  label: LabelAset[];
}

/**
 * Geometri lembar masih bawaan: 3 kolom di A4 dengan margin 10mm. Ukuran label
 * fisik yang dipakai di lapangan belum ditentukan, jadi angkanya sengaja
 * ditaruh di satu tempat agar mudah disetel nanti.
 */
const GAYA_CETAK = `
@page { size: A4; margin: 10mm; }
@media print {
  .tanpa-cetak { display: none !important; }
  body { background: #fff; }
  .lembar-label { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 4mm; }
  .satu-label { break-inside: avoid; page-break-inside: avoid; border-color: #d4d4d4; }
}
`;

export default function AsetLabel({ label }: Props) {
  const tanpaQr = label.filter((satu) => satu.Svg === null).length;

  return (
    <>
      <Head title={`Label Aset (${label.length})`}>
        <style>{GAYA_CETAK}</style>
      </Head>

      <div className="min-h-screen bg-background p-6 text-foreground">
        <div className="tanpa-cetak mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
          <div>
            <h1 className="text-[22px] leading-tight font-semibold tracking-[-0.01em]">Label Aset</h1>
            <p className="text-sm text-muted-foreground">
              {label.length} label siap cetak.
              {tanpaQr > 0 && ` ${tanpaQr} di antaranya belum punya kode QR, jadi dicetak tanpa QR.`}
            </p>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href={ruteAset.index}>Kembali ke daftar</Link>
            </Button>
            <Button type="button" onClick={() => window.print()}>
              Cetak
            </Button>
          </div>
        </div>

        <div className="lembar-label grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {label.map((satu) => (
            <figure
              key={satu.Id}
              className="satu-label flex items-center gap-3 rounded-sm border border-border p-3"
            >
              {satu.Svg ? (
                <div
                  className="size-[26mm] shrink-0 [&>svg]:h-full [&>svg]:w-full"
                  // QR dirender sebagai SVG di server; isinya hanya KodeQr milik aset ini.
                  dangerouslySetInnerHTML={{ __html: satu.Svg }}
                />
              ) : (
                <div className="flex size-[26mm] shrink-0 items-center justify-center rounded-xs border border-dashed border-border text-center text-[9px] text-muted-foreground">
                  Tanpa QR
                </div>
              )}

              <figcaption className="min-w-0 leading-tight">
                <div className="truncate text-sm font-semibold">{satu.Nama}</div>
                <div className="font-mono text-xs">{satu.KodeAset}</div>
                {satu.NomorSeri && (
                  <div className="truncate text-[10px] text-muted-foreground">SN {satu.NomorSeri}</div>
                )}
                <div className="truncate text-[10px] text-muted-foreground">
                  {[satu.Kategori, satu.Lokasi].filter(Boolean).join(' · ') || '—'}
                </div>
              </figcaption>
            </figure>
          ))}
        </div>
      </div>
    </>
  );
}
