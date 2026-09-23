'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';
import { CheckIcon } from 'lucide-react';
import { Checkbox as CheckboxPrimitive } from 'radix-ui';

/** Tepi Grafit-500 (5,5:1): Garis-300 semula 1,4:1, kotak kosong nyaris tak terlihat (WCAG 1.4.11 butuh 3:1). */
function Checkbox({ className, ...props }: React.ComponentProps<typeof CheckboxPrimitive.Root>) {
  return (
    <CheckboxPrimitive.Root
      data-slot="checkbox"
      className={cn(
        'peer size-4 shrink-0 cursor-pointer rounded-[4px] border border-grafit-500 bg-card shadow-xs transition-shadow outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive data-[state=checked]:border-primary data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground dark:bg-input/30 dark:data-[state=checked]:bg-primary',
        className,
      )}
      {...props}
    >
      <CheckboxPrimitive.Indicator
        data-slot="checkbox-indicator"
        className="grid place-content-center text-current transition-none"
      >
        <CheckIcon className="size-3.5" />
      </CheckboxPrimitive.Indicator>
    </CheckboxPrimitive.Root>
  );
}

export { Checkbox };
