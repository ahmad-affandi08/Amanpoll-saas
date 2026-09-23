import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import type { HalamanDetail, PilihanHalaman } from '@/features/Pemasaran/types';
import { rutePemasaran } from '@/features/Pemasaran/api';
import { dariMasukanWaktu, keMasukanWaktu } from '@/lib/waktu';

export function PanelPenerbitan({ halaman, pilihan }: { halaman: HalamanDetail; pilihan: PilihanHalaman }) {
  const [status, setStatus] = useState(halaman.Status);
  const [terbitPada, setTerbitPada] = useState(keMasukanWaktu(halaman.TerbitPada));
  const [tarikPada, setTarikPada] = useState(keMasukanWaktu(halaman.TarikPada));

  const akar = rutePemasaran.halamanDetail(halaman.Id);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Penerbitan</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-6">
        <div className="flex flex-wrap items-center gap-3">
          <Button onClick={() => router.post(`${akar}/terbitkan`, {}, { preserveScroll: true })}>
            Terbitkan Draf Sekarang
          </Button>
          {halaman.VersiDrafId ? (
            <Button variant="outline" asChild>
              <a href={`${akar}/pratinjau/${halaman.VersiDrafId}`} target="_blank" rel="noreferrer">
                Pratinjau Draf
              </a>
            </Button>
          ) : null}
        </div>

        <Separator />

        <div className="grid gap-4 sm:grid-cols-3">
          <div className="grid content-start gap-2">
            <Label>Status</Label>
            <Select value={status} onValueChange={setStatus}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pilihan.Status.filter((satu) => satu !== 'Terbit').map((satu) => (
                  <SelectItem key={satu} value={satu}>
                    {satu}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid content-start gap-2">
            <Label htmlFor="TerbitPada">Terbit pada</Label>
            <Input
              id="TerbitPada"
              type="datetime-local"
              value={terbitPada}
              onChange={(e) => setTerbitPada(e.target.value)}
            />
          </div>

          <div className="grid content-start gap-2">
            <Label htmlFor="TarikPada">Tarik pada</Label>
            <Input
              id="TarikPada"
              type="datetime-local"
              value={tarikPada}
              onChange={(e) => setTarikPada(e.target.value)}
            />
          </div>
        </div>

        <div>
          <Button
            variant="outline"
            onClick={() =>
              router.post(
                `${akar}/status`,
                {
                  Status: status,
                  TerbitPada: dariMasukanWaktu(terbitPada),
                  TarikPada: dariMasukanWaktu(tarikPada),
                },
                { preserveScroll: true },
              )
            }
          >
            Simpan Status
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
