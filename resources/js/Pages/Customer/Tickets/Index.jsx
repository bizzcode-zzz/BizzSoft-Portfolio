import { Head, Link } from '@inertiajs/react';
import CustomerLayout from '../../../Layouts/CustomerLayout';

export default function Index({ tickets }) {
    const statusLabels = {
        waiting_for_admin: 'Waiting for Admin',
        waiting_for_customer: 'Waiting for Customer',
        resolved: 'Resolved',
        closed: 'Closed',
    };

    return (
        <>
            <Head title="Support Tickets" />

            <CustomerLayout>
                <div>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-bold">
                                Support Tickets
                            </h1>

                            <p className="mt-2 text-gray-400">
                                View and manage your support tickets.
                            </p>
                        </div>

                        <Link
                            href="/tickets/create"
                            className="rounded-lg bg-white px-5 py-3 font-semibold text-black"
                        >
                            Create Ticket
                        </Link>
                    </div>

                    <div className="mt-8">
                        {tickets.length === 0 ? (
                            <div className="rounded-xl border border-gray-800 bg-gray-900 p-8">
                                <h2 className="text-lg font-semibold">
                                    No support tickets yet
                                </h2>

                                <p className="mt-2 text-gray-400">
                                    Create a ticket when you need help or support.
                                </p>

                                <Link
                                    href="/tickets/create"
                                    className="mt-5 inline-block rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium"
                                >
                                    Create Your First Ticket
                                </Link>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {tickets.map((ticket) => (
                                    <Link
                                        key={ticket.id}
                                        href={`/tickets/${ticket.id}`}
                                        className="block rounded-xl border border-gray-800 bg-gray-900 p-5 transition hover:border-gray-700"
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <p className="text-sm text-gray-500">
                                                    Ticket #{ticket.id}
                                                </p>

                                                <h2 className="mt-1 text-lg font-semibold">
                                                    {ticket.subject}
                                                </h2>
                                            </div>

                                            <span className="rounded-full border border-gray-700 px-3 py-1 text-sm text-gray-300">
                                                {statusLabels[ticket.status] ?? ticket.status}
                                            </span>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </CustomerLayout>
        </>
    );
}