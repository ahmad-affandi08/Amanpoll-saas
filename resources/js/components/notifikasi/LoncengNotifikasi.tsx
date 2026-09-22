import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { http } from '@/lib/http';
import type { Notifikasi } from '@/features/Notifikasi/types';
import { ruteNotifikasi } from '@/features/Notifikasi/api';

export function LoncengNotifikasi() {
  const [data, setData] = useState<Notifikasi[]>([]);
  const [jumlahBelumDibaca, setJumlahBelumDibaca] = useState(0);
  const [buka, setBuka] = useState(false);

  const muat = () => {
    http.get(ruteNotifikasi.ringkasan).then((res) => {
      setData(res.data.data);
      setJumlahBelumDibaca(res.data.jumlahBelumDibaca);
    });
  };

  useEffect(() => {
    muat();
    const interval = setInterval(muat, 60000);
    return () => clearInterval(interval);
  }, []);

  const bacaSatu = (notifikasi: Notifikasi) => {
    if (!notifikasi.DibacaPada) {
      router.post(ruteNotifikasi.baca(notifikasi.Id), {}, { preserveScroll: true, onSuccess: muat });
    }
  };

  const bacaSemua = () => {
    router.post(ruteNotifikasi.bacaSemua, {}, { preserveScroll: true, onSuccess: muat });
  };

  return (
    <DropdownMenu
      open={buka}
      onOpenChange={(v) => {
        setBuka(v);
        if (v) muat();
      }}
    >
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className="relative" aria-label="Notifikasi">
          <Bell size={18} strokeWidth={1.75} />
          {jumlahBelumDibaca > 0 && (
            <Badge
              className="absolute -right-1 -top-1 h-4 min-w-4 justify-center px-1 py-0 text-[10px]"
              variant="bahaya"
            >
              {jumlahBelumDibaca}
            </Badge>
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <DropdownMenuLabel className="flex items-center justify-between">
          Notifikasi
          {jumlahBelumDibaca > 0 && (
            <button
              type="button"
              className="text-xs font-normal text-muted-foreground hover:underline"
              onClick={bacaSemua}
            >
              Tandai semua dibaca
            </button>
          )}
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        {data.length === 0 && (
          <div className="px-2 py-4 text-center text-sm text-muted-foreground">Belum ada notifikasi.</div>
        )}
        <div className="max-h-96 overflow-y-auto">
          {data.map((n) => (
            <DropdownMenuItem
              key={n.Id}
              className="flex flex-col items-start gap-0.5 whitespace-normal"
              onClick={() => bacaSatu(n)}
            >
              <div className="flex w-full items-center justify-between gap-2">
                <span className="font-medium text-foreground">{n.Judul ?? n.JenisPeristiwa}</span>
                {!n.DibacaPada && <span className="h-2 w-2 shrink-0 rounded-full bg-primary" />}
              </div>
              <span className="text-xs text-muted-foreground">{n.Isi}</span>
              <span className="text-xs text-muted-foreground">
                {new Date(n.DibuatPada).toLocaleString('id-ID')}
              </span>
            </DropdownMenuItem>
          ))}
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
