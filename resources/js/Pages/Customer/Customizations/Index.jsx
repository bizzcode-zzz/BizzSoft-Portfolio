import { Head, Link } from '@inertiajs/react';
import CustomerLayout from '../../../Layouts/CustomerLayout';

function statusLabel(status) {
    const labels = {
        submitted: 'Submitted',
        under_review: 'Under Review',
        needs_information: 'Needs Information',
        quote_sent: 'Quote Sent',
        quote_declined: 'Quote Declined',
        accepted: 'Accepted',
        in_progress: 'In Progress',
        ready_for_review: 'Ready for Review',
        revision_requested: 'Revision Requested',
        completed: 'Completed',
        request_declined: 'Request Declined',
        cancelled: 'Cancelled',
    };

    return labels[status] ?? status;
}

function statusClasses(status) {
    const classes = {
        submitted: 'bg-blue-500/10 text-blue-300 ring-blue-500/20',
        under_review: 'bg-amber-500/10 text-amber-300 ring-amber-500/20',
        needs_information:
            'bg-orange-500/10 text-orange-300 ring-orange-500/20',
        quote_sent: 'bg-violet-500/10 text-violet-300 ring-violet-500/20',
        quote_declined: 'bg-red-500/10 text-red-300 ring-red-500/20',
        accepted: 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20',
        in_progress: 'bg-indigo-500/10 text-indigo-300 ring-indigo-500/20',
        ready_for_review: 'bg-cyan-500/10 text-cyan-300 ring-cyan-500/20',
        revision_requested:
            'bg-orange-500/10 text-orange-300 ring-orange-500/20',
        completed:
            'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20',
        request_declined: 'bg-red-500/10 text-red-300 ring-red-500/20',
        cancelled: 'bg-gray-500/10 text-gray-300 ring-gray-500/20',
    };

    return (
        classes[status] ??
        'bg-gray-500/10 text-gray-300 ring-gray-500/20'
    );
}

export default function Index({ customizationRequests }) {
    return (
        <CustomerLayout>
            <Head title="Customization Requests" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-500">
                            Custom Development
                        </p>

                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                            My Customization Requests
                        </h1>

                        <p className="mt-2 max-w-2xl text-sm leading-6 text-gray-400">
                            Submit and track custom development requests for your
                            BizzSoft products and projects.
                        </p>
                    </div>

                    <Link
                        href="/customizations/create"
                        className="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-black transition hover:bg-gray-200"
                    >
                        New Request
                    </Link>
                </div>

                {customizationRequests.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 px-6 py-12 text-center">
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-800 text-xl text-gray-300">
                            ⚙
                        </div>

                        <h2 className="mt-4 text-base font-semibold text-white">
                            No customization requests yet
                        </h2>

                        <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-400">
                            Need a feature, workflow change, integration, or
                            other custom development? Start a request and
                            describe what you need.
                        </p>

                        <Link
                            href="/customizations/create"
                            className="mt-5 inline-flex rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-black transition hover:bg-gray-200"
                        >
                            Create Customization Request
                        </Link>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
                        <div className="divide-y divide-gray-800">
                            {customizationRequests.map((request) => (
                                <Link
                                    key={request.id}
                                    href={`/customizations/${request.id}`}
                                    className="block px-5 py-5 transition hover:bg-gray-800/70 sm:px-6"
                                >
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <span className="text-xs font-medium text-gray-500">
                                                    #{request.id}
                                                </span>

                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${statusClasses(request.status)}`}
                                                >
                                                    {statusLabel(request.status)}
                                                </span>
                                            </div>

                                            <h2 className="mt-2 truncate text-base font-semibold text-white">
                                                {request.title}
                                            </h2>

                                            <p className="mt-1 line-clamp-2 text-sm leading-6 text-gray-400">
                                                {request.description}
                                            </p>

                                             
                                        </div>

                                        <div className="shrink-0 text-right">
                                            <div className="text-sm font-medium text-gray-400 transition group-hover:text-white">
                                                View request →
                                            </div>

                                            {request.unread_messages_count > 0 && (
                                                <p className="mt-2 text-sm font-medium text-red-400">
                                                    {request.unread_messages_count}{' '}
                                                    new{' '}
                                                    {request.unread_messages_count === 1
                                                        ? 'message'
                                                        : 'messages'}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </CustomerLayout>
    );
}