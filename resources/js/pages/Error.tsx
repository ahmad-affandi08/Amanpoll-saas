import { Head } from '@inertiajs/react';

interface ErrorProps {
  status: number;
  pesan: string;
}

const judulPerStatus: Record<number, string> = {
  403: 'Akses ditolak',
  404: 'Data tidak ditemukan',
  409: 'Konflik data',
  422: 'Permintaan tidak valid',
  500: 'Terjadi kesalahan pada server',
};

export default function Error({ status, pesan }: ErrorProps) {
  const judul = judulPerStatus[status] ?? 'Terjadi kesalahan';

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-6">
      <Head title={judul} />
      <div className="w-full max-w-md space-y-2 rounded-lg border border-border bg-card p-6 text-center">
        <p className="font-mono text-sm text-muted-foreground">{status}</p>
        <h1 className="text-xl font-semibold text-foreground">{judul}</h1>
        <p className="text-sm text-muted-foreground">{pesan}</p>
      </div>
    </div>
  );
}
