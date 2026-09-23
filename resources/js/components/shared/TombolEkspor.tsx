import { FileDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

/** Format berkas yang dilayani EksporDaftar di server. */
const FORMAT = [
  { nilai: 'csv', label: 'CSV' },
  { nilai: 'xlsx', label: 'Excel (XLSX)' },
  { nilai: 'pdf', label: 'PDF' },
] as const;

interface Props {
  /** Rute ekspor daftar ini, mis. `/aset/ekspor`. */
  url: string;
  /**
   * Penyaring yang sedang berlaku di layar, diteruskan apa adanya.
   *
   * Berkas harus berisi tepat apa yang dilihat pemesannya; tanpa ini ia
   * mengunduh seluruh daftar padahal layarnya sedang tersaring, dan tidak ada
   * di berkas itu yang memberitahunya.
   */
  filter?: Record<string, string | number | null | undefined>;
  label?: string;
}

export function TombolEkspor({ url, filter, label = 'Ekspor' }: Props) {
  const tautan = (format: string) => {
    const parameter = new URLSearchParams();

    Object.entries(filter ?? {}).forEach(([kunci, nilai]) => {
      if (nilai !== null && nilai !== undefined && String(nilai) !== '') {
        parameter.set(kunci, String(nilai));
      }
    });

    parameter.set('format', format);

    return `${url}?${parameter.toString()}`;
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" size="sm">
          <FileDown className="size-4" />
          {label}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {FORMAT.map((satu) => (
          <DropdownMenuItem key={satu.nilai} asChild>
            {/* Unduhan biasa, bukan kunjungan Inertia: responsnya berkas, bukan halaman. */}
            <a href={tautan(satu.nilai)}>{satu.label}</a>
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
