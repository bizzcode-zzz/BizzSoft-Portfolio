import { Head } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';

export default function Dashboard() {
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

                    <div className="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="font-semibold">
                                Support Tickets
                            </h2>

                            <p className="mt-2 text-sm text-gray-400">
                                Customer support tickets will be managed here.
                            </p>
                        </div>

                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="font-semibold">
                                Scripts / Products
                            </h2>

                            <p className="mt-2 text-sm text-gray-400">
                                Products and scripts will be managed here.
                            </p>
                        </div>

                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="font-semibold">
                                Users
                            </h2>

                            <p className="mt-2 text-sm text-gray-400">
                                Customer accounts will be managed here.
                            </p>
                        </div>
                    </div>
                </div>
            </AdminLayout>
        </>
    );
}