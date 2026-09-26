import { Head, Link } from '@inertiajs/react';
import CustomerLayout from '../../../Layouts/CustomerLayout';

const paymentStatusClasses = {
    pending: 'border-amber-500/20 bg-amber-500/10 text-amber-300',
    processing: 'border-blue-500/20 bg-blue-500/10 text-blue-300',
    submitted: 'border-violet-500/20 bg-violet-500/10 text-violet-300',
    verified: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
    failed: 'border-red-500/20 bg-red-500/10 text-red-300',
    rejected: 'border-red-500/20 bg-red-500/10 text-red-300',
    cancelled: 'border-gray-700 bg-gray-800 text-gray-300',
};

export default function Order({
    order,
    verified_amount,
    remaining_amount,
    payments,
}) {
    const formatPrice = (amount, currency = 'PHP') => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency,
            minimumFractionDigits: 2,
        }).format(Number(amount ?? 0));
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

    const version = order.product_version
        ? `v${String(order.product_version).replace(/^v/i, '')}`
        : '—';

    return (
        <CustomerLayout>
            <Head title={`Payment ${order.order_number}`} />

            <div className="mx-auto max-w-4xl space-y-6">
                <div>
                    <Link
                        href={`/orders/${order.id}`}
                        className="text-sm font-medium text-gray-400 transition hover:text-white"
                    >
                        ← Back to Order
                    </Link>

                    <div className="mt-5">
                        <p className="text-sm font-medium text-gray-400">
                            Payment
                        </p>

                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                            Pay for {order.product_name}
                        </h1>

                        <p className="mt-2 text-sm leading-6 text-gray-400">
                            Order {order.order_number}
                        </p>
                    </div>
                </div>

                <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="text-sm text-gray-500">
                                Product
                            </p>

                            <h2 className="mt-1 text-xl font-semibold text-white">
                                {order.product_name}
                            </h2>

                            <p className="mt-1 text-sm text-gray-400">
                                Version {version}
                            </p>
                        </div>

                        <div className="sm:text-right">
                            <p className="text-sm text-gray-500">
                                Order Total
                            </p>

                            <p className="mt-1 text-2xl font-bold text-white">
                                {formatPrice(order.price)}
                            </p>
                        </div>
                    </div>

                    <div className="mt-6 grid gap-4 sm:grid-cols-2">
                        <div className="rounded-lg border border-gray-800 bg-gray-950 p-4">
                            <p className="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Verified Payments
                            </p>

                            <p className="mt-2 text-lg font-semibold text-emerald-300">
                                {formatPrice(verified_amount)}
                            </p>
                        </div>

                        <div className="rounded-lg border border-gray-800 bg-gray-950 p-4">
                            <p className="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Amount Due
                            </p>

                            <p className="mt-2 text-lg font-bold text-white">
                                {formatPrice(remaining_amount)}
                            </p>
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <div>
                        <h2 className="text-lg font-semibold text-white">
                            Choose Payment Method
                        </h2>

                        <p className="mt-2 text-sm leading-6 text-gray-400">
                            Available payment providers will appear here. We
                            will connect card gateways, manual payment options,
                            and future crypto providers to this shared payment
                            page.
                        </p>
                    </div>

                    <div className="mt-5 rounded-lg border border-dashed border-gray-700 bg-gray-950 p-5">
                        <p className="font-medium text-gray-200">
                            Payment gateway setup in progress
                        </p>

                        <p className="mt-2 text-sm leading-6 text-gray-500">
                            No payment will be marked successful from this page
                            until the selected provider confirms the
                            transaction.
                        </p>
                    </div>
                </section>

                {payments.length > 0 && (
                    <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                        <h2 className="text-lg font-semibold text-white">
                            Payment History
                        </h2>

                        <div className="mt-5 divide-y divide-gray-800">
                            {payments.map((payment) => (
                                <div
                                    key={payment.id}
                                    className="flex flex-col gap-4 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="font-medium text-white">
                                            {payment.payment_number}
                                        </p>

                                        <p className="mt-1 text-sm text-gray-400">
                                            {payment.provider ?? '—'}
                                            {payment.method
                                                ? ` · ${payment.method}`
                                                : ''}
                                        </p>

                                        <p className="mt-1 text-xs text-gray-500">
                                            {formatDate(payment.created_at)}
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-4 sm:text-right">
                                        <p className="font-semibold text-white">
                                            {formatPrice(
                                                payment.amount,
                                                payment.currency,
                                            )}
                                        </p>

                                        <span
                                            className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${
                                                paymentStatusClasses[
                                                    payment.status
                                                ] ??
                                                'border-gray-700 bg-gray-800 text-gray-300'
                                            }`}
                                        >
                                            {payment.status_label}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                )}

                <div className="rounded-xl border border-blue-500/20 bg-blue-500/5 p-5">
                    <p className="text-sm leading-6 text-gray-300">
                        Opening this payment page does not mean your order is
                        paid. Payment is only considered verified after the
                        payment provider or an authorized admin confirms it.
                    </p>
                </div>
            </div>
        </CustomerLayout>
    );
}