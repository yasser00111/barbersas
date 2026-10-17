import { cn } from '@/lib/utils';
import {
    InputHTMLAttributes,
    ReactNode,
    SelectHTMLAttributes,
    TextareaHTMLAttributes,
} from 'react';

export function Field({ label, error, hint, children, required }: { label?: string; error?: string; hint?: string; children: ReactNode; required?: boolean }) {
    return (
        <label className="block space-y-1.5">
            {label && (
                <span className="block text-sm font-medium text-ink-700">
                    {label}
                    {required && <span className="text-rose-500"> *</span>}
                </span>
            )}
            {children}
            {hint && !error && <span className="block text-xs text-ink-400">{hint}</span>}
            {error && <span className="block text-xs font-medium text-rose-600">{error}</span>}
        </label>
    );
}

const controlClass =
    'block w-full rounded-lg border border-ink-200 bg-white px-3 py-2 text-sm text-ink-900 shadow-sm transition placeholder:text-ink-400 focus:border-gold-400 focus:outline-none focus:ring-2 focus:ring-gold-200 disabled:bg-ink-50 disabled:text-ink-400';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    error?: boolean;
}

export function Input({ className, error, ...props }: InputProps) {
    return <input {...props} className={cn(controlClass, error && 'border-rose-400 focus:border-rose-400 focus:ring-rose-200', className)} />;
}

interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    error?: boolean;
}

export function Textarea({ className, error, ...props }: TextareaProps) {
    return <textarea {...props} className={cn(controlClass, 'min-h-[90px]', error && 'border-rose-400', className)} />;
}

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    error?: boolean;
}

export function Select({ className, error, children, ...props }: SelectProps) {
    return (
        <select {...props} className={cn(controlClass, 'pr-8', error && 'border-rose-400', className)}>
            {children}
        </select>
    );
}

export function Checkbox({ label, className, ...props }: InputHTMLAttributes<HTMLInputElement> & { label?: ReactNode }) {
    return (
        <label className={cn('inline-flex cursor-pointer items-center gap-2 text-sm text-ink-700', className)}>
            <input
                type="checkbox"
                {...props}
                className="h-4 w-4 rounded border-ink-300 text-gold-500 focus:ring-gold-400"
            />
            {label}
        </label>
    );
}
