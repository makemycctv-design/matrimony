import { Head, Link } from '@inertiajs/react';
import { RefreshCw, XCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';

export default function PaymentFailed() {
    return (
        <MemberLayout>
            <Head title="Payment not completed" />
            <div className="mx-auto max-w-lg">
                <Card>
                    <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
                        <div className="bg-destructive/10 text-destructive flex size-16 items-center justify-center rounded-full">
                            <XCircle className="size-9" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold">Payment not completed</h1>
                            <p className="text-muted-foreground mt-1">No amount has been charged. You can try again with a different method.</p>
                        </div>
                        <Button asChild>
                            <Link href={route('member.subscription')}>
                                <RefreshCw className="size-4" /> Try again
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </MemberLayout>
    );
}
