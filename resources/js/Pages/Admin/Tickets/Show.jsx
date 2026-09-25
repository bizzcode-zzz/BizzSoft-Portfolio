import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import SecureAccessPanel from './SecureAccessPanel';

export default function Show({ ticket }) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm({
        message: '',
    });

    const {
        patch,
        processing: statusProcessing,
    } = useForm({});

    const statusLabels = {
        waiting_for_admin: 'Waiting for Admin',
        waiting_for_customer: 'Waiting for Customer',
        resolved: 'Resolved',
        closed: 'Closed',
    };

    const submitReply = (event) => {
        event.preventDefault();

        post(`/admin/tickets/${ticket.id}/replies`, {
            preserveScroll: true,
            onSuccess: () => reset('message'),
        });
    };

    const markResolved = () => {
        patch(`/admin/tickets/${ticket.id}/resolve`, {
            preserveScroll: true,
        });
    };

    const closeTicket = () => {
        patch(`/admin/tickets/${ticket.id}/close`, {
            preserveScroll: true,
        });
    };

    const canResolve =
        ticket.status === 'waiting_for_admin' ||
        ticket.status === 'waiting_for_customer';

    const isResolved = ticket.status === 'resolved';
    const isClosed = ticket.status === 'closed';

    return (
        <>
            <Head title={`Ticket #${ticket.id}`} />

            <AdminLayout>
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
                            href="/admin/tickets"
                            className="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium"
                        >
                            Back to Tickets
                        </Link>
                    </div>

                    <div className="mt-8 rounded-xl border border-gray-800 bg-gray-900 p-6">
                        <p className="text-sm text-gray-500">
                            Customer
                        </p>

                        <p className="mt-2 font-semibold">
                            {ticket.user?.name}
                        </p>

                        <p className="mt-1 text-sm text-gray-400">
                            {ticket.user?.email}
                        </p>
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

                                            <p className="text-sm text-gray-500">
                                                {reply.user?.email}
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

                    {!isClosed && (
                        <div className="mt-8">
                            <h2 className="text-xl font-semibold">
                                Ticket Actions
                            </h2>

                            <div className="mt-4 rounded-xl border border-gray-800 bg-gray-900 p-6">
                                {canResolve && (
                                    <div>
                                        <p className="text-sm text-gray-400">
                                            Mark this ticket as resolved when
                                            the support issue has been solved.
                                        </p>

                                        <button
                                            type="button"
                                            onClick={markResolved}
                                            disabled={statusProcessing}
                                            className="mt-4 rounded-lg border border-gray-600 px-5 py-3 font-semibold transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {statusProcessing
                                                ? 'Updating...'
                                                : 'Mark Resolved'}
                                        </button>
                                    </div>
                                )}

                                {isResolved && (
                                    <div>
                                        <p className="text-sm text-gray-400">
                                            This ticket is resolved. Close it
                                            when no further action is required.
                                        </p>

                                        <button
                                            type="button"
                                            onClick={closeTicket}
                                            disabled={statusProcessing}
                                            className="mt-4 rounded-lg border border-gray-600 px-5 py-3 font-semibold transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {statusProcessing
                                                ? 'Updating...'
                                                : 'Close Ticket'}
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    <div className="mt-8">
                        <h2 className="text-xl font-semibold">
                            Admin Reply
                        </h2>

                        {isClosed ? (
                            <div className="mt-4 rounded-xl border border-gray-800 bg-gray-900 p-6">
                                <p className="font-medium">
                                    This ticket is closed.
                                </p>

                                <p className="mt-2 text-sm text-gray-400">
                                    Closed tickets are read-only and cannot
                                    receive new replies.
                                </p>
                            </div>
                        ) : (
                            <form
                                onSubmit={submitReply}
                                className="mt-4 rounded-xl border border-gray-800 bg-gray-900 p-6"
                            >
                                <div>
                                    <label
                                        htmlFor="admin-reply-message"
                                        className="mb-2 block font-medium"
                                    >
                                        Message
                                    </label>

                                    <textarea
                                        id="admin-reply-message"
                                        rows="6"
                                        value={data.message}
                                        onChange={(event) =>
                                            setData(
                                                'message',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3"
                                        placeholder="Write a reply to the customer..."
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

                    <SecureAccessPanel
                        ticketId={ticket.id}
                        secureAccesses={ticket.secure_accesses ?? []}
                    />
                </div>
            </AdminLayout>
        </>
    );
}