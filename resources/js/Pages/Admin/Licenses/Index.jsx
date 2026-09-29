import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

const statusClasses = {
    active: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
    unactivated: 'border-gray-700 bg-gray-800 text-gray-300',
    revoked: 'border-red-500/20 bg-red-500/10 text-red-300',
};

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

function statusLabel(status) {
    if (status === 'active') {
        return 'Active';
    }

    if (status === 'revoked') {
        return 'Revoked';
    }

    return 'Not Activated';
}

export default function Index({ licenses = [] }) {
    const revokeLicense = (license) => {
        const reason = window.prompt(
            'Enter the reason for revoking this license:',
        );

        if (!reason?.trim()) {
            return;
        }

        const confirmed = window.confirm(
            'Revoke this license permanently? The original production domain will remain recorded.',
        );

        if (!confirmed) {
            return;
        }

        router.patch(
            `/admin/licenses/${license.id}/revoke`,
            { reason: reason.trim() },
            { preserveScroll: true },
        );
    };

    return (
        <AdminLayout>
            <Head title="Licenses" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div>
                    <p className="text-sm font-medium text-gray-400">
                        Product Licensing
                    </p>

                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                        Licenses
                    </h1>

                    <p className="mt-2 text-sm leading-6 text-gray-400">
                        Review issued single-domain product licenses and their
                        current production-domain activation.
                    </p>
                </div>

                {licenses.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-800 bg-gray-900 p-10 text-center">
                        <h2 className="text-lg font-semibold text-white">
                            No licenses issued yet
                        </h2>

                        <p className="mt-2 text-sm text-gray-400">
                            Product licenses will appear here after eligible
                            orders are completed.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-gray-800 bg-gray-900">
                        <table className="w-full min-w-300 table-fixed text-left">
                            <colgroup>
                                <col className="w-[16%]" />
                                <col className="w-[14%]" />
                                <col className="w-[13%]" />
                                <col className="w-[18%]" />
                                <col className="w-[14%]" />
                                <col className="w-[12%]" />
                                <col className="w-[7%]" />
                                <col className="w-[6%]" />
                            </colgroup>

                            <thead className="border-b border-gray-800 bg-gray-950/60">
                                <tr>
                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Customer
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Product
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Order
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        License
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Status
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Production Domain
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Activated
                                    </th>

                                    <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-gray-800">
                                {licenses.map((license) => (
                                    <>
                                        <tr
                                            key={`license-${license.id}`}
                                            className="transition hover:bg-gray-800/40"
                                        >
                                            <td className="px-5 py-4 align-middle">
                                                <p className="truncate text-sm font-medium text-gray-200">
                                                    {license.customer.name}
                                                </p>

                                                {license.customer.name !==
                                                    license.customer.email && (
                                                    <p className="mt-1 truncate text-xs text-gray-500">
                                                        {license.customer.email}
                                                    </p>
                                                )}
                                            </td>

                                            <td className="px-5 py-4 align-middle">
                                                <p className="truncate text-sm font-medium text-gray-200">
                                                    {license.product.name}
                                                </p>
                                            </td>

                                            <td className="px-5 py-4 align-middle">
                                                <Link
                                                    href={`/admin/orders/${license.order.id}`}
                                                    className="text-sm font-semibold text-blue-400 transition hover:text-blue-300"
                                                >
                                                    {license.order.order_number}
                                                </Link>
                                            </td>

                                            <td className="px-5 py-4 align-middle">
                                                <p className="break-all font-mono text-xs text-gray-300">
                                                    {license.license_key}
                                                </p>
                                            </td>

                                            <td className="px-5 py-4 align-middle">
                                                <p className="break-all text-sm text-gray-300">
                                                    {license.production_domain ??
                                                        'Not activated'}
                                                </p>
                                            </td>

                                            <td className="px-5 py-4 align-middle">
                                                <p className="whitespace-nowrap text-xs text-gray-400">
                                                    {formatDate(
                                                        license.activated_at,
                                                    )}
                                                </p>
                                            </td>

                                            <td className="px-5 py-4 align-middle">
                                                <span
                                                    className={`inline-flex whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold ${
                                                        statusClasses[
                                                            license.status
                                                        ] ??
                                                        'border-gray-700 bg-gray-800 text-gray-300'
                                                    }`}
                                                >
                                                    {statusLabel(
                                                        license.status,
                                                    )}
                                                </span>
                                            </td>

                                            <td className="px-5 py-4 text-right align-middle">
                                                {license.status !== 'revoked' ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            revokeLicense(
                                                                license,
                                                            )
                                                        }
                                                        className="inline-flex items-center justify-center rounded-lg border border-red-500/30 px-3 py-2 text-xs font-semibold text-red-300 transition hover:bg-red-500/10"
                                                    >
                                                        Revoke
                                                    </button>
                                                ) : (
                                                    <span className="text-xs font-medium text-gray-600">
                                                        Locked
                                                    </span>
                                                )}
                                            </td>
                                        </tr>

                                        {license.status === 'revoked' && (
                                            <tr
                                                key={`revocation-${license.id}`}
                                                className="bg-red-500/[0.03]"
                                            >
                                                <td
                                                    colSpan="8"
                                                    className="px-5 pb-4 pt-0"
                                                >
                                                    <div className="rounded-lg border border-red-500/15 bg-red-500/[0.04] px-4 py-3">
                                                        <div className="mb-2 flex items-center gap-2">
                                                            <span className="text-xs font-semibold uppercase tracking-wider text-red-300">
                                                                Revocation details
                                                            </span>
                                                        </div>

                                                        <div className="grid gap-3 text-xs sm:grid-cols-3">
                                                            <div>
                                                                <p className="text-gray-500">
                                                                    Reason
                                                                </p>
                                                                <p className="mt-1 text-gray-300">
                                                                    {license.revocation_reason ??
                                                                        '-'}
                                                                </p>
                                                            </div>

                                                            <div>
                                                                <p className="text-gray-500">
                                                                    Revoked by
                                                                </p>
                                                                <p className="mt-1 text-gray-300">
                                                                    {license.revoker?.email ??
                                                                        '-'}
                                                                </p>
                                                            </div>

                                                            <div>
                                                                <p className="text-gray-500">
                                                                    Revoked at
                                                                </p>
                                                                <p className="mt-1 text-gray-300">
                                                                    {formatDate(
                                                                        license.revoked_at,
                                                                    )}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
