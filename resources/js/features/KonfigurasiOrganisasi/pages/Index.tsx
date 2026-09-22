import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import KerangkaAplikasi from '@/layouts/KerangkaAplikasi';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { KonfigurasiOrganisasi } from '@/features/Konfigurasi/types';
import { ruteKonfigurasiOrganisasi } from '@/features/KonfigurasiOrganisasi/api';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';

interface Props {
  konfigurasi: KonfigurasiOrganisasi[];
}

function BarisKonfigurasi({ item }: { item: KonfigurasiOrganisasi }) {
  const [nilai, setNilai] = useState<string>(item.Rahasia ? '' : String(item.Nilai ?? ''));
  const [menyimpan, setMenyimpan] = useState(false);

  const simpan = (nilaiBaru: boolean | number | string) => {
    setMenyimpan(true);
    router.put(
      ruteKonfigurasiOrganisasi.detail(item.Kunci),
      { Nilai: nilaiBaru },
      {
        preserveScroll: true,
        onFinish: () => setMenyimpan(false),
      },
    );
  };

  return (
    <div className="flex items-center justify-between border-b border-border py-3 last:border-0">
      <div>
        <div className="text-sm font-medium text-foreground">{item.Label}</div>
        <div className="font-mono text-xs text-muted-foreground">{item.Kunci}</div>
      </div>
      <div className="flex items-center gap-2">
        {item.Tipe === 'boolean' && (
          <Switch checked={Boolean(item.Nilai)} disabled={menyimpan} onCheckedChange={(v) => simpan(v)} />
        )}
        {item.Tipe !== 'boolean' && (
          <>
            <Input
              type={item.Tipe === 'integer' ? 'number' : item.Rahasia ? 'password' : 'text'}
              value={nilai}
              placeholder={item.Rahasia ? 'Diisi tersembunyi' : undefined}
              onChange={(e) => setNilai(e.target.value)}
              className="w-56"
            />
            <Button
              size="sm"
              variant="outline"
              disabled={menyimpan || nilai === ''}
              onClick={() => simpan(item.Tipe === 'integer' ? Number(nilai) : nilai)}
            >
              Simpan
            </Button>
          </>
        )}
      </div>
    </div>
  );
}

export default function KonfigurasiOrganisasiIndex({ konfigurasi }: Props) {
  const kelompok = konfigurasi.reduce<Record<string, KonfigurasiOrganisasi[]>>((acc, item) => {
    (acc[item.Namespace] ??= []).push(item);
    return acc;
  }, {});

  return (
    <KerangkaAplikasi>
      <Head title="Konfigurasi" />
      <KepalaHalaman judul="Konfigurasi Organisasi" deskripsi="Pengaturan per fitur untuk organisasi Anda." />

      <div className="space-y-4">
        {Object.entries(kelompok).map(([namespace, daftar]) => (
          <Card key={namespace}>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                {namespace}
                {daftar.some((d) => d.Rahasia) && <Badge variant="secondary">Berisi Rahasia</Badge>}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {daftar.map((item) => (
                <BarisKonfigurasi key={item.Kunci} item={item} />
              ))}
            </CardContent>
          </Card>
        ))}
      </div>
    </KerangkaAplikasi>
  );
}
