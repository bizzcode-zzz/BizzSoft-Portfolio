import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
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
    checkout_provider,
    payments,
}) {
    const [startingCheckout, setStartingCheckout] = useState(false);
    const [acceptedTerms, setAcceptedTerms] = useState(false);

    const formatPrice = (amount, currency = 'USD') => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency,
            minimumFractionDigits: 2,
        }).format(Number(amount ?? 0));
    };

    const formatDate = (date) => {
        if (!date) {
            return '-';
        }

        return new Intl.DateTimeFormat('en-PH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(date));
    };

    const startCheckout = () => {
        if (startingCheckout) {
            return;
        }

        router.post(
            `/orders/${order.id}/payment/checkout`,
            { accepted_terms: acceptedTerms },
            {
                preserveScroll: true,
                onStart: () => {
                    setStartingCheckout(true);
                },
                onFinish: () => {
                    setStartingCheckout(false);
                },
            },
        );
    };

    const version = order.product_version
        ? `v${String(order.product_version).replace(/^v/i, '')}`
        : '-';

    const providerLabel =
        checkout_provider === 'paddle'
            ? 'Paddle'
            : checkout_provider ?? 'Payment Provider';

    return (
        <CustomerLayout>
            <Head title={`Payment ${order.order_number}`} />

            <div className="mx-auto max-w-4xl space-y-6">
                <div>
                    <Link
                        href={`/orders/${order.id}`}
                        className="text-sm font-medium text-gray-400 transition hover:text-white"
                    >
                        ? Back to Order
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
                            Continue to our secure global checkout. Your order
                            will only be considered paid after the payment
                            provider confirms the transaction.
                        </p>
                    </div>

                    <div className="mt-5 rounded-xl border border-gray-800 bg-gray-950 p-5">
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p className="font-semibold text-white">
                                    Pay with {providerLabel}
                                </p>

                                <p className="mt-1 text-sm leading-6 text-gray-500">
                                    Secure checkout for your remaining balance
                                    of {formatPrice(remaining_amount)}.
                                </p>
                            </div>

                            <div className="flex shrink-0 flex-col items-start gap-3 sm:items-end">
                                <label className="flex max-w-sm items-start gap-3 text-sm leading-6 text-gray-400 sm:max-w-xs">
                                    <input
                                        type="checkbox"
                                        checked={acceptedTerms}
                                        onChange={(event) =>
                                            setAcceptedTerms(event.target.checked)
                                        }
                                        className="mt-1 h-4 w-4 shrink-0 rounded border-gray-600 bg-gray-900 text-blue-500 focus:ring-blue-500"
                                    />

                                    <span>
                                        I agree to the{' '}
                                        <a
                                            href="/terms"
                                            target="_blank"
                                            rel="noreferrer"
                                            className="font-medium text-blue-300 hover:text-blue-200"
                                        >
                                            Terms & Conditions
                                        </a>{' '}
                                        and{' '}
                                        <a
                                            href="/refund-policy"
                                            target="_blank"
                                            rel="noreferrer"
                                            className="font-medium text-blue-300 hover:text-blue-200"
                                        >
                                            Refund Policy
                                        </a>
                                        .
                                    </span>
                                </label>

                                <button
                                    type="button"
                                    onClick={startCheckout}
                                    disabled={startingCheckout || !acceptedTerms}
                                    className={`inline-flex shrink-0 items-center justify-center rounded-lg px-5 py-3 text-sm font-semibold text-white transition ${
                                        startingCheckout || !acceptedTerms
                                            ? 'cursor-not-allowed bg-blue-800 opacity-50'
                                            : 'bg-blue-600 hover:bg-blue-500'
                                    }`}
                                >
                                    {startingCheckout
                                        ? 'Starting Checkout...'
                                        : `Pay with ${providerLabel}`}
                                </button>
                            </div>
                        </div>
                    </div>

                    <p className="mt-4 text-xs leading-5 text-gray-500">
                        You will be transferred to the selected provider's
                        secure checkout. Returning from checkout does not by
                        itself mark the payment as successful.
                    </p>
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
                                            {payment.provider ?? '-'}
                                            {payment.method
                                                ? ` \u00B7 ${payment.method}`
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
                        Opening checkout does not mean your order is paid.
                        Payment is only considered verified after the payment
                        provider confirms it through our server-side payment
                        verification flow.
                    </p>
                </div>
            </div>
        </CustomerLayout>
    );
}
