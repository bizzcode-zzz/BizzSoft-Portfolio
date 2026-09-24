import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

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
        needs_information: 'bg-orange-500/10 text-orange-300 ring-orange-500/20',
        quote_sent: 'bg-violet-500/10 text-violet-300 ring-violet-500/20',
        quote_declined: 'bg-red-500/10 text-red-300 ring-red-500/20',
        accepted: 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20',
        in_progress: 'bg-indigo-500/10 text-indigo-300 ring-indigo-500/20',
        ready_for_review: 'bg-cyan-500/10 text-cyan-300 ring-cyan-500/20',
        revision_requested: 'bg-orange-500/10 text-orange-300 ring-orange-500/20',
        completed: 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20',
        request_declined: 'bg-red-500/10 text-red-300 ring-red-500/20',
        cancelled: 'bg-gray-500/10 text-gray-300 ring-gray-500/20',
    };

    return classes[status] ?? 'bg-gray-500/10 text-gray-300 ring-gray-500/20';
}

export default function Index({ customizationRequests }) {
    return (
        <AdminLayout>
            <Head title="Customization Requests" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div>
                    <p className="text-sm font-medium text-gray-400">
                        Custom Development
                    </p>

                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                        Customization Requests
                    </h1>

                    <p className="mt-2 text-sm leading-6 text-gray-400">
                        Review and manage customization requests submitted by
                        BizzSoft customers.
                    </p>
                </div>

                {customizationRequests.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-10 text-center">
                        <h2 className="font-semibold text-white">
                            No customization requests
                        </h2>

                        <p className="mt-2 text-sm text-gray-400">
                            Customer customization requests will appear here.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
                        <div className="divide-y divide-gray-800">
                            {customizationRequests.map((request) => (
                                <Link
                                    key={request.id}
                                    href={`/admin/customizations/${request.id}`}
                                    className="block px-6 py-5 transition hover:bg-gray-800/70"
                                >
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-xs font-medium text-gray-500">
                                                    Request #{request.id}
                                                </span>

                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${statusClasses(request.status)}`}
                                                >
                                                    {statusLabel(request.status)}
                                                </span>
                                            </div>

                                            <h2 className="mt-2 truncate font-semibold text-white">
                                                {request.title}
                                            </h2>

                                            <p className="mt-1 text-sm text-gray-400">
                                                {request.user?.name}
                                                {' · '}
                                                {request.user?.email}
                                            </p>

                                            <p className="mt-2 line-clamp-2 text-sm leading-6 text-gray-400">
                                                {request.description}
                                            </p>
                                        </div>

                                        <div className="shrink-0 text-sm font-medium text-gray-300">
                                            Review request →
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}