import * as React from 'react';
import {
  format,
  parseISO,
  isValid,
  isToday,
  isSameDay,
  isSameMonth,
  startOfMonth,
  endOfMonth,
  eachDayOfInterval,
  startOfWeek,
  endOfWeek,
  addMonths,
  subMonths,
  addDays,
  subDays,
} from 'date-fns';
import { id } from 'date-fns/locale';
import { CalendarIcon, ChevronDown, ChevronLeft, ChevronRight, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';

export interface DatePickerProps {
  value?: string | null;
  onChange?: (value: string) => void;
  placeholder?: string;
  disabled?: boolean;
  className?: string;
  minDate?: string;
  maxDate?: string;
  showShortcuts?: boolean;
  align?: 'start' | 'center' | 'end';
}

export function DatePicker({
  value,
  onChange,
  placeholder = 'Pilih tanggal',
  disabled = false,
  className,
  minDate,
  maxDate,
  showShortcuts = true,
  align = 'start',
}: DatePickerProps) {
  const [open, setOpen] = React.useState(false);

  // Parse tanggal saat ini
  const parsedValue = React.useMemo(() => {
    if (!value) return null;
    const d = parseISO(value);
    return isValid(d) ? d : null;
  }, [value]);

  const [viewDate, setViewDate] = React.useState<Date>(() => parsedValue ?? new Date());

  React.useEffect(() => {
    if (parsedValue) {
      setViewDate(parsedValue);
    }
  }, [parsedValue]);

  const min = minDate ? parseISO(minDate) : null;
  const max = maxDate ? parseISO(maxDate) : null;

  const isDateDisabled = (date: Date) => {
    if (min && isValid(min) && date < min) return true;
    if (max && isValid(max) && date > max) return true;
    return false;
  };

  const handleSelect = (date: Date) => {
    if (isDateDisabled(date)) return;
    const formatted = format(date, 'yyyy-MM-dd');
    onChange?.(formatted);
    setOpen(false);
  };

  const handleClear = (e: React.MouseEvent) => {
    e.stopPropagation();
    onChange?.('');
  };

  // Kalkulasi hari dalam bulan untuk kalender
  const monthStart = startOfMonth(viewDate);
  const monthEnd = endOfMonth(monthStart);
  const startDate = startOfWeek(monthStart, { weekStartsOn: 1 });
  const endDate = endOfWeek(monthEnd, { weekStartsOn: 1 });
  const days = eachDayOfInterval({ start: startDate, end: endDate });

  const weekDays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <button
          type="button"
          disabled={disabled}
          className={cn(
            'flex h-9 w-full min-w-0 items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer text-left md:text-sm dark:bg-input/30 dark:hover:bg-input/50',
            !parsedValue && 'text-muted-foreground',
            className,
          )}
        >
          <div className="flex items-center gap-2 truncate">
            <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
            <span className={cn('truncate font-normal', parsedValue ? 'text-foreground' : 'text-muted-foreground')}>
              {parsedValue ? (
                format(parsedValue, 'd MMMM yyyy', { locale: id })
              ) : (
                placeholder
              )}
            </span>
          </div>
          {parsedValue && !disabled ? (
            <span
              role="button"
              tabIndex={0}
              onClick={handleClear}
              className="rounded-full p-0.5 hover:bg-muted text-muted-foreground hover:text-foreground transition-colors cursor-pointer shrink-0"
              title="Hapus tanggal"
            >
              <X className="size-3.5" />
            </span>
          ) : (
            <ChevronDown className="size-4 opacity-50 shrink-0 text-muted-foreground" />
          )}
        </button>
      </PopoverTrigger>
      <PopoverContent
        className="w-[calc(100vw-2rem)] max-w-[280px] p-0 shadow-lg border-border bg-popover overflow-hidden"
        align={align}
      >
        {showShortcuts && (
          <div className="flex items-center justify-between gap-1 p-2 border-b border-border bg-permukaan-50">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="h-7 text-xs px-2 flex-1 text-muted-foreground hover:bg-permukaan-100 hover:text-foreground"
              onClick={() => handleSelect(new Date())}
            >
              Hari Ini
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="h-7 text-xs px-2 flex-1 text-muted-foreground hover:bg-permukaan-100 hover:text-foreground"
              onClick={() => handleSelect(addDays(new Date(), 1))}
            >
              Besok
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="h-7 text-xs px-2 flex-1 text-muted-foreground hover:bg-permukaan-100 hover:text-foreground"
              onClick={() => handleSelect(subDays(new Date(), 1))}
            >
              Kemarin
            </Button>
          </div>
        )}

        <div className="p-3">
          {/* Header Bulan & Navigasi */}
          <div className="flex items-center justify-between gap-2 mb-2 px-1">
            <Button
              type="button"
              variant="ghost"
              size="icon"
              className="size-7 text-muted-foreground hover:text-foreground hover:bg-permukaan-100"
              onClick={() => setViewDate((d) => subMonths(d, 1))}
            >
              <ChevronLeft className="size-4" />
            </Button>
            <span className="text-sm font-semibold text-foreground tracking-tight">
              {format(viewDate, 'MMMM yyyy', { locale: id })}
            </span>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              className="size-7 text-muted-foreground hover:text-foreground hover:bg-permukaan-100"
              onClick={() => setViewDate((d) => addMonths(d, 1))}
            >
              <ChevronRight className="size-4" />
            </Button>
          </div>

          {/* Header Nama Hari */}
          <div className="grid grid-cols-7 gap-1 text-center mb-1">
            {weekDays.map((hari) => (
              <div key={hari} className="text-[11px] font-medium text-muted-foreground py-0.5">
                {hari}
              </div>
            ))}
          </div>

          {/* Grid Hari */}
          <div className="grid grid-cols-7 gap-1">
            {days.map((day) => {
              const isSelected = parsedValue ? isSameDay(day, parsedValue) : false;
              const isCurrentMonth = isSameMonth(day, viewDate);
              const isDayToday = isToday(day);
              const isDisabled = isDateDisabled(day);

              return (
                <button
                  key={day.toISOString()}
                  type="button"
                  disabled={isDisabled}
                  onClick={() => handleSelect(day)}
                  className={cn(
                    'size-8 flex items-center justify-center rounded-md text-xs font-normal transition-colors cursor-pointer select-none',
                    !isCurrentMonth && 'text-muted-foreground/30',
                    isDayToday && !isSelected && 'border border-primary/50 text-primary font-medium',
                    isSelected
                      ? 'bg-primary text-primary-foreground font-semibold shadow-xs hover:bg-primary'
                      : 'hover:bg-permukaan-100 text-foreground',
                    isDisabled && 'pointer-events-none opacity-30',
                  )}
                >
                  {format(day, 'd')}
                </button>
              );
            })}
          </div>
        </div>
      </PopoverContent>
    </Popover>
  );
}
