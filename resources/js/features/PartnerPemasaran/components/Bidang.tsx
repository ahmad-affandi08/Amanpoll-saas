import { Label } from '@/components/ui/label';

export function Bidang({
  label,
  galat,
  children,
}: {
  label: string;
  galat?: string;
  children: React.ReactNode;
}) {
  return (
    <div className="space-y-1.5">
      <Label>{label}</Label>
      {children}
      {galat && <p className="text-sm text-destructive">{galat}</p>}
    </div>
  );
}
