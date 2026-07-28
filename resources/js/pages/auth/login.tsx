import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import AuthLayout from '@/layouts/auth-layout';

interface LoginForm {
    login: string;
    password: string;
    remember: boolean;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { t } = useTranslations();
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        login: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title={t('auth.login_title')} description={t('auth.login_subtitle')}>
            <Head title={t('nav.login')} />

            {status && <div className="bg-secondary/10 text-secondary rounded-md p-3 text-center text-sm font-medium">{status}</div>}

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="login">{t('auth.email_or_mobile')}</Label>
                    <Input
                        id="login"
                        type="text"
                        required
                        autoFocus
                        tabIndex={1}
                        autoComplete="username"
                        value={data.login}
                        onChange={(e) => setData('login', e.target.value)}
                        placeholder="you@example.com / 9876543210"
                    />
                    <InputError message={errors.login} />
                </div>

                <div className="grid gap-2">
                    <div className="flex items-center">
                        <Label htmlFor="password">{t('auth.password')}</Label>
                        {canResetPassword && (
                            <TextLink href={route('password.request')} className="ml-auto text-sm" tabIndex={5}>
                                {t('auth.forgot_password')}
                            </TextLink>
                        )}
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabIndex={2}
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="••••••••"
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="flex items-center space-x-3">
                    <Checkbox
                        id="remember"
                        name="remember"
                        checked={data.remember}
                        onClick={() => setData('remember', !data.remember)}
                        tabIndex={3}
                    />
                    <Label htmlFor="remember">{t('auth.remember_me')}</Label>
                </div>

                <Button type="submit" className="w-full" tabIndex={4} disabled={processing}>
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    {t('nav.login')}
                </Button>

                <div className="text-muted-foreground text-center text-sm">
                    {t('auth.no_account')}{' '}
                    <TextLink href={route('register')} tabIndex={5}>
                        {t('nav.register')}
                    </TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}
