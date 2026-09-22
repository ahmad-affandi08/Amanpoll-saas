import { Head } from '@inertiajs/react';

interface Props {
  email: string;
}

/** Konfirmasi bahwa pencabutan sudah tercatat (MARKETING.md 27). */
export default function PublikBerhentiLangganan({ email }: Props) {
  return (
    <>
      <Head>
        <title>Berhenti Berlangganan — Amanpoll</title>
        <meta name="robots" content="noindex, nofollow" />
      </Head>

      <div className="flex min-h-screen items-center justify-center bg-background p-6">
        <div className="w-full max-w-md space-y-3 rounded-lg border p-6 text-center">
          <h1 className="text-xl font-semibold tracking-tight">Anda sudah berhenti berlangganan</h1>
          <p className="text-sm text-muted-foreground">
            <span className="font-mono">{email}</span> tidak akan lagi menerima email pemasaran dari
            Amanpoll. Email operasional yang berkaitan dengan akun Anda tetap dikirim.
          </p>
        </div>
      </div>
    </>
  );
}
