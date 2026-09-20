import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const ringkasan = [
  ['Total Aset', '—'], ['Work Order Aktif', '—'], ['PM Jatuh Tempo', '—'], ['Kalibrasi Jatuh Tempo', '—'],
];

export default function Dashboard() {
  return (
    <AppLayout>
      <Head title="Dashboard" />
      <div className="mb-6">
        <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
        <p className="text-sm text-zinc-500">Ringkasan operasional Amanpoll.</p>
      </div>
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {ringkasan.map(([label, value]) => (
          <Card key={label}>
            <CardHeader><CardTitle className="text-sm font-medium text-zinc-500">{label}</CardTitle></CardHeader>
            <CardContent><div className="text-3xl font-semibold">{value}</div></CardContent>
          </Card>
        ))}
      </div>
    </AppLayout>
  );
}
