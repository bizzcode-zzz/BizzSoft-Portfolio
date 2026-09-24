import { Head, Link, useForm } from '@inertiajs/react';
import CustomerLayout from '../../../Layouts/CustomerLayout';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        subject: '',
        message: '',
    });

    const submit = (event) => {
        event.preventDefault();

        post('/tickets');
    };

    return (
        <>
            <Head title="Create Support Ticket" />

            <CustomerLayout>
                <div className="max-w-3xl">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-bold">
                                Create Support Ticket
                            </h1>

                            <p className="mt-2 text-gray-400">
                                Tell us what you need help with.
                            </p>
                        </div>

                        <Link
                            href="/tickets"
                            className="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium"
                        >
                            Back to Tickets
                        </Link>
                    </div>

                    <form
                        onSubmit={submit}
                        className="mt-8 space-y-6 rounded-xl border border-gray-800 bg-gray-900 p-6"
                    >
                        <div>
                            <label
                                htmlFor="subject"
                                className="mb-2 block font-medium"
                            >
                                Subject
                            </label>

                            <input
                                id="subject"
                                type="text"
                                value={data.subject}
                                onChange={(event) =>
                                    setData('subject', event.target.value)
                                }
                                className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3"
                                placeholder="What do you need help with?"
                            />

                            {errors.subject && (
                                <p className="mt-2 text-sm text-red-400">
                                    {errors.subject}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="message"
                                className="mb-2 block font-medium"
                            >
                                Message
                            </label>

                            <textarea
                                id="message"
                                rows="8"
                                value={data.message}
                                onChange={(event) =>
                                    setData('message', event.target.value)
                                }
                                className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3"
                                placeholder="Describe your issue..."
                            />

                            {errors.message && (
                                <p className="mt-2 text-sm text-red-400">
                                    {errors.message}
                                </p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-white px-5 py-3 font-semibold text-black disabled:opacity-50"
                        >
                            {processing ? 'Creating Ticket...' : 'Create Ticket'}
                        </button>
                    </form>
                </div>
            </CustomerLayout>
        </>
    );
}