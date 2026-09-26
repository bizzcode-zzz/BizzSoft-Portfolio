import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

const statusClasses = {
    pending: 'border-amber-500/20 bg-amber-500/10 text-amber-300',
    awaiting_payment: 'border-blue-500/20 bg-blue-500/10 text-blue-300',
    processing: 'border-violet-500/20 bg-violet-500/10 text-violet-300',
    completed: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
    cancelled: 'border-red-500/20 bg-red-500/10 text-red-300',
};

export default function Index({ orders }) {
    const formatPrice = (price) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2,
        }).format(Number(price ?? 0));
    };

    const formatDate = (date) => {
        if (!date) {
            return '—';
        }

        return new Intl.DateTimeFormat('en-PH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(date));
    };

    const formatVersion = (version) => {
        if (!version) {
            return '—';
        }

        const value = String(version);

        return value.toLowerCase().startsWith('v')
            ? value
            : `v${value}`;
    };

    return (
        <AdminLayout>
            <Head title="Orders" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div>
                    <p className="text-sm font-medium text-gray-400">
                        Sales
                    </p>

                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                        Orders
                    </h1>

                    <p className="mt-2 text-sm leading-6 text-gray-400">
                        Review customer product orders and manage their current
                        order status.
                    </p>
                </div>

                {orders.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-800 bg-gray-900 p-10 text-center">
                        <h2 className="text-lg font-semibold text-white">
                            No orders yet
                        </h2>

                        <p className="mt-2 text-sm text-gray-400">
                            Customer product orders will appear here once they
                            start placing orders.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-gray-800 bg-gray-900">
                        <table className="w-full min-w-245 table-fixed text-left">
                            <colgroup>
                                <col className="w-[22%]" />
                                <col className="w-[18%]" />
                                <col className="w-[16%]" />
                                <col className="w-[8%]" />
                                <col className="w-[13%]" />
                                <col className="w-[13%]" />
                                <col className="w-[10%]" />
                            </colgroup>

                            <thead className="border-b border-gray-800 bg-gray-950/60">
                                <tr>
                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Order
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Customer
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Product
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Version
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Price
                                    </th>

                                    <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Status
                                    </th>

                                    <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-gray-800">
                                {orders.map((order) => (
                                    <tr
                                        key={order.id}
                                        className="transition hover:bg-gray-800/40"
                                    >
                                        <td className="px-5 py-4 align-middle">
                                            <p className="truncate text-sm font-semibold text-white">
                                                {order.order_number}
                                            </p>

                                            <p className="mt-1 text-xs text-gray-500">
                                                {formatDate(
                                                    order.ordered_at,
                                                )}
                                            </p>
                                        </td>

                                        <td className="px-5 py-4 align-middle">
                                            <p className="truncate text-sm font-medium text-gray-200">
                                                {order.customer_name}
                                            </p>

                                            <p className="mt-1 truncate text-xs text-gray-500">
                                                {order.customer_email}
                                            </p>
                                        </td>

                                        <td className="px-5 py-4 align-middle">
                                            <p className="truncate text-sm font-medium text-gray-200">
                                                {order.product_name}
                                            </p>
                                        </td>

                                        <td className="px-5 py-4 align-middle">
                                            <p className="text-sm text-gray-300">
                                                {formatVersion(
                                                    order.product_version,
                                                )}
                                            </p>
                                        </td>

                                        <td className="px-5 py-4 align-middle">
                                            <p className="whitespace-nowrap text-sm font-semibold text-white">
                                                {formatPrice(order.price)}
                                            </p>
                                        </td>

                                        <td className="px-5 py-4 align-middle">
                                            <span
                                                className={`inline-flex whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold ${
                                                    statusClasses[
                                                        order.status
                                                    ] ??
                                                    'border-gray-700 bg-gray-800 text-gray-300'
                                                }`}
                                            >
                                                {order.status_label}
                                            </span>
                                        </td>

                                        <td className="px-5 py-4 text-right align-middle">
                                            <Link
                                                href={`/admin/orders/${order.id}`}
                                                className="inline-flex items-center justify-center rounded-lg border border-gray-700 px-3 py-2 text-sm font-semibold text-gray-300 transition hover:bg-gray-800 hover:text-white"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}