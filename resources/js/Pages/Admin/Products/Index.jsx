import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

function statusLabel(status) {
    const labels = {
        draft: 'Draft',
        active: 'Active',
        inactive: 'Inactive',
    };

    return labels[status] ?? status;
}

function statusClasses(status) {
    const classes = {
        draft: 'bg-amber-500/10 text-amber-300 ring-amber-500/20',
        active: 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20',
        inactive: 'bg-gray-500/10 text-gray-300 ring-gray-500/20',
    };

    return (
        classes[status] ??
        'bg-gray-500/10 text-gray-300 ring-gray-500/20'
    );
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(value ?? 0));
}

export default function Index({ products }) {
    return (
        <AdminLayout>
            <Head title="Products" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-400">
                            Scripts / Products
                        </p>

                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                            Products
                        </h1>

                        <p className="mt-2 text-sm leading-6 text-gray-400">
                            Manage BizzSoft scripts, software products, pricing,
                            versions, and live demo links.
                        </p>
                    </div>

                    <Link
                        href="/admin/products/create"
                        className="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500"
                    >
                        + Add Product
                    </Link>
                </div>

                {products.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-10 text-center">
                        <div className="text-3xl">📦</div>

                        <h2 className="mt-3 font-semibold text-white">
                            No products yet
                        </h2>

                        <p className="mt-2 text-sm text-gray-400">
                            Create your first script or software product to
                            begin building the BizzSoft store.
                        </p>

                        <Link
                            href="/admin/products/create"
                            className="mt-5 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500"
                        >
                            Create Product
                        </Link>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
                        <div className="divide-y divide-gray-800">
                            {products.map((product) => (
                                <Link
                                    key={product.id}
                                    href={`/admin/products/${product.slug}`}
                                    className="block px-6 py-5 transition hover:bg-gray-800/70"
                                >
                                    <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="flex min-w-0 items-center gap-4">
                                            <div className="flex h-16 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-800 bg-gray-950">
                                                {product.thumbnail_url ? (
                                                    <img
                                                        src={
                                                            product.thumbnail_url
                                                        }
                                                        alt={product.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <span className="text-2xl">
                                                        📦
                                                    </span>
                                                )}
                                            </div>

                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span
                                                        className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${statusClasses(
                                                            product.status,
                                                        )}`}
                                                    >
                                                        {statusLabel(
                                                            product.status,
                                                        )}
                                                    </span>

                                                    {product.version && (
                                                        <span className="rounded-full bg-gray-800 px-2.5 py-1 text-xs font-medium text-gray-300">
                                                            v
                                                            {
                                                                product.version
                                                            }
                                                        </span>
                                                    )}
                                                </div>

                                                <h2 className="mt-2 truncate font-semibold text-white">
                                                    {product.name}
                                                </h2>

                                                <p className="mt-1 text-sm font-medium text-emerald-400">
                                                    {formatCurrency(
                                                        product.price,
                                                    )}
                                                </p>

                                                {product.short_description && (
                                                    <p className="mt-2 line-clamp-2 text-sm leading-6 text-gray-400">
                                                        {
                                                            product.short_description
                                                        }
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="shrink-0 text-right">
                                            <div className="text-sm font-medium text-gray-300">
                                                Manage product →
                                            </div>

                                            <p className="mt-1 text-xs text-gray-500">
                                                /products/{product.slug}
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}