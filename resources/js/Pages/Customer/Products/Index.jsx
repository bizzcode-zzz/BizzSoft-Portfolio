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
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <h3 className="font-semibold text-white">
                                            Licenses
                                        </h3>

                                        <span className="text-xs text-gray-500">
                                            {ownedProduct.licenses?.length ?? 0} license{(ownedProduct.licenses?.length ?? 0) === 1 ? '' : 's'}
                                        </span>
                                    </div>

                                    {ownedProduct.licenses?.length > 0 ? (
                                        <div className="mt-4 space-y-3">
                                            {ownedProduct.licenses.map((license, index) => (
                                                <div
                                                    key={license.id}
                                                    className="rounded-lg border border-gray-800 bg-gray-950 p-4"
                                                >
                                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                        <div>
                                                            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                                                License #{index + 1}
                                                            </p>

                                                            <p className="mt-2 break-all font-mono text-sm text-gray-200">
                                                                {license.license_key}
                                                            </p>
                                                        </div>

                                                        <span
                                                            className={`inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
                                                                license.status === 'active'
                                                                    ? 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20'
                                                                    : license.status === 'revoked'
                                                                      ? 'bg-red-500/10 text-red-300 ring-red-500/20'
                                                                    : 'bg-gray-500/10 text-gray-300 ring-gray-500/20'
                                                            }`}
                                                        >
                                                            {license.status === 'active'
                                                                ? 'Active'
                                                                : license.status === 'revoked'
                                                                  ? 'Revoked'
                                                                  : 'Not Activated'}
                                                        </span>
                                                    </div>

                                                    <div className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                                        <div>
                                                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                                                Production Domain
                                                            </p>
                                                            <p className="mt-1 text-gray-300">
                                                                {license.production_domain ?? 'Not activated yet'}
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <p className="text-xs uppercase tracking-wide text-gray-500">
                                                                Activated
                                                            </p>
                                                            <p className="mt-1 text-gray-300">
                                                                {license.activated_at
                                                                    ? formatDate(license.activated_at)
                                                                    : 'Not activated yet'}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="mt-4 text-sm text-gray-500">
                                            No licenses have been issued yet.
                                        </p>
                                    )}
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
                                                                <div className="flex flex-wrap items-center gap-2">
                                                                    <p className="font-semibold text-white">
                                                                        v{release.version}
                                                                    </p>

                                                                    {release.is_latest && (
                                                                        <span className="inline-flex rounded-full bg-blue-500/10 px-2.5 py-1 text-xs font-semibold text-blue-300 ring-1 ring-inset ring-blue-500/20">
                                                                            Latest
                                                                        </span>
                                                                    )}
                                                                </div>

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

                                                        {release.package_replaced_at && (
                                                            <div className="mt-4 rounded-lg border border-amber-500/30 bg-amber-500/10 p-4">
                                                                <p className="text-sm font-semibold text-amber-300">
                                                                    Important Package Update
                                                                </p>

                                                                <p className="mt-1 text-sm leading-6 text-amber-100/80">
                                                                    Package updated:{' '}
                                                                    {formatDate(
                                                                        release.package_replaced_at,
                                                                    )}
                                                                </p>

                                                                <p className="mt-2 text-sm leading-6 text-amber-100/80">
                                                                    If you downloaded this release before the package update, please download it again.
                                                                </p>
                                                            </div>
                                                        )}

                                                        {(release.release_notes ||
                                                            release.upgrade_notes) && (
                                                            <div className="mt-4 space-y-4 border-t border-gray-800 pt-4">
                                                                {release.release_notes && (
                                                                    <div>
                                                                        <p className="text-xs font-semibold uppercase tracking-wide text-blue-300">
                                                                            What's New
                                                                        </p>
                                                                        <p className="mt-2 whitespace-pre-line text-sm leading-6 text-gray-300">
                                                                            {release.release_notes}
                                                                        </p>
                                                                    </div>
                                                                )}

                                                                {release.upgrade_notes && (
                                                                    <div>
                                                                        <p className="text-xs font-semibold uppercase tracking-wide text-amber-300">
                                                                            Upgrade Notes
                                                                        </p>
                                                                        <p className="mt-2 whitespace-pre-line text-sm leading-6 text-gray-400">
                                                                            {release.upgrade_notes}
                                                                        </p>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        )}

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
