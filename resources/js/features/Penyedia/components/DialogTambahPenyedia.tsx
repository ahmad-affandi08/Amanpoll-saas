import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import type { AturanWajib } from '@/lib/aturan-wajib';
import { FormInfoPenyedia } from '@/features/Penyedia/components/FormInfoPenyedia';

export function DialogTambahPenyedia({ wajib }: { wajib: AturanWajib }) {
  const [buka, setBuka] = useState(false);

  return (
    <Dialog open={buka} onOpenChange={setBuka}>
      <DialogTrigger asChild>
        <Button>Tambah Penyedia</Button>
      </DialogTrigger>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Tambah Penyedia</DialogTitle>
        </DialogHeader>
        <FormInfoPenyedia penyedia={null} wajib={wajib} onSukses={() => setBuka(false)} />
      </DialogContent>
    </Dialog>
  );
}
