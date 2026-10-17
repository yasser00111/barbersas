import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ButtonHTMLAttributes, ReactNode } from 'react';

type Variant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger';
type Size = 'sm' | 'md' | 'lg';

const variants: Record<Variant, string> = {
    primary: 'bg-ink-900 text-white hover:bg-ink-800 focus-visible:outline-ink-900',
    secondary: 'bg-gold-400 text-ink-950 hover:bg-gold-300 focus-visible:outline-gold-500',
    outline: 'border border-ink-200 bg-white text-ink-800 hover:bg-ink-50',
    ghost: 'text-ink-700 hover:bg-ink-100',
    danger: 'bg-rose-600 text-white hover:bg-rose-500',
};

const sizes: Record<Size, string> = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-5 py-2.5 text-sm',
};

interface CommonProps {
    variant?: Variant;
    size?: Size;
    className?: string;
    children: ReactNode;
}

interface ButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children'>, CommonProps {
    loading?: boolean;
}

export function Button({ variant = 'primary', size = 'md', className, children, loading, disabled, ...props }: ButtonProps) {
    return (
        <button
            {...props}
            disabled={disabled || loading}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                variants[variant],
                sizes[size],
                className,
            )}
        >
            {loading && (
                <span className="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
            )}
            {children}
        </button>
    );
}

interface LinkButtonProps extends CommonProps {
    href: string;
    method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    as?: 'a' | 'button';
    preserveScroll?: boolean;
    data?: Record<string, never>;
}

export function LinkButton({ href, variant = 'primary', size = 'md', className, children, method, as, preserveScroll, data }: LinkButtonProps) {
    return (
        <Link
            href={href}
            method={method as never}
            as={as as never}
            data={data as never}
            preserveScroll={preserveScroll}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2',
                variants[variant],
                sizes[size],
                className,
            )}
        >
            {children}
        </Link>
    );
}
