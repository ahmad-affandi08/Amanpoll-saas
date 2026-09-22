import { Head, router } from '@inertiajs/react';
import { KerangkaPlatform } from '@/features/Platform/components/KerangkaPlatform';
import { KepalaHalaman } from '@/components/shared/KepalaHalaman';
import { HUE_UTAMA } from '@/components/grafik/palet';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { rutePemasaran } from '@/features/Pemasaran/api';
import type { MenuWhatsApp, TemplateWhatsApp } from '@/features/Pemasaran/types';
import { DialogFormTemplate } from '@/features/Pemasaran/components/DialogFormTemplate';
import { DialogKeputusan } from '@/features/Pemasaran/components/DialogKeputusan';
import { KonsolMenu } from '@/features/Pemasaran/components/KonsolMenu';
interface Props {
  template: TemplateWhatsApp[];
  menu: MenuWhatsApp[];
  pratinjauMenu: string;
  kataBerhenti: string[];
  ringkasanKiriman: Record<string, number>;
  variabel: string[];
  pilihan: { Status: string[] };
}

export default function PemasaranWhatsApp({
  template,
  menu,
  pratinjauMenu,
  kataBerhenti,
  ringkasanKiriman,
  variabel,
  pilihan,
}: Props) {
  return (
    <KerangkaPlatform>
      <Head title="WhatsApp" />

      <KepalaHalaman
        judul="WhatsApp"
        deskripsi="Template menunggu persetujuan penyedia sebelum boleh berangkat, dan setiap nomor dapat berhenti kapan saja."
        tanpaBreadcrumb
        aksi={<DialogFormTemplate template={null} variabel={variabel} />}
        className="mb-6"
      />

      <RingkasanKiriman ringkasan={ringkasanKiriman} kataBerhenti={kataBerhenti} />

      <Tabs defaultValue="template" className="mt-6">
        <TabsList>
          <TabsTrigger value="template">Template</TabsTrigger>
          <TabsTrigger value="menu">Menu Percakapan</TabsTrigger>
        </TabsList>

        <TabsContent value="template" className="mt-4 space-y-4">
          {template.length === 0 ? (
            <p className="text-sm text-muted-foreground">Belum ada template WhatsApp.</p>
          ) : (
            template.map((satu) => (
              <KartuTemplate key={satu.Id} template={satu} variabel={variabel} pilihan={pilihan} />
            ))
          )}
        </TabsContent>

        <TabsContent value="menu" className="mt-4">
          <KonsolMenu menu={menu} pratinjau={pratinjauMenu} />
        </TabsContent>
      </Tabs>
    </KerangkaPlatform>
  );
}

function RingkasanKiriman({
  ringkasan,
  kataBerhenti,
}: {
  ringkasan: Record<string, number>;
  kataBerhenti: string[];
}) {
  const baris = Object.entries(ringkasan);
  const puncak = Math.max(...baris.map(([, nilai]) => nilai), 1);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Kiriman WhatsApp</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-3">
          {baris.map(([status, jumlah]) => (
            <div key={status}>
              <div className="flex items-baseline justify-between gap-2 text-sm">
                <span className="text-foreground">{status}</span>
                <span className="font-mono text-foreground">{jumlah.toLocaleString('id-ID')}</span>
              </div>
              <div className="mt-1 h-2 rounded-sm bg-muted" role="img" aria-label={`${status}: ${jumlah}`}>
                <div
                  className="h-2 rounded-sm"
                  style={{
                    width: `${Math.max((jumlah / puncak) * 100, jumlah > 0 ? 2 : 0)}%`,
                    backgroundColor: HUE_UTAMA,
                  }}
                />
              </div>
            </div>
          ))}
        </div>

        <div className="text-sm text-muted-foreground">
          <p>
            Nomor berhenti dengan membalas{' '}
            {kataBerhenti.map((satu) => (
              <Badge key={satu} variant="outline" className="mx-0.5 font-mono">
                {satu}
              </Badge>
            ))}
            . Setelah itu nomornya masuk daftar supresi dan tidak pernah dikirimi lagi, walau otomasi
            menjadwalkannya.
          </p>
          <p className="mt-2">
            Opt-in dibaca dari buku consent yang sama dengan email; berhenti dari WhatsApp tidak mencabut
            consent emailnya.
          </p>
        </div>
      </CardContent>
    </Card>
  );
}

function KartuTemplate({
  template,
  variabel,
  pilihan,
}: {
  template: TemplateWhatsApp;
  variabel: string[];
  pilihan: { Status: string[] };
}) {
  return (
    <Card>
      <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <CardTitle className="text-base">{template.Nama}</CardTitle>
          <p className="font-mono text-xs text-muted-foreground">
            {template.Kode} · {template.Bahasa} · {template.Kategori}
          </p>
        </div>
        <div className="flex shrink-0 flex-wrap items-center gap-2">
          <Badge variant={template.SiapKirim ? 'default' : 'secondary'}>{template.StatusPersetujuan}</Badge>
          <DialogFormTemplate template={template} variabel={variabel} />
          <Button
            variant="outline"
            size="sm"
            onClick={() =>
              router.post(rutePemasaran.whatsappTemplateAjukan(template.Id), {}, { preserveScroll: true })
            }
          >
            Ajukan
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() =>
              router.post(rutePemasaran.whatsappTemplatePeriksa(template.Id), {}, { preserveScroll: true })
            }
          >
            Periksa
          </Button>
          <DialogKeputusan template={template} pilihan={pilihan} />
        </div>
      </CardHeader>

      <CardContent className="space-y-2 text-sm">
        <pre className="whitespace-pre-wrap rounded-md bg-muted p-3 font-sans text-foreground">
          {template.IsiTeks}
        </pre>
        {template.AlasanPenolakan ? <p className="text-destructive">{template.AlasanPenolakan}</p> : null}
        <p className="text-xs text-muted-foreground">
          {template.SiapKirim
            ? 'Siap dikirim.'
            : 'Belum dapat dikirim sampai penyedia menyetujuinya dan templatenya aktif.'}
          {template.IdTemplatePenyedia ? ` Id penyedia: ${template.IdTemplatePenyedia}.` : ''}
        </p>
      </CardContent>
    </Card>
  );
}
