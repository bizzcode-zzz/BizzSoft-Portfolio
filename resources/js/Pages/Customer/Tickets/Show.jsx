import { Head, Link, useForm } from '@inertiajs/react';
import CustomerLayout from '../../../Layouts/CustomerLayout';
import SecureAccessPanel from './SecureAccessPanel';

export default function Show({ ticket }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        message: '',
    });

    const statusLabels = {
        waiting_for_admin: 'Waiting for Admin',
        waiting_for_customer: 'Waiting for Customer',
        resolved: 'Resolved',
        closed: 'Closed',
    };

    const submitReply = (event) => {
        event.preventDefault();

        post(`/tickets/${ticket.id}/replies`, {
            preserveScroll: true,
            onSuccess: () => reset('message'),
        });
    };

    const isClosed = ticket.status === 'closed';

    return (
        <>
            <Head title={ticket.subject} />

            <CustomerLayout>
                <div className="max-w-4xl">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm text-gray-500">
                                Ticket #{ticket.id}
                            </p>

                            <h1 className="mt-1 text-3xl font-bold">
                                {ticket.subject}
                            </h1>

                            <div className="mt-3">
                                <span className="rounded-full border border-gray-700 bg-gray-900 px-3 py-1 text-sm text-gray-300">
                                    {statusLabels[ticket.status] ?? ticket.status}
                                </span>
                            </div>
                        </div>

                        <Link
                            href="/tickets"
                            className="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium"
                        >
                            Back to Tickets
                        </Link>
                    </div>

                    <div className="mt-8">
                        <h2 className="mb-4 text-lg font-semibold">
                            Original Message
                        </h2>

                        <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                            <p className="whitespace-pre-wrap text-gray-300">
                                {ticket.message}
                            </p>
                        </div>
                    </div>

                    <SecureAccessPanel
                        ticketId={ticket.id}
                        secureAccesses={ticket.secure_accesses ?? []}
                    />

                    <div className="mt-8">
                        <h2 className="text-xl font-semibold">
                            Conversation
                        </h2>

                        {ticket.replies.length === 0 ? (
                            <div className="mt-4 rounded-xl border border-gray-800 bg-gray-900 p-6">
                                <p className="text-gray-400">
                                    No replies yet.
                                </p>
                            </div>
                        ) : (
                            <div className="mt-4 space-y-4">
                                {ticket.replies.map((reply) => (
                                    <div
                                        key={reply.id}
                                        className="rounded-xl border border-gray-800 bg-gray-900 p-6"
                                    >
                                        <div className="mb-3">
                                            <p className="font-medium">
                                                {reply.user?.name ?? 'User'}
                                            </p>
                                        </div>

                                        <p className="whitespace-pre-wrap text-gray-300">
                                            {reply.message}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="mt-8">
                        <h2 className="text-xl font-semibold">
                            Send Follow-up
                        </h2>

                        {isClosed ? (
                            <div className="mt-4 rounded-xl border border-gray-800 bg-gray-900 p-6">
                                <p className="font-medium">
                                    This ticket is closed.
                                </p>

                                <p className="mt-2 text-sm text-gray-400">
                                    Closed tickets are read-only. Please create
                                    a new ticket if you need further assistance.
                                </p>
                            </div>
                        ) : (
                            <form
                                onSubmit={submitReply}
                                className="mt-4 rounded-xl border border-gray-800 bg-gray-900 p-6"
                            >
                                <div>
                                    <label
                                        htmlFor="reply-message"
                                        className="mb-2 block font-medium"
                                    >
                                        Message
                                    </label>

                                    <textarea
                                        id="reply-message"
                                        rows="6"
                                        value={data.message}
                                        onChange={(event) =>
                                            setData(
                                                'message',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3"
                                        placeholder="Write a follow-up..."
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
                                    className="mt-4 rounded-lg bg-white px-5 py-3 font-semibold text-black disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processing
                                        ? 'Sending...'
                                        : 'Send Reply'}
                                </button>
                            </form>
                        )}
                    </div>
                </div>
            </CustomerLayout>
        </>
    );
}