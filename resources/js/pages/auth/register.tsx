import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslations } from '@/hooks/use-translations';
import AuthLayout from '@/layouts/auth-layout';

interface RegisterForm {
    name: string;
    email: string;
    country_code: string;
    mobile: string;
    gender: string;
    profile_for: string;
    password: string;
    password_confirmation: string;
    accept_terms: boolean;
    accept_privacy: boolean;
    marketing_opt_in: boolean;
    [key: string]: string | boolean;
}

export default function Register() {
    const { t } = useTranslations();
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        name: '',
        email: '',
        country_code: '+91',
        mobile: '',
        gender: '',
        profile_for: 'self',
        password: '',
        password_confirmation: '',
        accept_terms: false,
        accept_privacy: false,
        marketing_opt_in: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout title={t('auth.register_title')} description={t('auth.register_subtitle')}>
            <Head title={t('nav.register')} />

            <form className="flex flex-col gap-5" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="profile_for">{t('auth.profile_for')}</Label>
                    <Select value={data.profile_for} onValueChange={(v) => setData('profile_for', v)}>
                        <SelectTrigger id="profile_for">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="self">Myself</SelectItem>
                            <SelectItem value="son">My son</SelectItem>
                            <SelectItem value="daughter">My daughter</SelectItem>
                            <SelectItem value="brother">My brother</SelectItem>
                            <SelectItem value="sister">My sister</SelectItem>
                            <SelectItem value="relative">A relative</SelectItem>
                            <SelectItem value="friend">A friend</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.profile_for} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="name">{t('auth.full_name')}</Label>
                    <Input
                        id="name"
                        required
                        autoFocus
                        autoComplete="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        disabled={processing}
                        placeholder="e.g. Anjali Menon"
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="gender">{t('auth.gender')}</Label>
                    <Select value={data.gender} onValueChange={(v) => setData('gender', v)}>
                        <SelectTrigger id="gender">
                            <SelectValue placeholder="Select gender" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="male">{t('common.male')}</SelectItem>
                            <SelectItem value="female">{t('common.female')}</SelectItem>
                            <SelectItem value="other">{t('common.other')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.gender} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">{t('auth.email')}</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        disabled={processing}
                        placeholder="email@example.com"
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="mobile">{t('auth.mobile')}</Label>
                    <div className="flex gap-2">
                        <Input
                            id="country_code"
                            className="w-20 shrink-0"
                            value={data.country_code}
                            onChange={(e) => setData('country_code', e.target.value)}
                            disabled={processing}
                            aria-label="Country code"
                        />
                        <Input
                            id="mobile"
                            type="tel"
                            required
                            inputMode="numeric"
                            autoComplete="tel"
                            className="flex-1"
                            value={data.mobile}
                            onChange={(e) => setData('mobile', e.target.value)}
                            disabled={processing}
                            placeholder="9876543210"
                        />
                    </div>
                    <InputError message={errors.mobile} />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="password">{t('auth.password')}</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            disabled={processing}
                            placeholder="••••••••"
                        />
                        <InputError message={errors.password} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">{t('auth.confirm_password')}</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            disabled={processing}
                            placeholder="••••••••"
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>
                </div>

                <div className="bg-muted/30 space-y-3 rounded-lg border p-4">
                    <label className="flex items-start gap-3 text-sm">
                        <Checkbox checked={data.accept_terms} onClick={() => setData('accept_terms', !data.accept_terms)} className="mt-0.5" />
                        <span>{t('auth.accept_terms')}</span>
                    </label>
                    <InputError message={errors.accept_terms} />
                    <label className="flex items-start gap-3 text-sm">
                        <Checkbox checked={data.accept_privacy} onClick={() => setData('accept_privacy', !data.accept_privacy)} className="mt-0.5" />
                        <span>{t('auth.accept_privacy')}</span>
                    </label>
                    <InputError message={errors.accept_privacy} />
                    <label className="text-muted-foreground flex items-start gap-3 text-sm">
                        <Checkbox
                            checked={data.marketing_opt_in}
                            onClick={() => setData('marketing_opt_in', !data.marketing_opt_in)}
                            className="mt-0.5"
                        />
                        <span>{t('auth.marketing_opt_in')}</span>
                    </label>
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    {t('nav.register')}
                </Button>

                <div className="text-muted-foreground text-center text-sm">
                    {t('auth.have_account')} <TextLink href={route('login')}>{t('nav.login')}</TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}
