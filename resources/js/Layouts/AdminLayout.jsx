import { Link, router, usePage } from '@inertiajs/react';

export default function AdminLayout({ children }) {
    const { adminUnread = {} } = usePage().props;

    const unreadTickets = adminUnread.tickets ?? 0;
    const unreadCustomizations = adminUnread.customizations ?? 0;

    const logout = () => {
        router.post('/logout');
    };

    return (
        <div className="min-h-screen bg-gray-950 text-white">
            <div className="flex min-h-screen">
                <aside className="w-64 border-r border-gray-800 bg-black p-6">
                    <div className="mb-8">
                        <h1 className="text-xl font-bold">
                            BizzSoft
                        </h1>

                        <p className="mt-1 text-sm text-gray-400">
                            Administration
                        </p>
                    </div>

                    <nav className="space-y-2">
                        <Link
                            href="/admin/dashboard"
                            className="block rounded-lg bg-gray-800 px-4 py-3 font-medium"
                        >
                            Dashboard
                        </Link>

                        <Link
                            href="/admin/tickets"
                            className="flex items-center justify-between rounded-lg px-4 py-3 font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                        >
                            <span>Support Tickets</span>

                            {unreadTickets > 0 && (
                                <span className="min-w-6 rounded-full bg-red-500 px-2 py-0.5 text-center text-xs font-bold text-white">
                                    {unreadTickets}
                                </span>
                            )}
                        </Link>

                        <Link
                            href="/admin/customizations"
                            className="flex items-center justify-between rounded-lg px-4 py-3 font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                        >
                            <span>Customization Requests</span>

                            {unreadCustomizations > 0 && (
                                <span className="min-w-6 rounded-full bg-red-500 px-2 py-0.5 text-center text-xs font-bold text-white">
                                    {unreadCustomizations}
                                </span>
                            )}
                        </Link>

                        <Link
                            href="/admin/products"
                            className="block rounded-lg px-4 py-3 font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                        >
                            Scripts / Products
                        </Link>

                        <Link
                            href="/admin/orders"
                            className="block rounded-lg px-4 py-3 font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                        >
                            Orders
                        </Link>

                        <div className="px-4 py-3 text-gray-500">
                            Users
                        </div>

                        <div className="px-4 py-3 text-gray-500">
                            Portfolio Projects
                        </div>

                        <div className="px-4 py-3 text-gray-500">
                            Settings
                        </div>
                    </nav>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="flex items-center justify-between border-b border-gray-800 bg-gray-900 px-8 py-5">
                        <div>
                            <p className="text-sm text-gray-400">
                                BizzSoft Administration
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={logout}
                            className="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-black"
                        >
                            Logout
                        </button>
                    </header>

                    <main className="flex-1 p-8">
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
}