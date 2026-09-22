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
// Dialias karena komponen ini juga menerima prop bernama id.
import { id as lokalId } from 'date-fns/locale';
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
  /** Menyambungkan Label htmlFor ke pemicunya. */
  id?: string;
  /**
   * Menghidupkan kembali penjagaan submit bawaan browser.
   *
   * Pemicunya sebuah tombol, dan tombol tidak pernah menahan submit. Tanpa
   * cerminan tersembunyi di bawah ini, mengganti <input type="date" required>
   * dengan DatePicker diam-diam melepas penjagaan yang tadinya ada.
   */
  required?: boolean;
  name?: string;
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
  id,
  required = false,
  name,
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

  const handleClear = (e?: React.MouseEvent) => {
    e?.stopPropagation();
    onChange?.('');
    setOpen(false);
  };

  // Kalkulasi hari dalam bulan untuk kalender
  const monthStart = startOfMonth(viewDate);
  const monthEnd = endOfMonth(monthStart);
  const startDate = startOfWeek(monthStart, { weekStartsOn: 1 });
  const endDate = endOfWeek(monthEnd, { weekStartsOn: 1 });
  const days = eachDayOfInterval({ start: startDate, end: endDate });

  const weekDays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

  const isTodaySelected = parsedValue ? isToday(parsedValue) : false;
  const isTomorrowSelected = parsedValue ? isSameDay(parsedValue, addDays(new Date(), 1)) : false;
  const isYesterdaySelected = parsedValue ? isSameDay(parsedValue, subDays(new Date(), 1)) : false;

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <button
          id={id}
          type="button"
          disabled={disabled}
          className={cn(
            'flex h-9 w-full min-w-0 items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer text-left dark:bg-input/30 dark:hover:bg-input/50',
            !parsedValue && 'text-muted-foreground',
            className,
          )}
        >
          <div className="flex items-center gap-2 truncate">
            <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
            <span
              className={cn(
                'truncate font-medium',
                parsedValue ? 'text-foreground' : 'text-muted-foreground',
              )}
            >
              {parsedValue ? format(parsedValue, 'd MMMM yyyy', { locale: lokalId }) : placeholder}
            </span>
          </div>
          {parsedValue && !disabled ? (
            <span
              role="button"
              tabIndex={0}
              onClick={handleClear}
              className="rounded-full p-1 hover:bg-permukaan-100 text-muted-foreground hover:text-foreground transition-colors cursor-pointer shrink-0"
              title="Hapus tanggal"
            >
              <X className="size-3.5" />
            </span>
          ) : (
            <ChevronDown className="size-4 opacity-50 shrink-0 text-muted-foreground" />
          )}
        </button>
      </PopoverTrigger>

      {required && (
        <input
          tabIndex={-1}
          aria-hidden="true"
          required
          name={name}
          value={value ?? ''}
          onChange={() => {}}
          className="pointer-events-none absolute size-0 opacity-0"
        />
      )}

      <PopoverContent
        className="w-[288px] p-0 shadow-2xl border border-permukaan-200 bg-card rounded-2xl overflow-hidden"
        align={align}
      >
        {/* Pilihan Cepat / Shortcuts */}
        {showShortcuts && (
          <div className="flex items-center justify-between gap-1.5 p-2.5 border-b border-permukaan-200 bg-permukaan-50/80">
            <Button
              type="button"
              variant={isTodaySelected ? 'default' : 'ghost'}
              size="sm"
              className={cn(
                'h-7 text-xs px-2 flex-1 rounded-lg cursor-pointer transition-all',
                isTodaySelected
                  ? 'bg-teknisi-700 text-white hover:bg-teknisi-800 font-semibold shadow-xs'
                  : 'text-permukaan-700 hover:bg-permukaan-100 font-medium',
              )}
              onClick={() => handleSelect(new Date())}
            >
              Hari Ini
            </Button>
            <Button
              type="button"
              variant={isTomorrowSelected ? 'default' : 'ghost'}
              size="sm"
              className={cn(
                'h-7 text-xs px-2 flex-1 rounded-lg cursor-pointer transition-all',
                isTomorrowSelected
                  ? 'bg-teknisi-700 text-white hover:bg-teknisi-800 font-semibold shadow-xs'
                  : 'text-permukaan-700 hover:bg-permukaan-100 font-medium',
              )}
              onClick={() => handleSelect(addDays(new Date(), 1))}
            >
              Besok
            </Button>
            <Button
              type="button"
              variant={isYesterdaySelected ? 'default' : 'ghost'}
              size="sm"
              className={cn(
                'h-7 text-xs px-2 flex-1 rounded-lg cursor-pointer transition-all',
                isYesterdaySelected
                  ? 'bg-teknisi-700 text-white hover:bg-teknisi-800 font-semibold shadow-xs'
                  : 'text-permukaan-700 hover:bg-permukaan-100 font-medium',
              )}
              onClick={() => handleSelect(subDays(new Date(), 1))}
            >
              Kemarin
            </Button>
          </div>
        )}

        <div className="p-3.5">
          {/* Header Bulan & Navigasi */}
          <div className="flex items-center justify-between h-9 mb-2 px-1">
            <Button
              type="button"
              variant="ghost"
              size="icon"
              className="size-7 rounded-md text-permukaan-600 hover:text-permukaan-900 hover:bg-permukaan-100 cursor-pointer"
              onClick={() => setViewDate((d) => subMonths(d, 1))}
              title="Bulan sebelumnya"
            >
              <ChevronLeft className="size-4" />
            </Button>
            <span className="text-sm font-bold text-permukaan-900 tracking-tight capitalize select-none">
              {format(viewDate, 'MMMM yyyy', { locale: lokalId })}
            </span>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              className="size-7 rounded-md text-permukaan-600 hover:text-permukaan-900 hover:bg-permukaan-100 cursor-pointer"
              onClick={() => setViewDate((d) => addMonths(d, 1))}
              title="Bulan berikutnya"
            >
              <ChevronRight className="size-4" />
            </Button>
          </div>

          {/* Header Nama Hari */}
          <div className="grid grid-cols-7 text-center mb-1.5 select-none">
            {weekDays.map((hari) => (
              <div
                key={hari}
                className="h-6 flex items-center justify-center text-[11px] font-semibold text-permukaan-400 uppercase tracking-wider"
              >
                {hari}
              </div>
            ))}
          </div>

          {/* Grid Hari */}
          <div className="grid grid-cols-7 gap-y-1">
            {days.map((day) => {
              const isSelected = parsedValue ? isSameDay(day, parsedValue) : false;
              const isCurrentMonth = isSameMonth(day, viewDate);
              const isDayToday = isToday(day);
              const isDisabled = isDateDisabled(day);

              return (
                <div key={day.toISOString()} className="h-8.5 w-full flex items-center justify-center">
                  <button
                    type="button"
                    disabled={isDisabled}
                    onClick={() => handleSelect(day)}
                    className={cn(
                      'size-8 flex items-center justify-center rounded-full text-xs font-medium transition-all cursor-pointer select-none',
                      // Warna teks bulan aktif vs luar bulan
                      isCurrentMonth ? 'text-permukaan-800' : 'text-permukaan-300 hover:text-permukaan-500',
                      // Hari ini
                      isDayToday && !isSelected && 'border border-teknisi-600 font-bold text-teknisi-700',
                      // Tanggal terpilih
                      isSelected
                        ? 'bg-teknisi-700 text-white font-bold shadow-sm hover:bg-teknisi-800 hover:text-white'
                        : 'hover:bg-permukaan-100',
                      isDisabled && 'pointer-events-none opacity-30',
                    )}
                  >
                    {format(day, 'd')}
                  </button>
                </div>
              );
            })}
          </div>
        </div>

        {/* Footer */}
        <div className="border-t border-permukaan-200 bg-permukaan-50 px-3.5 py-2.5 flex items-center justify-between gap-2">
          <div className="text-xs text-permukaan-600 font-medium truncate">
            {parsedValue ? (
              <span className="font-semibold text-permukaan-900">
                {format(parsedValue, 'd MMM yyyy', { locale: lokalId })}
              </span>
            ) : (
              <span className="text-permukaan-400 text-[11px]">Belum dipilih</span>
            )}
          </div>
          <div className="flex items-center gap-1.5 shrink-0">
            {parsedValue && (
              <Button
                type="button"
                variant="outline"
                size="sm"
                className="h-7 px-2.5 text-xs rounded-lg cursor-pointer border-permukaan-200 hover:bg-permukaan-100 text-permukaan-700"
                onClick={() => handleClear()}
              >
                Kosongkan
              </Button>
            )}
            <Button
              type="button"
              size="sm"
              className="h-7 px-3 text-xs font-semibold rounded-lg cursor-pointer bg-teknisi-700 hover:bg-teknisi-800 text-white shadow-xs"
              onClick={() => handleSelect(new Date())}
            >
              Hari Ini
            </Button>
          </div>
        </div>
      </PopoverContent>
    </Popover>
  );
}
