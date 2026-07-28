import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, FileText } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';
import { formatPaise } from '@/lib/format';

interface Props {
    payment: {
        uuid: string;
        amount: number;
        plan: string | null;
        status: string;
        invoice: { uuid: string; number: string } | null;
    };
}

export default function PaymentSuccess({ payment }: Props) {
    return (
        <MemberLayout>
            <Head title="Payment successful" />
            <div className="mx-auto max-w-lg">
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
                        <div className="bg-secondary/15 text-secondary flex size-16 items-center justify-center rounded-full">
                            <CheckCircle2 className="size-9" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold">Payment successful!</h1>
                            <p className="text-muted-foreground mt-1">
                                Your {payment.plan} membership is now active. We've charged {formatPaise(payment.amount)}.
                            </p>
                        </div>
                        <div className="flex flex-wrap justify-center gap-3 pt-2">
                            <Button asChild>
                                <Link href={route('member.subscription')}>View subscription</Link>
                            </Button>
                            {payment.invoice && (
                                <Button variant="outline" asChild>
                                    <Link href={route('member.billing.invoice', { invoice: payment.invoice.uuid })}>
                                        <FileText className="size-4" /> Invoice {payment.invoice.number}
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </MemberLayout>
    );
}
