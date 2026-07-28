import { Head, router, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import AuthLayout from '@/layouts/auth-layout';
import { maskMobile } from '@/lib/format';

interface VerifyMobileProps {
    mobile: string | null;
    status?: string;
}

export default function VerifyMobile({ mobile, status }: VerifyMobileProps) {
    const { t } = useTranslations();
    const { data, setData, post, processing, errors } = useForm<{ code: string }>({ code: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.mobile.verify'));
    };

    return (
        <AuthLayout title={t('auth.verify_mobile_title')} description={t('auth.verify_mobile_subtitle')}>
            <Head title={t('auth.verify_mobile_title')} />

            {status && <div className="bg-secondary/10 text-secondary rounded-md p-3 text-center text-sm font-medium">{status}</div>}

            <p className="text-muted-foreground text-center text-sm">
                We sent a 6-digit code to <span className="text-foreground font-medium">{maskMobile(mobile)}</span>
            </p>

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="code">Verification code</Label>
                    <Input
                        id="code"
                        inputMode="numeric"
                        autoFocus
                        maxLength={6}
                        className="text-center text-2xl tracking-[0.5em]"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value.replace(/\D/g, ''))}
                        placeholder="000000"
                    />
                    <InputError message={errors.code} />
                </div>

                <Button type="submit" className="w-full" disabled={processing || data.code.length < 6}>
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    {t('common.continue')}
                </Button>
            </form>

            <div className="flex items-center justify-between text-sm">
                <button type="button" className="text-primary hover:underline" onClick={() => router.post(route('verification.mobile.send'))}>
                    {t('auth.resend_code')}
                </button>
                <button type="button" className="text-muted-foreground hover:underline" onClick={() => router.post(route('logout'))}>
                    {t('nav.logout')}
                </button>
            </div>
        </AuthLayout>
    );
}
