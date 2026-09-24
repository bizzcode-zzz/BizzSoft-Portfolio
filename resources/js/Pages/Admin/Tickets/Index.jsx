import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function Index({ tickets }) {
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

                                            <span className="rounded-full border border-gray-700 px-3 py-1 text-sm capitalize text-gray-300">
                                                {ticket.status}
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