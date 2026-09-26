import { Head, Link } from '@inertiajs/react';
import CustomerLayout from '../../../Layouts/CustomerLayout';

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

    return (
        <CustomerLayout>
            <Head title="My Orders" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div>
                    <p className="text-sm font-medium text-gray-400">
                        Customer Orders
                    </p>

                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                        My Orders
                    </h1>

                    <p className="mt-2 text-sm leading-6 text-gray-400">
                        Review your product orders and their current status.
                    </p>
                </div>

                {orders.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-800 bg-gray-900 p-10 text-center">
                        <h2 className="text-lg font-semibold text-white">
                            No orders yet
                        </h2>

                        <p className="mt-2 text-sm text-gray-400">
                            Your product orders will appear here after you place
                            an order.
                        </p>

                        <Link
                            href="/products"
                            className="mt-6 inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500"
                        >
                            Browse Products
                        </Link>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
                        <div className="hidden grid-cols-[1.2fr_1.5fr_0.8fr_0.8fr_1fr_auto] gap-4 border-b border-gray-800 px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 lg:grid">
                            <div>Order</div>
                            <div>Product</div>
                            <div>Version</div>
                            <div>Price</div>
                            <div>Status</div>
                            <div />
                        </div>

                        <div className="divide-y divide-gray-800">
                            {orders.map((order) => (
                                <div
                                    key={order.id}
                                    className="grid gap-4 px-6 py-5 lg:grid-cols-[1.2fr_1.5fr_0.8fr_0.8fr_1fr_auto] lg:items-center"
                                >
                                    <div>
                                        <p className="text-xs text-gray-500 lg:hidden">
                                            Order
                                        </p>

                                        <p className="font-semibold text-white">
                                            {order.order_number}
                                        </p>

                                        <p className="mt-1 text-xs text-gray-500">
                                            {formatDate(order.ordered_at)}
                                        </p>
                                    </div>

                                    <div>
                                        <p className="text-xs text-gray-500 lg:hidden">
                                            Product
                                        </p>

                                        <p className="text-sm font-medium text-gray-200">
                                            {order.product_name}
                                        </p>
                                    </div>

                                    <div>
                                        <p className="text-xs text-gray-500 lg:hidden">
                                            Version
                                        </p>

                                        <p className="text-sm text-gray-300">
                                            {order.product_version
                                                ? `v${String(
                                                      order.product_version,
                                                  ).replace(/^v/i, '')}`
                                                : '—'}
                                        </p>
                                    </div>

                                    <div>
                                        <p className="text-xs text-gray-500 lg:hidden">
                                            Price
                                        </p>

                                        <p className="text-sm font-semibold text-white">
                                            {formatPrice(order.price)}
                                        </p>
                                    </div>

                                    <div>
                                        <p className="text-xs text-gray-500 lg:hidden">
                                            Status
                                        </p>

                                        <span
                                            className={`mt-1 inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold lg:mt-0 ${
                                                statusClasses[order.status] ??
                                                'border-gray-700 bg-gray-800 text-gray-300'
                                            }`}
                                        >
                                            {order.status_label}
                                        </span>
                                    </div>

                                    <div>
                                        <Link
                                            href={`/orders/${order.id}`}
                                            className="inline-flex items-center justify-center rounded-lg border border-gray-700 px-3 py-2 text-sm font-semibold text-gray-300 transition hover:bg-gray-800 hover:text-white"
                                        >
                                            View
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </CustomerLayout>
    );
}