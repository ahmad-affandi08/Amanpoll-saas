/**
 * Bentuk baris tooltip yang benar-benar dipakai di sini. Dibuat longgar
 * (`unknown` untuk nilai) supaya cocok dengan tipe internal Recharts yang
 * mengizinkan larik, lalu dipersempit saat dibaca.
 */
interface BarisTooltip {
  name?: string | number;
  value?: unknown;
  color?: string;
  payload?: unknown;
}

export interface PropsTooltipGrafik {
  active?: boolean;
  payload?: readonly BarisTooltip[];
  label?: string | number;
  formatNilai: (nilai: number) => string;
  formatLabel?: (label: string) => string;
}

/** Nilai tooltip bisa berupa larik pada grafik bertumpuk; ambil angka pertamanya. */
function angka(nilai: unknown): number {
  if (Array.isArray(nilai)) {
    return Number(nilai[0] ?? 0);
  }

  return Number(nilai ?? 0);
}

/** Label kategori disimpan pada payload baris; dibaca aman tanpa asumsi bentuk. */
function labelBaris(payload: unknown, cadangan: string): string {
  if (payload !== null && typeof payload === 'object' && 'Label' in payload) {
    const label = (payload as { Label?: unknown }).Label;
    if (typeof label === 'string') {
      return label;
    }
  }

  return cadangan;
}

/**
 * Tooltip bersama untuk seluruh grafik Amanpoll.
 *
 * Nilai tampil sebagai elemen kuat dan nama deret sebagai pendukung, karena
 * pembaca yang sudah mengarahkan penunjuk sudah tahu deretnya dan yang ia cari
 * adalah angkanya. Deret dikunci dengan garis pendek berwarna, bukan kotak
 * penuh, supaya tinta data tidak dipakai untuk pekerjaan label.
 *
 * Label kategori berasal dari data (nama gudang, nama anggaran) sehingga selalu
 * dirender sebagai teks React biasa, tidak pernah lewat HTML mentah.
 */
export function TooltipGrafik({
  active,
  payload,
  label,
  formatNilai,
  formatLabel,
}: PropsTooltipGrafik) {
  if (!active || !payload || payload.length === 0) {
    return null;
  }

  const judul = typeof label === 'string' ? (formatLabel?.(label) ?? label) : '';

  return (
    <div className="rounded-[5px] border border-border bg-card px-3 py-2 shadow-lg">
      {judul && <p className="mb-1 text-xs font-medium text-muted-foreground">{judul}</p>}
      <ul className="space-y-1">
        {payload.map((baris, indeks) => (
          <li key={`${baris.name ?? indeks}`} className="flex items-center gap-2 text-sm">
            <span
              aria-hidden="true"
              className="h-0.5 w-3 shrink-0 rounded-full"
              style={{ backgroundColor: baris.color ?? undefined }}
            />
            <span className="font-semibold tabular-nums text-foreground">
              {formatNilai(angka(baris.value))}
            </span>
            <span className="truncate text-xs text-muted-foreground">
              {labelBaris(baris.payload, String(baris.name ?? ''))}
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}
