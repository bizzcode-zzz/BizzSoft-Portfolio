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

export default function Show({ product }) {
    return (
        <AdminLayout>
            <Head title={product.name} />

            <div className="mx-auto max-w-5xl space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-400">
                            Scripts / Products
                        </p>

                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                            {product.name}
                        </h1>

                        <div className="mt-3 flex flex-wrap items-center gap-2">
                            <span
                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${statusClasses(
                                    product.status,
                                )}`}
                            >
                                {statusLabel(product.status)}
                            </span>

                            {product.version && (
                                <span className="rounded-full bg-gray-800 px-2.5 py-1 text-xs font-medium text-gray-300">
                                    v{product.version}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Link
                            href="/admin/products"
                            className="inline-flex items-center justify-center rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-semibold text-gray-300 transition hover:bg-gray-800 hover:text-white"
                        >
                            ← Products
                        </Link>

                        <Link
                            href={`/admin/products/${product.slug}/edit`}
                            className="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500"
                        >
                            Edit Product
                        </Link>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div className="space-y-6">
                        <div className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
                            <div className="flex min-h-72 items-center justify-center bg-gray-950">
                                {product.thumbnail_url ? (
                                    <img
                                        src={product.thumbnail_url}
                                        alt={product.name}
                                        className="h-full max-h-105 w-full object-cover"
                                    />
                                ) : (
                                    <div className="py-16 text-center">
                                        <div className="text-5xl">📦</div>

                                        <p className="mt-3 text-sm text-gray-500">
                                            No product thumbnail
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="font-semibold text-white">
                                Product Description
                            </h2>

                            {product.short_description && (
                                <p className="mt-4 text-sm font-medium leading-6 text-gray-300">
                                    {product.short_description}
                                </p>
                            )}

                            {product.description ? (
                                <div className="mt-5 whitespace-pre-line text-sm leading-7 text-gray-400">
                                    {product.description}
                                </div>
                            ) : (
                                <p className="mt-4 text-sm text-gray-500">
                                    No full description added yet.
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <p className="text-sm text-gray-500">
                                Product Price
                            </p>

                            <p className="mt-2 text-3xl font-bold text-emerald-400">
                                {formatCurrency(product.price)}
                            </p>
                        </div>

                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="font-semibold text-white">
                                Product Details
                            </h2>

                            <dl className="mt-5 space-y-4 text-sm">
                                <div>
                                    <dt className="text-gray-500">
                                        Status
                                    </dt>

                                    <dd className="mt-1 text-gray-200">
                                        {statusLabel(product.status)}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-gray-500">
                                        Version
                                    </dt>

                                    <dd className="mt-1 text-gray-200">
                                        {product.version
                                            ? `v${product.version}`
                                            : 'Not specified'}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-gray-500">
                                        Slug
                                    </dt>

                                    <dd className="mt-1 break-all text-gray-200">
                                        {product.slug}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-gray-500">
                                        Public URL
                                    </dt>

                                    <dd className="mt-1 break-all text-gray-200">
                                        /products/{product.slug}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="font-semibold text-white">
                                Live Demo
                            </h2>

                            {product.demo_url ? (
                                <>
                                    <p className="mt-3 break-all text-sm leading-6 text-gray-400">
                                        {product.demo_url}
                                    </p>

                                    <a
                                        href={product.demo_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="mt-5 inline-flex w-full items-center justify-center rounded-lg border border-blue-500/30 bg-blue-500/10 px-4 py-2.5 text-sm font-semibold text-blue-300 transition hover:bg-blue-500/20"
                                    >
                                        Open Live Demo ↗
                                    </a>
                                </>
                            ) : (
                                <p className="mt-3 text-sm leading-6 text-gray-500">
                                    No live demo URL configured.
                                </p>
                            )}
                        </div>

                        <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-6">
                            <h2 className="font-semibold text-white">
                                Product Release
                            </h2>

                            <p className="mt-3 text-sm leading-6 text-gray-500">
                                Downloadable release files will be managed in
                                the Product Releases module later.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}