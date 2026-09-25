import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function Index({ tickets }) {
    const statusLabels = {
        waiting_for_admin: 'Waiting for Admin',
        waiting_for_customer: 'Waiting for Customer',
        resolved: 'Resolved',
        closed: 'Closed',
    };

    const statusColors = {
        waiting_for_admin:
            'border-amber-500/30 bg-amber-500/10 text-amber-300',
        waiting_for_customer:
            'border-blue-500/30 bg-blue-500/10 text-blue-300',
        resolved:
            'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        closed:
            'border-gray-600 bg-gray-800 text-gray-300',
    };

    return (
        <>
            <Head title="Support Tickets" />

            <AdminLayout>
                <div>
                    <div>
                        <h1 className="text-3xl font-bold">
                            Support Tickets
                        </h1>

                        <p className="mt-2 text-gray-400">
                            View and manage customer support tickets.
                        </p>
                    </div>

                    <div className="mt-8">
                        {tickets.length === 0 ? (
                            <div className="rounded-xl border border-gray-800 bg-gray-900 p-8">
                                <h2 className="text-lg font-semibold">
                                    No support tickets
                                </h2>

                                <p className="mt-2 text-gray-400">
                                    There are currently no customer support tickets.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {tickets.map((ticket) => (
                                    <Link
                                        key={ticket.id}
                                        href={`/admin/tickets/${ticket.id}`}
                                        className="block rounded-xl border border-gray-800 bg-gray-900 p-5 transition hover:border-gray-700"
                                    >
                                        <div className="flex items-start justify-between gap-6">
                                            <div>
                                                <p className="text-sm text-gray-500">
                                                    Ticket #{ticket.id}
                                                </p>

                                                <h2 className="mt-1 text-lg font-semibold">
                                                    {ticket.subject}
                                                </h2>

                                                <div className="mt-3 text-sm text-gray-400">
                                                    <p>
                                                        Customer: {ticket.user?.name}
                                                    </p>

                                                    <p>
                                                        {ticket.user?.email}
                                                    </p>
                                                </div>
                                            </div>

                                            <span
                                                className={`rounded-full border px-3 py-1 text-sm font-medium ${
                                                    statusColors[ticket.status] ??
                                                    'border-gray-700 bg-gray-800 text-gray-300'
                                                }`}
                                            >
                                                {statusLabels[ticket.status] ??
                                                    ticket.status}
                                            </span>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </AdminLayout>
        </>
    );
}