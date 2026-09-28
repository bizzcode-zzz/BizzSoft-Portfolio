import { Head } from '@inertiajs/react';
import CustomerLayout from '../../../Layouts/CustomerLayout';

function formatFileSize(bytes) {
    const size = Number(bytes ?? 0);

    if (size < 1024) {
        return `${size} B`;
    }

    if (size < 1024 * 1024) {
        return `${(size / 1024).toFixed(1)} KB`;
    }

    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-PH', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function Index({ ownedProducts = [] }) {
    return (
        <CustomerLayout>
            <Head title="My Products" />

            <div className="mx-auto max-w-5xl space-y-6">
                <div>
                    <p className="text-sm font-medium text-gray-400">
                        Scripts / Products
                    </p>

                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                        My Products
                    </h1>

                    <p className="mt-2 text-sm leading-6 text-gray-500">
                        Products that have been granted to your account after
                        verified payment and completed fulfillment.
                    </p>
                </div>

                {ownedProducts.length > 0 ? (
                    <div className="space-y-6">
                        {ownedProducts.map((ownedProduct) => (
                            <section
                                key={ownedProduct.ownership_id}
                                className="rounded-xl border border-gray-800 bg-gray-900 p-6"
                            >
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h2 className="text-xl font-semibold text-white">
                                            {ownedProduct.product.name}
                                        </h2>

                                        <p className="mt-1 text-sm text-gray-500">
                                            Product version:{' '}
                                            {ownedProduct.product.version
                                                ? `v${ownedProduct.product.version}`
                                                : 'Not specified'}
                                        </p>
                                    </div>

                                    <div className="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300 ring-1 ring-inset ring-emerald-500/20">
                                        Owned
                                    </div>
                                </div>

                                <div className="mt-5 rounded-lg border border-gray-800 bg-gray-950 p-4">
                                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Ownership Granted
                                    </p>

                                    <p className="mt-2 text-sm text-gray-300">
                                        {formatDate(ownedProduct.granted_at)}
                                    </p>
                                </div>

                                <div className="mt-6">
                                    <h3 className="font-semibold text-white">
                                        Available Releases
                                    </h3>

                                    {ownedProduct.product.releases.length > 0 ? (
                                        <div className="mt-4 space-y-3">
                                            {ownedProduct.product.releases.map(
                                                (release) => (
                                                    <div
                                                        key={release.id}
                                                        className="rounded-lg border border-gray-800 bg-gray-950 p-4"
                                                    >
                                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                            <div>
                                                                <p className="font-semibold text-white">
                                                                    v
                                                                    {
                                                                        release.version
                                                                    }
                                                                </p>

                                                                <p className="mt-1 break-all text-sm text-gray-500">
                                                                    {
                                                                        release.original_name
                                                                    }
                                                                </p>
                                                            </div>

                                                            <span className="inline-flex w-fit rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-300 ring-1 ring-inset ring-emerald-500/20">
                                                                Published
                                                            </span>
                                                        </div>

                                                        <div className="mt-4 space-y-1 text-xs text-gray-500">
                                                            <p>
                                                                Size:{' '}
                                                                {formatFileSize(
                                                                    release.file_size,
                                                                )}
                                                            </p>

                                                            <p>
                                                                Released:{' '}
                                                                {formatDate(
                                                                    release.released_at,
                                                                )}
                                                            </p>
                                                        </div>

                                                        <a
                                                            href={`/my-products/${ownedProduct.product.slug}/releases/${release.id}/download`}
                                                            className="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500"
                                                        >
                                                            Download Release
                                                        </a>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    ) : (
                                        <p className="mt-4 text-sm leading-6 text-gray-500">
                                            You own this product, but there are
                                            no published releases available yet.
                                        </p>
                                    )}
                                </div>
                            </section>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-xl border border-gray-800 bg-gray-900 p-8 text-center">
                        <h2 className="font-semibold text-white">
                            No owned products yet
                        </h2>

                        <p className="mt-2 text-sm leading-6 text-gray-500">
                            Products will appear here after payment is verified,
                            fulfillment is completed, and ownership is granted.
                        </p>
                    </div>
                )}
            </div>
        </CustomerLayout>
    );
}
