import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
  nilai: string;
  onUbah: (nilai: string) => void;
  galat?: string;
  /** Label kolom; sebagian entitas memakai istilah sendiri, mis. "Kode Aset". */
  label?: string;
  contoh?: string;
  id?: string;
}

/**
 * Kolom kode yang tersembunyi selama server yang membuatkannya.
 *
 * Kode data induk diterbitkan server saat kolomnya dikosongkan, jadi kolomnya
 * tidak perlu ditampilkan. Ia tetap dapat dibuka untuk organisasi yang sudah
 * punya skema penomoran sendiri, dan langsung terbuka saat menyunting data
 * yang kodenya memang sudah ada.
 */
export function BidangKode({ nilai, onUbah, galat, label = 'Kode', contoh, id = 'Kode' }: Props) {
  const [terbuka, setTerbuka] = useState(nilai !== '');
  const [nilaiTerakhir, setNilaiTerakhir] = useState(nilai);

  /*
   * Sebagian halaman memakai satu dialog bersama untuk tambah dan ubah, jadi
   * komponen ini hanya dipasang sekali. Tanpa ini, kode baris yang disunting
   * tidak akan pernah terlihat karena keadaan awalnya sudah terlanjur tertutup.
   */
  if (nilai !== nilaiTerakhir) {
    setNilaiTerakhir(nilai);

    if (nilai !== '' && !terbuka) {
      setTerbuka(true);
    }
  }

  if (!terbuka) {
    return (
      <p className="text-xs text-muted-foreground">
        {label} dibuat otomatis.{' '}
        <button
          type="button"
          onClick={() => setTerbuka(true)}
          className="cursor-pointer underline underline-offset-2 hover:text-foreground"
        >
          Atur sendiri
        </button>
      </p>
    );
  }

  return (
    <div className="space-y-1.5">
      <Label htmlFor={id}>{label}</Label>
      <Input
        id={id}
        value={nilai}
        onChange={(e) => onUbah(e.target.value)}
        placeholder={contoh}
        className="font-mono"
      />
      <p className="text-xs text-muted-foreground">Dikosongkan berarti dibuatkan server.</p>
      {galat && <p className="text-sm text-destructive">{galat}</p>}
    </div>
  );
}
