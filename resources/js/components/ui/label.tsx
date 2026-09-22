import * as React from 'react';
import { cn } from '@/lib/utils';
import { Label as LabelPrimitive } from 'radix-ui';
import { useWajib } from '@/lib/aturan-wajib';

interface LabelProps extends React.ComponentProps<typeof LabelPrimitive.Root> {
  /** Nama field pada FormRequest; status wajibnya diambil dari aturan server. */
  nama?: string;
  /** Memaksa tanda wajib, mis. untuk field yang aturannya bersyarat. */
  wajib?: boolean;
}

function Label({ className, nama, wajib, children, ...props }: LabelProps) {
  const wajibDariServer = useWajib(nama);
  const bertanda = wajib ?? wajibDariServer;

  return (
    <LabelPrimitive.Root
      data-slot="label"
      className={cn(
        'flex items-center gap-2 text-sm leading-none font-medium select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
        className,
      )}
      {...props}
    >
      {children}
      {bertanda && (
        <span className="-ml-1 leading-none text-destructive">
          {/* Bintangnya hiasan; pembaca layar mendapat kata yang sesungguhnya. */}
          <span aria-hidden="true">*</span>
          <span className="sr-only">wajib diisi</span>
        </span>
      )}
    </LabelPrimitive.Root>
  );
}

export { Label };
