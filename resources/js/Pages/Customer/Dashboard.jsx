import { Head, Link } from '@inertiajs/react';
import CustomerLayout from '../../Layouts/CustomerLayout';

export default function Dashboard({
    activeTickets = 0,
    unreadTicketMessages = 0,
    activeCustomizations = 0,
    unreadCustomizationMessages = 0,
}) {
    return (
        <>
            <Head title="Customer Dashboard" />

            <CustomerLayout>
                <div>
                    <h1 className="text-3xl font-bold">
                        Customer Dashboard
                    </h1>

                    <p className="mt-2 text-gray-400">
                        Welcome to your BizzSoft customer area.
                    </p>

                    <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                            <div className="flex items-center gap-2">
                                <span className="text-lg">💳</span>

                                <h2 className="font-semibold text-gray-200">
                                    Account Balance
                                </h2>
                            </div>

                            <p className="mt-3 text-2xl font-bold text-emerald-400">
                                ₱0.00
                            </p>

                            <div className="mt-3 flex items-center justify-between gap-3">
                                <p className="text-sm text-gray-500">
                                    Available balance
                                </p>

                                <button
                                    type="button"
                                    className="text-sm font-medium text-emerald-400 transition hover:text-emerald-300"
                                >
                                    + Add Balance
                                </button>
                            </div>
                        </div>

                        <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                            <div className="flex items-center gap-2">
                                <span className="text-lg">📄</span>

                                <h2 className="font-semibold text-gray-200">
                                    Invoice
                                </h2>
                            </div>

                            <p className="mt-3 text-2xl font-bold text-amber-400">
                                ₱0.00
                            </p>

                            <div className="mt-3 flex items-center justify-between gap-3">
                                <p className="text-sm text-gray-500">
                                    No outstanding invoice
                                </p>

                                <button
                                    type="button"
                                    className="text-sm font-medium text-amber-400 transition hover:text-amber-300"
                                >
                                    View Invoice →
                                </button>
                            </div>
                        </div>

                        <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                            <div className="flex items-center gap-2">
                                <span className="text-lg">🎧</span>

                                <h2 className="font-semibold text-gray-200">
                                    Active Support Tickets
                                </h2>
                            </div>

                            <p className="mt-3 text-2xl font-bold text-blue-400">
                                {activeTickets}
                            </p>

                            <div className="mt-3 flex items-center justify-between gap-3">
                                <p
                                    className={
                                        unreadTicketMessages > 0
                                            ? 'text-sm font-medium text-red-400'
                                            : 'text-sm text-gray-500'
                                    }
                                >
                                    {unreadTicketMessages > 0
                                        ? `${unreadTicketMessages} new ${
                                              unreadTicketMessages === 1
                                                  ? 'message'
                                                  : 'messages'
                                          }`
                                        : 'Active tickets'}
                                </p>

                                <Link
                                    href="/tickets"
                                    className="text-sm font-medium text-blue-400 transition hover:text-blue-300"
                                >
                                    View Tickets →
                                </Link>
                            </div>
                        </div>

                        <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                            <div className="flex items-center gap-2">
                                <span className="text-lg">⚙️</span>

                                <h2 className="font-semibold text-gray-200">
                                    Active Customizations
                                </h2>
                            </div>

                            <p className="mt-3 text-2xl font-bold text-purple-400">
                                {activeCustomizations}
                            </p>

                            <div className="mt-3 flex items-center justify-between gap-3">
                                <p
                                    className={
                                        unreadCustomizationMessages > 0
                                            ? 'text-sm font-medium text-red-400'
                                            : 'text-sm text-gray-500'
                                    }
                                >
                                    {unreadCustomizationMessages > 0
                                        ? `${unreadCustomizationMessages} new ${
                                              unreadCustomizationMessages === 1
                                                  ? 'message'
                                                  : 'messages'
                                          }`
                                        : 'Active requests'}
                                </p>

                                <Link
                                    href="/customizations"
                                    className="text-sm font-medium text-purple-400 transition hover:text-purple-300"
                                >
                                    View Requests →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </CustomerLayout>
        </>
    );
}