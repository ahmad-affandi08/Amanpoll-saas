import * as React from 'react';
import { cn } from '@/lib/utils';

function Textarea({ className, ...props }: React.ComponentProps<'textarea'>) {
  return (
    <textarea
      data-slot="textarea"
      className={cn(
        'flex field-sizing-content min-h-16 w-full rounded-sm border border-input bg-card px-2.5 py-1.5 text-base transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:bg-permukaan-100 disabled:text-grafit-500 aria-invalid:border-destructive aria-invalid:ring-destructive md:text-sm dark:bg-input/30',
        className,
      )}
      {...props}
    />
  );
}

export { Textarea };
