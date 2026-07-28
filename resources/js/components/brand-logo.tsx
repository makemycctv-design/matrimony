import { cn } from '@/lib/utils';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { HeartHandshake } from 'lucide-react';

interface BrandLogoProps {
    className?: string;
    showText?: boolean;
    textClassName?: string;
}

/**
 * The platform wordmark. Uses the app name from shared props so it stays in
 * sync with branding/tenant configuration.
 */
export default function BrandLogo({ className, showText = true, textClassName }: BrandLogoProps) {
    const { name } = usePage<SharedData>().props;

    return (
        <div className={cn('flex items-center gap-2', className)}>
            <span className="bg-primary text-primary-foreground flex size-9 items-center justify-center rounded-xl shadow-sm">
                <HeartHandshake className="size-5" strokeWidth={2.2} />
            </span>
            {showText && <span className={cn('text-lg font-semibold tracking-tight', textClassName)}>{name}</span>}
        </div>
    );
}
