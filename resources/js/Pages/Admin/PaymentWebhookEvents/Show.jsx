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
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit',
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

export default function Show({ event }) {
    return (
        <AdminLayout>
            <Head title={`Webhook Event #${event.id}`} />

            <div className="mx-auto max-w-5xl space-y-6">
                <div>
                    <Link
                        href="/admin/payment-webhook-events"
                        className="text-sm font-medium text-gray-400 transition hover:text-white"
                    >
                        Back to Webhook Events
                    </Link>

                    <div className="mt-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="text-sm font-medium text-gray-400">
                                Payment Webhook Event
                            </p>

                            <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                                {formatLabel(event.event_type)}
                            </h1>

                            <p className="mt-2 break-all font-mono text-xs text-gray-500">
                                {event.event_id}
                            </p>
                        </div>

                        <span
                            className={`inline-flex w-fit rounded-full border px-3 py-1.5 text-sm font-semibold ${
                                statusClasses[event.status] ??
                                'border-gray-700 bg-gray-800 text-gray-300'
                            }`}
                        >
                            {formatLabel(event.status)}
                        </span>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
                    <div className="space-y-6">
                        <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="text-lg font-semibold text-white">
                                Event Information
                            </h2>

                            <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                <div className="rounded-lg border border-gray-800 bg-gray-950 p-4">
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Provider
                                    </p>

                                    <p className="mt-2 font-semibold capitalize text-gray-200">
                                        {event.provider}
                                    </p>
                                </div>

                                <div className="rounded-lg border border-gray-800 bg-gray-950 p-4">
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Event Type
                                    </p>

                                    <p className="mt-2 text-sm font-medium text-gray-200">
                                        {formatLabel(event.event_type)}
                                    </p>
                                </div>

                                <div className="rounded-lg border border-gray-800 bg-gray-950 p-4 sm:col-span-2">
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Event ID
                                    </p>

                                    <p className="mt-2 break-all font-mono text-xs text-gray-300">
                                        {event.event_id}
                                    </p>
                                </div>

                                <div className="rounded-lg border border-gray-800 bg-gray-950 p-4 sm:col-span-2">
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Provider Payment ID
                                    </p>

                                    <p className="mt-2 break-all font-mono text-xs text-gray-300">
                                        {event.provider_payment_id ?? '-'}
                                    </p>
                                </div>
                            </div>
                        </section>

                        {event.failure_message && (
                            <section className="rounded-xl border border-red-500/20 bg-red-500/5 p-6">
                                <h2 className="text-lg font-semibold text-red-300">
                                    Failure Details
                                </h2>

                                <p className="mt-4 whitespace-pre-wrap break-words text-sm leading-7 text-gray-300">
                                    {event.failure_message}
                                </p>
                            </section>
                        )}

                        <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="text-lg font-semibold text-white">
                                Metadata
                            </h2>

                            <p className="mt-2 text-sm leading-6 text-gray-400">
                                Stored webhook metadata for administrative
                                troubleshooting.
                            </p>

                            <pre className="mt-5 overflow-x-auto rounded-lg border border-gray-800 bg-gray-950 p-4 text-xs leading-6 text-gray-300">
                                {event.metadata
                                    ? JSON.stringify(event.metadata, null, 2)
                                    : 'No metadata recorded.'}
                            </pre>
                        </section>
                    </div>

                    <aside className="space-y-5">
                        <section className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="text-lg font-semibold text-white">
                                Processing Timeline
                            </h2>

                            <div className="mt-5 space-y-5">
                                <div>
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Received
                                    </p>

                                    <p className="mt-1 text-sm text-gray-300">
                                        {formatDate(event.created_at)}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Occurred
                                    </p>

                                    <p className="mt-1 text-sm text-gray-300">
                                        {formatDate(event.occurred_at)}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Processed
                                    </p>

                                    <p className="mt-1 text-sm text-gray-300">
                                        {formatDate(event.processed_at)}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Failed
                                    </p>

                                    <p className="mt-1 text-sm text-gray-300">
                                        {formatDate(event.failed_at)}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section className="rounded-xl border border-blue-500/20 bg-blue-500/5 p-5">
                            <p className="text-sm font-semibold text-blue-300">
                                Admin Diagnostic Data
                            </p>

                            <p className="mt-2 text-sm leading-6 text-gray-400">
                                These webhook details are intended for
                                administrative monitoring and payment
                                troubleshooting only.
                            </p>
                        </section>
                    </aside>
                </div>
            </div>
        </AdminLayout>
    );
}
