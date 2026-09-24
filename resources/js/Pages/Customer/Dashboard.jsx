import { Head } from '@inertiajs/react';
import CustomerLayout from '../../Layouts/CustomerLayout';

export default function Dashboard() {
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

                    <div className="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="font-semibold">
                                Support Tickets
                            </h2>

                            <p className="mt-2 text-sm text-gray-400">
                                Your support inquiries will appear here.
                            </p>
                        </div>

                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="font-semibold">
                                Customization Requests
                            </h2>

                            <p className="mt-2 text-sm text-gray-400">
                                Your customization requests will appear here.
                            </p>
                        </div>

                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <h2 className="font-semibold">
                                Scripts / Products
                            </h2>

                            <p className="mt-2 text-sm text-gray-400">
                                Available and purchased products will appear here.
                            </p>
                        </div>
                    </div>
                </div>
            </CustomerLayout>
        </>
    );
}