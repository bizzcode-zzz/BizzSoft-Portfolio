import { Head, Link } from '@inertiajs/react';
import CustomerLayout from '../../../Layouts/CustomerLayout';

const statusClasses = {
    pending: 'border-amber-500/20 bg-amber-500/10 text-amber-300',
    awaiting_payment: 'border-blue-500/20 bg-blue-500/10 text-blue-300',
    processing: 'border-violet-500/20 bg-violet-500/10 text-violet-300',
    completed: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
    cancelled: 'border-red-500/20 bg-red-500/10 text-red-300',
};

export default function Show({ order }) {
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
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(date));
    };

    const version = order.product_version
        ? `v${String(order.product_version).replace(/^v/i, '')}`
        : '—';

    return (
        <CustomerLayout>
            <Head title={`Order ${order.order_number}`} />

            <div className="mx-auto max-w-4xl space-y-6">
                <div>
                    <Link
                        href="/orders"
                        className="text-sm font-medium text-gray-400 transition hover:text-white"
                    >
                        ← Back to My Orders
                    </Link>

                    <div className="mt-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="text-sm font-medium text-gray-400">
                                Customer Order
                            </p>

                            <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                                {order.order_number}
                            </h1>

                            <p className="mt-2 text-sm text-gray-500">
                                Ordered {formatDate(order.ordered_at)}
                            </p>
                        </div>

                        <span
                            className={`inline-flex w-fit rounded-full border px-3 py-1.5 text-sm font-semibold ${
                                statusClasses[order.status] ??
                                'border-gray-700 bg-gray-800 text-gray-300'
                            }`}
                        >
                            {order.status_label}
                        </span>
                    </div>
                </div>

                <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <div>
                        <p className="text-sm font-medium text-gray-500">
                            Product
                        </p>

                        <h2 className="mt-1 text-xl font-semibold text-white">
                            {order.product_name}
                        </h2>
                    </div>

                    <div className="mt-6 grid gap-4 sm:grid-cols-2">
                        <div className="rounded-lg border border-gray-800 bg-gray-950 p-4">
                            <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                Version
                            </p>

                            <p className="mt-2 font-semibold text-gray-200">
                                {version}
                            </p>
                        </div>

                        <div className="rounded-lg border border-gray-800 bg-gray-950 p-4">
                            <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                Order Price
                            </p>

                            <p className="mt-2 text-lg font-bold text-white">
                                {formatPrice(order.price)}
                            </p>
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <h2 className="text-lg font-semibold text-white">
                        Order Status
                    </h2>

                    <div className="mt-5 space-y-4">
                        <div className="flex gap-4">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-500/10 text-sm font-bold text-blue-400">
                                1
                            </div>

                            <div>
                                <p className="font-medium text-white">
                                    Order Submitted
                                </p>

                                <p className="mt-1 text-sm leading-6 text-gray-400">
                                    Your product order has been recorded and is
                                    waiting for the next step.
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-800 text-sm font-bold text-gray-500">
                                2
                            </div>

                            <div>
                                <p className="font-medium text-gray-300">
                                    Payment
                                </p>

                                <p className="mt-1 text-sm leading-6 text-gray-500">
                                    Payment instructions and verification will
                                    be handled separately.
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-800 text-sm font-bold text-gray-500">
                                3
                            </div>

                            <div>
                                <p className="font-medium text-gray-300">
                                    Product Delivery
                                </p>

                                <p className="mt-1 text-sm leading-6 text-gray-500">
                                    Secure product access will only be provided
                                    after payment is verified and ownership is
                                    granted.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {order.notes && (
                    <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                        <h2 className="text-lg font-semibold text-white">
                            Notes
                        </h2>

                        <p className="mt-4 whitespace-pre-line text-sm leading-7 text-gray-300">
                            {order.notes}
                        </p>
                    </section>
                )}

                <div className="rounded-xl border border-blue-500/20 bg-blue-500/5 p-5">
                    <p className="text-sm leading-6 text-gray-300">
                        Creating an order does not mean the product is paid for
                        or owned yet. Payment verification and secure delivery
                        will be handled in later steps.
                    </p>
                </div>
            </div>
        </CustomerLayout>
    );
}