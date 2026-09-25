import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';

export default function Dashboard({
    openTickets = 0,
    unreadTicketMessages = 0,
    activeCustomizations = 0,
    unreadCustomizationMessages = 0,
    outstandingInvoices = 0,
    monthlyRevenue = 0,
    customers = 0,
    products = 0,
    orders = 0,
    pendingPayments = 0,
}) {
    const formatCurrency = (amount) =>
        new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
        }).format(amount);

    return (
        <>
            <Head title="Admin Dashboard" />

            <AdminLayout>
                <div>
                    <h1 className="text-3xl font-bold">
                        Admin Dashboard
                    </h1>

                    <p className="mt-2 text-gray-400">
                        Manage the BizzSoft platform from here.
                    </p>

                    <div className="mt-8">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-gray-500">
                            Operations
                        </h2>

                        <div className="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                                <div className="flex items-center gap-2">
                                    <span className="text-lg">🎧</span>

                                    <h3 className="font-semibold text-gray-200">
                                        Open Support Tickets
                                    </h3>
                                </div>

                                <p className="mt-3 text-2xl font-bold text-blue-400">
                                    {openTickets}
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
                                            : 'Open tickets'}
                                    </p>

                                    <Link
                                        href="/admin/tickets"
                                        className="text-sm font-medium text-blue-400 transition hover:text-blue-300"
                                    >
                                        View Tickets →
                                    </Link>
                                </div>
                            </div>

                            <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                                <div className="flex items-center gap-2">
                                    <span className="text-lg">⚙️</span>

                                    <h3 className="font-semibold text-gray-200">
                                        Active Customizations
                                    </h3>
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
                                        href="/admin/customizations"
                                        className="text-sm font-medium text-purple-400 transition hover:text-purple-300"
                                    >
                                        View Requests →
                                    </Link>
                                </div>
                            </div>

                            <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                                <div className="flex items-center gap-2">
                                    <span className="text-lg">🧾</span>

                                    <h3 className="font-semibold text-gray-200">
                                        Outstanding Invoices
                                    </h3>
                                </div>

                                <p className="mt-3 text-2xl font-bold text-amber-400">
                                    {formatCurrency(outstandingInvoices)}
                                </p>

                                <div className="mt-3 flex items-center justify-between gap-3">
                                    <p className="text-sm text-gray-500">
                                        Unpaid invoices
                                    </p>

                                    <span className="text-sm font-medium text-gray-600">
                                        Coming Soon
                                    </span>
                                </div>
                            </div>

                            <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                                <div className="flex items-center gap-2">
                                    <span className="text-lg">💰</span>

                                    <h3 className="font-semibold text-gray-200">
                                        Revenue / Payments
                                    </h3>
                                </div>

                                <p className="mt-3 text-2xl font-bold text-emerald-400">
                                    {formatCurrency(monthlyRevenue)}
                                </p>

                                <div className="mt-3 flex items-center justify-between gap-3">
                                    <p className="text-sm text-gray-500">
                                        This month
                                    </p>

                                    <span className="text-sm font-medium text-gray-600">
                                        Coming Soon
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-gray-500">
                            Business Overview
                        </h2>

                        <div className="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                                <div className="flex items-center gap-2">
                                    <span className="text-lg">👥</span>

                                    <h3 className="font-semibold text-gray-200">
                                        Customers
                                    </h3>
                                </div>

                                <p className="mt-3 text-2xl font-bold text-cyan-400">
                                    {customers}
                                </p>

                                <p className="mt-3 text-sm text-gray-500">
                                    Customer accounts
                                </p>
                            </div>

                            <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                                <div className="flex items-center gap-2">
                                    <span className="text-lg">📦</span>

                                    <h3 className="font-semibold text-gray-200">
                                        Products
                                    </h3>
                                </div>

                                <p className="mt-3 text-2xl font-bold text-indigo-400">
                                    {products}
                                </p>

                                <p className="mt-3 text-sm text-gray-500">
                                    Scripts / products
                                </p>
                            </div>

                            <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                                <div className="flex items-center gap-2">
                                    <span className="text-lg">🛒</span>

                                    <h3 className="font-semibold text-gray-200">
                                        Orders
                                    </h3>
                                </div>

                                <p className="mt-3 text-2xl font-bold text-orange-400">
                                    {orders}
                                </p>

                                <p className="mt-3 text-sm text-gray-500">
                                    Total orders
                                </p>
                            </div>

                            <div className="rounded-xl border border-dashed border-gray-700 bg-gray-900 p-5">
                                <div className="flex items-center gap-2">
                                    <span className="text-lg">💳</span>

                                    <h3 className="font-semibold text-gray-200">
                                        Pending Payments
                                    </h3>
                                </div>

                                <p className="mt-3 text-2xl font-bold text-red-400">
                                    {pendingPayments}
                                </p>

                                <p className="mt-3 text-sm text-gray-500">
                                    Awaiting payment
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </AdminLayout>
        </>
    );
}