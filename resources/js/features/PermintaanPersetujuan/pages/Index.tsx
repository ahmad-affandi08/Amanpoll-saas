import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogTrigger,
} from '@/components/ui/dialog';
import { http } from '@/lib/http';
import type { PermintaanPersetujuan, StatusPermintaanPersetujuan } from '@/features/Persetujuan/types';
import { rutePermintaanPersetujuan } from '@/features/PermintaanPersetujuan/api';

function badgeStatus(status: StatusPermintaanPersetujuan) {
  const varian = status === 'Disetujui' ? 'default' : status === 'Menunggu' ? 'secondary' : 'outline';
  return <Badge variant={varian}>{status}</Badge>;
}

function DialogKeputusan({
  permintaan,
  tindakan,
  onSelesai,
}: {
  permintaan: PermintaanPersetujuan;
  tindakan: 'setujui' | 'tolak';
  onSelesai: () => void;
}) {
  const [buka, setBuka] = useState(false);
  const [catatan, setCatatan] = useState('');
  const [memproses, setMemproses] = useState(false);

  const kirim = () => {
    setMemproses(true);
    router.post(
      rutePermintaanPersetujuan.detail2(permintaan.Id, tindakan),
      { Catatan: catatan || null },
      {
        preserveScroll: true,
        onSuccess: () => {
          setBuka(false);
          setCatatan('');
          onSelesai();
        },
        onFinish: () => setMemproses(false),
      },
    );
  };

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button size="sm" variant={tindakan === 'setujui' ? 'default' : 'outline'}>
          {tindakan === 'setujui' ? 'Setujui' : 'Tolak'}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{tindakan === 'setujui' ? 'Setujui Permintaan' : 'Tolak Permintaan'}</DialogTitle>
        </DialogHeader>
        <div className="space-y-2">
          <Textarea
            placeholder="Catatan (opsional)"
            value={catatan}
            onChange={(e) => setCatatan(e.target.value)}
            rows={3}
          />
        </div>
        <DialogFooter>
          <Button
            onClick={kirim}
            disabled={memproses}
            variant={tindakan === 'setujui' ? 'default' : 'destructive'}
          >
            {tindakan === 'setujui' ? 'Setujui' : 'Tolak'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function InboxTab() {
  const [data, setData] = useState<PermintaanPersetujuan[]>([]);
  const [memuat, setMemuat] = useState(true);

  const muat = () => {
    setMemuat(true);
    http
      .get(rutePermintaanPersetujuan.inbox)
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, []);

  if (memuat) return <p className="text-sm text-muted-foreground">Memuat...</p>;
  if (data.length === 0)
    return <p className="text-sm text-muted-foreground">Tidak ada permintaan yang perlu tindakan Anda.</p>;

  return (
    <div className="space-y-3">
      {data.map((permintaan) => (
        <div
          key={permintaan.Id}
          className="flex items-center justify-between rounded-md border border-border p-3"
        >
          <div>
            <div className="font-medium text-foreground">
              {permintaan.NamaAlur} -- {permintaan.JenisEntitas}
            </div>
            <div className="text-sm text-muted-foreground">
              Diminta oleh {permintaan.NamaPeminta} pada{' '}
              {new Date(permintaan.DimintaPada).toLocaleString('id-ID')}
            </div>
          </div>
          <div className="flex gap-2">
            <DialogKeputusan permintaan={permintaan} tindakan="setujui" onSelesai={muat} />
            <DialogKeputusan permintaan={permintaan} tindakan="tolak" onSelesai={muat} />
          </div>
        </div>
      ))}
    </div>
  );
}

function MilikSayaTab() {
  const [data, setData] = useState<PermintaanPersetujuan[]>([]);
  const [memuat, setMemuat] = useState(true);

  const muat = () => {
    setMemuat(true);
    http
      .get(rutePermintaanPersetujuan.milikSaya)
      .then((res) => setData(res.data))
      .finally(() => setMemuat(false));
  };

  useEffect(muat, []);

  const batalkan = (permintaan: PermintaanPersetujuan) => {
    if (!confirm('Batalkan permintaan ini?')) return;
    router.delete(rutePermintaanPersetujuan.detail(permintaan.Id), { preserveScroll: true, onSuccess: muat });
  };

  if (memuat) return <p className="text-sm text-muted-foreground">Memuat...</p>;
  if (data.length === 0)
    return <p className="text-sm text-muted-foreground">Anda belum mengajukan permintaan persetujuan.</p>;

  return (
    <div className="space-y-3">
      {data.map((permintaan) => (
        <div key={permintaan.Id} className="rounded-md border border-border p-3">
          <div className="flex items-center justify-between">
            <div className="font-medium text-foreground">
              {permintaan.NamaAlur} -- {permintaan.JenisEntitas}
            </div>
            {badgeStatus(permintaan.Status)}
          </div>
          <div className="mt-1 text-sm text-muted-foreground">
            Diajukan {new Date(permintaan.DimintaPada).toLocaleString('id-ID')}
            {permintaan.Status !== 'Menunggu' &&
              permintaan.SelesaiPada &&
              ` -- selesai ${new Date(permintaan.SelesaiPada).toLocaleString('id-ID')}`}
          </div>
          {permintaan.Keputusan.length > 0 && (
            <div className="mt-2 space-y-1 border-t border-border pt-2">
              {permintaan.Keputusan.map((k) => (
                <div key={k.Id} className="text-xs text-muted-foreground">
                  {k.NamaPenyetuju}: <span className="font-medium">{k.Keputusan}</span>
                  {k.Catatan && ` -- "${k.Catatan}"`}
                </div>
              ))}
            </div>
          )}
          {permintaan.Status === 'Menunggu' && (
            <div className="mt-2 flex justify-end">
              <Button size="sm" variant="ghost" onClick={() => batalkan(permintaan)}>
                Batalkan
              </Button>
            </div>
          )}
        </div>
      ))}
    </div>
  );
}

export default function PermintaanPersetujuanIndex() {
  return (
    <AppLayout>
      <Head title="Persetujuan Saya" />
      <div className="mb-6">
        <h1 className="text-2xl font-semibold tracking-tight text-foreground">Persetujuan Saya</h1>
        <p className="text-sm text-muted-foreground">
          Kelola permintaan persetujuan yang Anda ajukan atau yang perlu tindakan Anda.
        </p>
      </div>

      <Tabs defaultValue="inbox">
        <TabsList>
          <TabsTrigger value="inbox">Perlu Tindakan Saya</TabsTrigger>
          <TabsTrigger value="milik-saya">Permintaan Saya</TabsTrigger>
        </TabsList>
        <TabsContent value="inbox">
          <InboxTab />
        </TabsContent>
        <TabsContent value="milik-saya">
          <MilikSayaTab />
        </TabsContent>
      </Tabs>
    </AppLayout>
  );
}
