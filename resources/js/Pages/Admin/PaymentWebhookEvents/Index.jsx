import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

const statusClasses = {
    processed:
        'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
    pending:
        'border-amber-500/20 bg-amber-500/10 text-amber-300',
    failed:
        'border-red-500/20 bg-red-500/10 text-red-300',
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

function formatLabel(value) {
    if (!value) {
        return '-';
    }

    return String(value)
        .replace(/[._-]+/g, ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

export default function Index({ events }) {
    const webhookEvents = events?.data ?? [];

    return (
        <AdminLayout>
            <Head title="Payment Webhook Events" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div>
                    <p className="text-sm font-medium text-gray-400">
                        Payments
                    </p>

                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                        Payment Webhook Events
                    </h1>

                    <p className="mt-2 text-sm leading-6 text-gray-400">
                        Review webhook events received from payment providers and
                        their processing status.
                    </p>
                </div>

                {webhookEvents.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-800 bg-gray-900 p-10 text-center">
                        <h2 className="text-lg font-semibold text-white">
                            No webhook events yet
                        </h2>

                        <p className="mt-2 text-sm text-gray-400">
                            Payment webhook events will appear here after they are
                            received from a payment provider.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-xl border border-gray-800 bg-gray-900">
                            <table className="w-full min-w-250 table-fixed text-left">
                                <colgroup>
                                    <col className="w-[24%]" />
                                    <col className="w-[12%]" />
                                    <col className="w-[22%]" />
                                    <col className="w-[12%]" />
                                    <col className="w-[20%]" />
                                    <col className="w-[10%]" />
                                </colgroup>

                                <thead className="border-b border-gray-800 bg-gray-950/60">
                                    <tr>
                                        <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Event
                                        </th>

                                        <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Provider
                                        </th>

                                        <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Payment ID
                                        </th>

                                        <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>

                                        <th className="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Received
                                        </th>

                                        <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-gray-800">
                                    {webhookEvents.map((event) => (
                                        <tr
                                            key={event.id}
                                            className="transition hover:bg-gray-800/40"
                                        >
                                            <td className="px-5 py-4 align-middle">
                                                <p className="text-sm font-semibold text-gray-200">
                                                    {formatLabel(event.event_type)}
                                                </p>

                                                <p className="mt-1 break-all font-mono text-xs text-gray-500">
                                                    {event.event_id}
                                                </p>
                                            </td>

                                            <td className="px-5 py-4 align-middle">
                                                <p className="text-sm font-medium capitalize text-gray-300">
                                                    {event.provider}
                                                </p>
                                            </td>

                                            <td className="px-5 py-4 align-middle">
                                                <p className="break-all font-mono text-xs text-gray-300">
                                                    {event.provider_payment_id ??
                                                        '-'}
                                                </p>
                                            </td>

                                            <td className="px-5 py-4 align-middle">
                                                <span
                                                    className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold ${
                                                        statusClasses[
                                                            event.status
                                                        ] ??
                                                        'border-gray-700 bg-gray-800 text-gray-300'
                                                    }`}
                                                >
                                                    {formatLabel(event.status)}
                                                </span>
                                            </td>

                                            <td className="px-5 py-4 align-middle">
                                                <p className="whitespace-nowrap text-xs text-gray-400">
                                                    {formatDate(
                                                        event.occurred_at ??
                                                            event.created_at,
                                                    )}
                                                </p>
                                            </td>

                                            <td className="px-5 py-4 text-right align-middle">
                                                <Link
                                                    href={`/admin/payment-webhook-events/${event.id}`}
                                                    className="text-xs font-semibold text-blue-400 transition hover:text-blue-300"
                                                >
                                                    View
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {events.last_page > 1 && (
                            <div className="flex flex-col gap-3 rounded-xl border border-gray-800 bg-gray-900 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-sm text-gray-500">
                                    Showing {events.from ?? 0} to{' '}
                                    {events.to ?? 0} of {events.total ?? 0}{' '}
                                    events
                                </p>

                                <div className="flex items-center gap-2">
                                    {events.prev_page_url ? (
                                        <Link
                                            href={events.prev_page_url}
                                            preserveScroll
                                            className="rounded-lg border border-gray-700 px-3 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                                        >
                                            Previous
                                        </Link>
                                    ) : (
                                        <span className="cursor-not-allowed rounded-lg border border-gray-800 px-3 py-2 text-sm font-medium text-gray-600">
                                            Previous
                                        </span>
                                    )}

                                    <span className="px-2 text-sm text-gray-500">
                                        Page {events.current_page} of{' '}
                                        {events.last_page}
                                    </span>

                                    {events.next_page_url ? (
                                        <Link
                                            href={events.next_page_url}
                                            preserveScroll
                                            className="rounded-lg border border-gray-700 px-3 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                                        >
                                            Next
                                        </Link>
                                    ) : (
                                        <span className="cursor-not-allowed rounded-lg border border-gray-800 px-3 py-2 text-sm font-medium text-gray-600">
                                            Next
                                        </span>
                                    )}
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
