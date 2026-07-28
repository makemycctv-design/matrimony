import { Head } from '@inertiajs/react';
import { Printer } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import MemberLayout from '@/layouts/member-layout';
import { formatDate, formatPaise } from '@/lib/format';

interface Props {
    invoice: {
        number: string;
        issued_at: string | null;
        billing_name: string | null;
        seller_gstin: string | null;
        place_of_supply: string | null;
        plan: string | null;
        subtotal_paise: number;
        discount_paise: number;
        tax_paise: number;
        cgst_paise: number;
        sgst_paise: number;
        igst_paise: number;
        total_paise: number;
        currency: string;
    };
    company: { name: string };
}

export default function Invoice({ invoice, company }: Props) {
    return (
        <MemberLayout>
            <Head title={`Invoice ${invoice.number}`} />

            <div className="mx-auto max-w-2xl">
                <div className="mb-4 flex justify-end print:hidden">
                    <Button onClick={() => window.print()}>
                        <Printer className="size-4" /> Print / Save PDF
                    </Button>
                </div>

                <Card className="print:border-0 print:shadow-none">
                    <CardContent className="space-y-6 p-8">
                        <div className="flex items-start justify-between">
                            <div>
                                <h1 className="text-primary text-xl font-bold">{company.name}</h1>
                                <p className="text-muted-foreground text-sm">Tax Invoice</p>
                            </div>
                            <div className="text-right text-sm">
                                <p className="font-semibold">{invoice.number}</p>
                                <p className="text-muted-foreground">{formatDate(invoice.issued_at)}</p>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p className="text-muted-foreground text-xs">Billed to</p>
                                <p className="font-medium">{invoice.billing_name ?? '—'}</p>
                            </div>
                            <div className="text-right">
                                {invoice.seller_gstin && <p className="text-muted-foreground text-xs">GSTIN: {invoice.seller_gstin}</p>}
                                {invoice.place_of_supply && (
                                    <p className="text-muted-foreground text-xs">Place of supply: {invoice.place_of_supply}</p>
                                )}
                            </div>
                        </div>

                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-muted-foreground border-b text-left text-xs uppercase">
                                    <th className="py-2">Description</th>
                                    <th className="py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="border-b">
                                    <td className="py-3">{invoice.plan} membership</td>
                                    <td className="py-3 text-right">{formatPaise(invoice.subtotal_paise)}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div className="ml-auto max-w-xs space-y-1 text-sm">
                            <Row label="Subtotal" value={formatPaise(invoice.subtotal_paise)} />
                            {invoice.discount_paise > 0 && <Row label="Discount" value={'− ' + formatPaise(invoice.discount_paise)} />}
                            {invoice.igst_paise > 0 ? (
                                <Row label="IGST" value={formatPaise(invoice.igst_paise)} />
                            ) : (
                                <>
                                    <Row label="CGST" value={formatPaise(invoice.cgst_paise)} />
                                    <Row label="SGST" value={formatPaise(invoice.sgst_paise)} />
                                </>
                            )}
                            <div className="flex justify-between border-t pt-2 text-base font-bold">
                                <span>Total</span>
                                <span>{formatPaise(invoice.total_paise)}</span>
                            </div>
                        </div>

                        <p className="text-muted-foreground border-t pt-4 text-center text-xs">
                            This is a computer-generated invoice and does not require a signature.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </MemberLayout>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between">
            <span className="text-muted-foreground">{label}</span>
            <span>{value}</span>
        </div>
    );
}
