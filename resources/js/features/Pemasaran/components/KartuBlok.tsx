import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { BlokDisunting, PilihanHalaman } from '@/features/Pemasaran/types';

/** Blok harga hanya menyebut kode paket; angkanya selalu dibaca dari domain Langganan. */
function BantuanHarga({ jenis, pilihan }: { jenis: string; pilihan: PilihanHalaman }) {
  const contoh =
    jenis === 'Harga'
      ? {
          judul: 'Harga',
          siklus: pilihan.SiklusHarga[0] ?? 'Bulanan',
          catatanPromo: '',
          paket: pilihan.Paket.slice(0, 3).map((satu, urutan) => ({
            kode: satu.Kode,
            disorot: urutan === 1,
            badge: urutan === 1 ? 'Paling dipilih' : '',
            ringkasan: '',
            ctaTeks: 'Coba gratis',
            ctaUrl: '/daftar',
          })),
        }
      : {
          judul: 'Perbandingan paket',
          paket: pilihan.Paket.map((satu) => satu.Kode),
          fitur: pilihan.FiturPaket,
        };

  return (
    <div className="grid gap-2 rounded-lg border border-dashed p-3 text-xs text-muted-foreground">
      <p>
        Blok ini hanya menyebut kode paket. Nama, harga, dan daftar fiturnya dibaca dari domain Langganan saat
        halaman tampil, jadi harga di sini tidak pernah basi.
      </p>
      <p>
        Kode paket tersedia:{' '}
        {pilihan.Paket.length === 0 ? (
          <span className="text-destructive">belum ada paket aktif.</span>
        ) : (
          <span className="font-mono">{pilihan.Paket.map((satu) => satu.Kode).join(', ')}</span>
        )}
      </p>
      <pre className="overflow-x-auto rounded bg-muted p-2 font-mono">{JSON.stringify(contoh, null, 2)}</pre>
    </div>
  );
}

export function KartuBlok({
  blok,
  urutan,
  total,
  pilihan,
  ubah,
  hapus,
  pindah,
}: {
  blok: BlokDisunting;
  urutan: number;
  total: number;
  pilihan: PilihanHalaman;
  ubah: (ubahan: Partial<BlokDisunting>) => void;
  hapus: () => void;
  pindah: (arah: -1 | 1) => void;
}) {
  /* Isi blok disunting sebagai JSON. */
  const [naskah, setNaskah] = useState(() => JSON.stringify(blok.Isi ?? {}, null, 2));
  const [galat, setGalat] = useState<string | null>(null);

  const ubahNaskah = (nilai: string) => {
    setNaskah(nilai);

    try {
      const terurai: unknown = JSON.parse(nilai === '' ? '{}' : nilai);

      if (typeof terurai !== 'object' || terurai === null || Array.isArray(terurai)) {
        setGalat('Isi blok harus berupa objek JSON.');

        return;
      }

      setGalat(null);
      ubah({ Isi: terurai as BlokDisunting['Isi'] });
    } catch {
      setGalat('JSON belum sah, perubahan terakhir belum disimpan ke draf.');
    }
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between gap-4 space-y-0">
        <CardTitle className="text-base">
          {urutan + 1}. {blok.Jenis}
        </CardTitle>
        <div className="flex gap-1">
          <Button variant="ghost" size="sm" disabled={urutan === 0} onClick={() => pindah(-1)}>
            Naik
          </Button>
          <Button variant="ghost" size="sm" disabled={urutan === total - 1} onClick={() => pindah(1)}>
            Turun
          </Button>
          <Button variant="ghost" size="sm" onClick={hapus}>
            Hapus
          </Button>
        </div>
      </CardHeader>
      <CardContent className="grid gap-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <div className="grid gap-2">
            <Label>Jenis</Label>
            <Select value={blok.Jenis} onValueChange={(v) => ubah({ Jenis: v })}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Blok.map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {blok.Jenis === 'Formulir' ? (
            <div className="grid gap-2">
              <Label>Formulir</Label>
              <Select value={blok.FormulirKode ?? ''} onValueChange={(v) => ubah({ FormulirKode: v })}>
                <SelectTrigger>
                  <SelectValue placeholder="Pilih formulir" />
                </SelectTrigger>
                <SelectContent>
                  {pilihan.Formulir.map((satu) => (
                    <SelectItem key={satu.Kode} value={satu.Kode}>
                      {satu.Nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          ) : null}
        </div>

        <div className="grid gap-2">
          <Label>Isi (JSON)</Label>
          <Textarea
            rows={8}
            className="font-mono text-xs"
            value={naskah}
            onChange={(e) => ubahNaskah(e.target.value)}
          />
          {galat ? <p className="text-sm text-destructive">{galat}</p> : null}
          {blok.Jenis === 'Harga' || blok.Jenis === 'Perbandingan' ? (
            <BantuanHarga jenis={blok.Jenis} pilihan={pilihan} />
          ) : null}
        </div>
      </CardContent>
    </Card>
  );
}
