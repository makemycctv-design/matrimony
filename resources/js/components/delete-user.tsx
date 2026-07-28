import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/**
 * Account deletion follows a privacy-friendly workflow: the member confirms
 * with their password, provides an optional reason, and the account is
 * scheduled for permanent deletion after a grace period during which they can
 * cancel by logging back in.
 */
export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, reset, errors, clearErrors } = useForm({ password: '', reason: '' });

    const requestDeletion: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('account.delete-request'), {
            preserveScroll: true,
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        clearErrors();
        reset();
    };

    return (
        <div className="space-y-6">
            <HeadingSmall title="Delete account" description="Request permanent deletion of your account and data" />
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium">This action starts a deletion request</p>
                    <p className="text-sm">
                        Your account is deactivated immediately and permanently deleted after a grace period. Log back in before then to cancel.
                    </p>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button variant="destructive">Request account deletion</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>Request account deletion?</DialogTitle>
                        <DialogDescription>
                            Enter your password to confirm. You can cancel the request by logging in again during the grace period.
                        </DialogDescription>
                        <form className="space-y-6" onSubmit={requestDeletion}>
                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    ref={passwordInput}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Current password"
                                    autoComplete="current-password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reason">Reason (optional)</Label>
                                <Input
                                    id="reason"
                                    name="reason"
                                    value={data.reason}
                                    onChange={(e) => setData('reason', e.target.value)}
                                    placeholder="Help us improve"
                                />
                                <InputError message={errors.reason} />
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button variant="secondary" onClick={closeModal}>
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button variant="destructive" disabled={processing} asChild>
                                    <button type="submit">Confirm deletion request</button>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
