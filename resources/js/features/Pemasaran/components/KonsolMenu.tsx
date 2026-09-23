import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { MenuWhatsApp } from '@/features/Pemasaran/types';

export function KonsolMenu({ menu, pratinjau }: { menu: MenuWhatsApp[]; pratinjau: string }) {
  const [baris, setBaris] = useState<MenuWhatsApp[]>(menu);

  const ubah = (indeks: number, kunci: keyof MenuWhatsApp, nilai: string | boolean) =>
    setBaris((lama) => lama.map((satu, ke) => (ke === indeks ? { ...satu, [kunci]: nilai } : satu)));

  const tambah = () =>
    setBaris((lama) => [
      ...lama,
      {
        Id: `baru-${lama.length}`,
        Kunci: String(lama.length + 1),
        Urutan: lama.length,
        Label: '',
        Balasan: '',
        Aktif: true,
      },
    ]);

  const simpan = (e: FormEvent) => {
    e.preventDefault();
    router.put(
      rutePemasaran.whatsappMenu,
      {
        Menu: baris.map((satu) => ({
          Kunci: satu.Kunci,
          Label: satu.Label,
          Balasan: satu.Balasan,
          Aktif: satu.Aktif,
        })),
      },
      { preserveScroll: true },
    );
  };

  return (
    <div className="grid gap-6 lg:grid-cols-3">
      <form onSubmit={simpan} className="space-y-4 lg:col-span-2">
        {baris.map((satu, indeks) => (
          <Card key={satu.Id}>
            <CardContent className="grid gap-3 p-4 sm:grid-cols-[6rem_1fr]">
              <div className="grid content-start gap-2">
                <Label htmlFor={`Kunci-${indeks}`}>Balasan</Label>
                <Input
                  id={`Kunci-${indeks}`}
                  value={satu.Kunci}
                  onChange={(e) => ubah(indeks, 'Kunci', e.target.value)}
                  required
                />
              </div>
              <div className="grid content-start gap-2">
                <Label htmlFor={`Label-${indeks}`}>Label menu</Label>
                <Input
                  id={`Label-${indeks}`}
                  value={satu.Label}
                  onChange={(e) => ubah(indeks, 'Label', e.target.value)}
                  required
                />
              </div>
              <div className="sm:col-span-2 grid gap-2">
                <Label htmlFor={`Balasan-${indeks}`}>Jawaban yang dikirim</Label>
                <Textarea
                  id={`Balasan-${indeks}`}
                  rows={3}
                  value={satu.Balasan}
                  onChange={(e) => ubah(indeks, 'Balasan', e.target.value)}
                  required
                />
              </div>
              <div className="sm:col-span-2 flex items-center justify-between">
                <label className="flex items-center gap-2 text-sm">
                  <Checkbox
                    checked={satu.Aktif}
                    onCheckedChange={(nilai) => ubah(indeks, 'Aktif', nilai === true)}
                  />
                  Aktif
                </label>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => setBaris((lama) => lama.filter((_, ke) => ke !== indeks))}
                >
                  Hapus
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}

        <div className="flex gap-2">
          <Button type="button" variant="outline" onClick={tambah}>
            Tambah butir
          </Button>
          <Button type="submit">Simpan menu</Button>
        </div>
      </form>

      <Card className="h-fit">
        <CardHeader>
          <CardTitle className="text-base">Pratinjau</CardTitle>
        </CardHeader>
        <CardContent>
          <pre className="whitespace-pre-wrap rounded-md bg-muted p-3 font-sans text-sm text-foreground">
            {pratinjau}
          </pre>
          <p className="mt-2 text-xs text-muted-foreground">
            Sapaan dan balasan untuk pilihan tak dikenal diatur di halaman Pengaturan pemasaran.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
