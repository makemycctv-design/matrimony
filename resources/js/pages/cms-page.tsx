import { Head } from '@inertiajs/react';

import PublicLayout from '@/layouts/public-layout';

interface Props {
    page: { title: string; body: string | null };
    seo: { title: string; description: string | null };
}

export default function CmsPage({ page, seo }: Props) {
    return (
        <PublicLayout>
            <Head title={seo.title}>{seo.description && <meta name="description" content={seo.description} />}</Head>

            <article className="mx-auto w-full max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
                <h1 className="mb-8 text-3xl font-bold tracking-tight">{page.title}</h1>
                <div className="prose prose-neutral text-muted-foreground dark:prose-invert max-w-none whitespace-pre-line">{page.body}</div>
            </article>
        </PublicLayout>
    );
}
