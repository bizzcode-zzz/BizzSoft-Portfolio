import {
    Head,
    Link,
    useForm,
} from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

const statusClasses = {
    pending: 'border-amber-500/20 bg-amber-500/10 text-amber-300',
    awaiting_payment: 'border-blue-500/20 bg-blue-500/10 text-blue-300',
    processing: 'border-violet-500/20 bg-violet-500/10 text-violet-300',
    completed: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
    cancelled: 'border-red-500/20 bg-red-500/10 text-red-300',
};

export default function Show({ order, statuses }) {
    const form = useForm({
        status: order.status,
    });

    const recovery = useForm({ reason: '' });

    const formatPrice = (price) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
        }).format(Number(price ?? 0));
    };

    const formatDate = (date) => {
        if (!date) {
            return '-';
        }

        return new Intl.DateTimeFormat('en-PH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(date));
    };

    const formatVersion = (version) => {
        if (!version) {
            return '-';
        }

        const value = String(version);

        return value.toLowerCase().startsWith('v')
            ? value
            : `v${value}`;
    };

    const submitStatus = (event) => {
        event.preventDefault();

        form.patch(
            `/admin/orders/${order.id}/status`,
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <AdminLayout>
            <Head title={`Order ${order.order_number}`} />

            <div className="mx-auto max-w-5xl space-y-6">
                <div>
                    <Link
                        href="/admin/orders"
                        className="text-sm font-medium text-gray-400 transition hover:text-white"
                    >
                        Back to Orders
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

                {order.payment_review?.payment_hold && (
                    <section className="rounded-xl border border-red-500/30 bg-red-500/10 p-6 text-red-200">
                        This purchase has an active payment hold. Completion and late-payment recovery are blocked.
                        <Link className="ml-2 underline" href="/admin/payment-adjustments">Review payment adjustments</Link>
                    </section>
                )}
                {order.payment_review?.needs_review && (
                    <section className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-6">
                        <h2 className="text-lg font-semibold text-amber-300">
                            Cancelled order has verified payment
                        </h2>
                        <p className="mt-2 text-sm text-gray-300">
                            Verified: {formatPrice(order.payment_review.verified_amount)}.
                            Review the cancellation and any replacement order with the customer.
                            Recovery moves this order to Processing; completion and access require a separate admin action.
                        </p>
                        {order.payment_review.can_recover ? (
                            <form
                                className="mt-4 space-y-3"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    recovery.post('/admin/orders/' + order.id + '/recover-payment', {
                                        preserveScroll: true,
                                    });
                                }}
                            >
                                <label htmlFor="recovery-reason" className="block text-sm text-gray-300">
                                    Reason for recovery (saved in order notes)
                                </label>
                                <textarea
                                    id="recovery-reason"
                                    required
                                    minLength={3}
                                    maxLength={1000}
                                    value={recovery.data.reason}
                                    onChange={(event) => recovery.setData('reason', event.target.value)}
                                    className="w-full rounded-lg border border-gray-700 bg-gray-950 p-3 text-white"
                                />
                                {recovery.errors.reason && (
                                    <p className="text-sm text-red-400">{recovery.errors.reason}</p>
                                )}
                                <button
                                    type="submit"
                                    disabled={recovery.processing}
                                    className="rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white disabled:opacity-50"
                                >
                                    Recover to Processing
                                </button>
                            </form>
                        ) : (
                            <p className="mt-3 text-sm text-amber-300">
                                Payment is not fully verified. Review the payment before recovery.
                            </p>
                        )}
                    </section>
                )}

                <div className="grid gap-6 lg:grid-cols-[1fr_340px]">
                    <div className="space-y-6">
                        {/* Customer */}

                        <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="text-lg font-semibold text-white">
                                Customer
                            </h2>

                            <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                <div className="rounded-lg border border-gray-800 bg-gray-950 p-4">
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Name
                                    </p>

                                    <p className="mt-2 font-semibold text-gray-200">
                                        {order.customer.name}
                                    </p>
                                </div>

                                <div className="rounded-lg border border-gray-800 bg-gray-950 p-4">
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Email
                                    </p>

                                    <p className="mt-2 break-all text-sm font-medium text-gray-200">
                                        {order.customer.email}
                                    </p>
                                </div>
                            </div>
                        </section>

                        {/* Product Snapshot */}

                        <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <div>
                                <p className="text-sm font-medium text-gray-500">
                                    Product Snapshot
                                </p>

                                <h2 className="mt-1 text-xl font-semibold text-white">
                                    {order.product_name}
                                </h2>

                                <p className="mt-2 text-sm leading-6 text-gray-400">
                                    These values represent the product at the
                                    time the customer placed the order.
                                </p>
                            </div>

                            <div className="mt-6 grid gap-4 sm:grid-cols-2">
                                <div className="rounded-lg border border-gray-800 bg-gray-950 p-4">
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Version
                                    </p>

                                    <p className="mt-2 font-semibold text-gray-200">
                                        {formatVersion(
                                            order.product_version,
                                        )}
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

                        {/* Notes */}

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
                    </div>

                    {/* Status Management */}

                    <aside className="space-y-5">
                        <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="text-lg font-semibold text-white">
                                Order Status
                            </h2>

                            <p className="mt-2 text-sm leading-6 text-gray-400">
                                Update the operational status of this order.
                            </p>

                            {statuses.length > 1 ? (
                            <form
                                onSubmit={submitStatus}
                                className="mt-5 space-y-4"
                            >
                                <div>
                                    <label
                                        htmlFor="status"
                                        className="text-sm font-medium text-gray-300"
                                    >
                                        Status
                                    </label>

                                    <select
                                        id="status"
                                        value={form.data.status}
                                        onChange={(event) =>
                                            form.setData(
                                                'status',
                                                event.target.value,
                                            )
                                        }
                                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition focus:border-blue-500"
                                    >
                                        {statuses.map((status) => (
                                            <option
                                                key={status.value}
                                                value={status.value}
                                            >
                                                {status.label}
                                            </option>
                                        ))}
                                    </select>

                                    {form.errors.status && (
                                        <p className="mt-2 text-sm text-red-400">
                                            {form.errors.status}
                                        </p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={
                                        form.processing ||
                                        form.data.status === order.status
                                    }
                                    className="w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {form.processing
                                        ? 'Updating...'
                                        : 'Update Status'}
                                </button>
                            </form>
                            ) : (
                                <div className="mt-5 rounded-lg border border-gray-800 bg-gray-950 p-4">
                                    <p className="text-sm font-medium text-gray-200">
                                        {order.status_label}
                                    </p>
                                    <p className="mt-1 text-sm leading-6 text-gray-500">
                                        No standard status changes are available
                                        for this order.
                                    </p>
                                </div>
                            )}
                        </section>

                        <section className="rounded-xl border border-amber-500/20 bg-amber-500/5 p-5">
                            <p className="text-sm font-semibold text-amber-300">
                                  Order Status &amp; Fulfillment
                              </p>

                              <p className="mt-2 text-sm leading-6 text-gray-400">
                                  Standard status changes only update the
                                  operational status. Selecting Completed
                                  requires fully verified payment and, when
                                  successful, grants product ownership and
                                  issues the customer license. It does not
                                  change the customer&apos;s account balance.
                              </p>
                        </section>
                    </aside>
                </div>
            </div>
        </AdminLayout>
    );
}
