import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';
import SecureAccessPanel from './SecureAccessPanel';

function statusLabel(status) {
    const labels = {
        submitted: 'Submitted',
        under_review: 'Under Review',
        needs_information: 'Needs Information',
        quote_sent: 'Quote Sent',
        quote_declined: 'Quote Declined',
        accepted: 'Accepted',
        in_progress: 'In Progress',
        ready_for_review: 'Ready for Review',
        revision_requested: 'Revision Requested',
        completed: 'Completed',
        request_declined: 'Request Declined',
        cancelled: 'Cancelled',
    };

    return labels[status] ?? status;
}

function statusClasses(status) {
    const classes = {
        submitted: 'bg-blue-500/10 text-blue-300 ring-blue-500/20',
        under_review: 'bg-amber-500/10 text-amber-300 ring-amber-500/20',
        needs_information:
            'bg-orange-500/10 text-orange-300 ring-orange-500/20',
        quote_sent: 'bg-violet-500/10 text-violet-300 ring-violet-500/20',
        quote_declined: 'bg-red-500/10 text-red-300 ring-red-500/20',
        accepted: 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20',
        in_progress: 'bg-indigo-500/10 text-indigo-300 ring-indigo-500/20',
        ready_for_review: 'bg-cyan-500/10 text-cyan-300 ring-cyan-500/20',
        revision_requested:
            'bg-orange-500/10 text-orange-300 ring-orange-500/20',
        completed:
            'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20',
        request_declined: 'bg-red-500/10 text-red-300 ring-red-500/20',
        cancelled: 'bg-gray-500/10 text-gray-300 ring-gray-500/20',
    };

    return (
        classes[status] ??
        'bg-gray-500/10 text-gray-300 ring-gray-500/20'
    );
}

function formatDate(date) {
    if (!date) {
        return '';
    }

    return new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}

export default function Show({
    customizationRequest,
    secureAccesses = [],
}) {
    const [processingReview, setProcessingReview] = useState(false);
    const [processingDevelopment, setProcessingDevelopment] = useState(false);
    const [processingReadyForReview, setProcessingReadyForReview] =
        useState(false);
    const [processingResumeDevelopment, setProcessingResumeDevelopment] =
        useState(false);

    const {
        data: informationData,
        setData: setInformationData,
        post: postInformation,
        processing: processingInformation,
        errors: informationErrors,
        reset: resetInformation,
    } = useForm({
        message: '',
    });

    const {
        data: conversationData,
        setData: setConversationData,
        post: postConversation,
        processing: processingConversation,
        errors: conversationErrors,
        reset: resetConversation,
    } = useForm({
        message: '',
    });

    const {
        data: quoteData,
        setData: setQuoteData,
        post: postQuote,
        processing: processingQuote,
        errors: quoteErrors,
        reset: resetQuote,
    } = useForm({
        price: '',
        scope: '',
        estimated_delivery: '',
    });

    const {
        data: declineData,
        setData: setDeclineData,
        post: postDecline,
        processing: processingDecline,
        errors: declineErrors,
        reset: resetDecline,
    } = useForm({
        message: '',
    });

    const startReview = () => {
        if (processingReview) {
            return;
        }

        setProcessingReview(true);

        router.patch(
            `/admin/customizations/${customizationRequest.id}/start-review`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingReview(false),
            },
        );
    };

    const startDevelopment = () => {
        if (processingDevelopment) {
            return;
        }

        const confirmed = window.confirm(
            'Are you sure you want to start development for this customization request?',
        );

        if (!confirmed) {
            return;
        }

        setProcessingDevelopment(true);

        router.patch(
            `/admin/customizations/${customizationRequest.id}/start-development`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingDevelopment(false),
            },
        );
    };

    const markReadyForReview = () => {
        if (processingReadyForReview) {
            return;
        }

        const confirmed = window.confirm(
            'Are you sure this customization is ready for customer review?',
        );

        if (!confirmed) {
            return;
        }

        setProcessingReadyForReview(true);

        router.patch(
            `/admin/customizations/${customizationRequest.id}/ready-for-review`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingReadyForReview(false),
            },
        );
    };

    const resumeDevelopment = () => {
        if (processingResumeDevelopment) {
            return;
        }

        const confirmed = window.confirm(
            'Are you sure you want to resume development for the requested revisions?',
        );

        if (!confirmed) {
            return;
        }

        setProcessingResumeDevelopment(true);

        router.patch(
            `/admin/customizations/${customizationRequest.id}/resume-development`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingResumeDevelopment(false),
            },
        );
    };

    const requestInformation = (event) => {
        event.preventDefault();

        postInformation(
            `/admin/customizations/${customizationRequest.id}/request-information`,
            {
                preserveScroll: true,
                onSuccess: () => resetInformation('message'),
            },
        );
    };

    const sendConversationMessage = (event) => {
        event.preventDefault();

        postConversation(
            `/admin/customizations/${customizationRequest.id}/messages`,
            {
                preserveScroll: true,
                onSuccess: () => resetConversation('message'),
            },
        );
    };

    const sendQuote = (event) => {
        event.preventDefault();

        postQuote(
            `/admin/customizations/${customizationRequest.id}/quote`,
            {
                preserveScroll: true,
                onSuccess: () => resetQuote(),
            },
        );
    };

    const declineRequest = (event) => {
        event.preventDefault();

        const confirmed = window.confirm(
            'Are you sure you want to decline this customization request? This will close the request and make the conversation read-only.',
        );

        if (!confirmed) {
            return;
        }

        postDecline(
            `/admin/customizations/${customizationRequest.id}/decline`,
            {
                preserveScroll: true,
                onSuccess: () => resetDecline('message'),
            },
        );
    };

    const messages = customizationRequest.messages ?? [];

    const terminalStatuses = [
        'completed',
        'request_declined',
        'cancelled',
        'quote_declined',
    ];

    const conversationIsReadOnly = terminalStatuses.includes(
        customizationRequest.status,
    );

    return (
        <AdminLayout>
            <Head title={customizationRequest.title} />

            <div className="mx-auto max-w-5xl space-y-6">
                <Link
                    href="/admin/customizations"
                    className="inline-flex text-sm font-medium text-gray-400 transition hover:text-white"
                >
                    ← Back to customization requests
                </Link>

                <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="text-sm font-medium text-gray-500">
                                Customization Request #{customizationRequest.id}
                            </p>

                            <h1 className="mt-2 text-2xl font-bold tracking-tight text-white">
                                {customizationRequest.title}
                            </h1>
                        </div>

                        <span
                            className={`inline-flex w-fit rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset ${statusClasses(customizationRequest.status)}`}
                        >
                            {statusLabel(customizationRequest.status)}
                        </span>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="rounded-xl border border-gray-800 bg-gray-900 p-6 lg:col-span-2">
                        <h2 className="text-sm font-semibold text-white">
                            Request details
                        </h2>

                        <p className="mt-4 whitespace-pre-wrap text-sm leading-7 text-gray-300">
                            {customizationRequest.description}
                        </p>
                    </div>

                    <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                        <h2 className="text-sm font-semibold text-white">
                            Customer
                        </h2>

                        <div className="mt-4">
                            <p className="font-medium text-white">
                                {customizationRequest.user?.name}
                            </p>

                            <p className="mt-1 break-all text-sm text-gray-400">
                                {customizationRequest.user?.email}
                            </p>
                        </div>

                        <div className="mt-6 border-t border-gray-800 pt-5">
                            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Current Status
                            </p>

                            <p className="mt-2 font-semibold text-white">
                                {statusLabel(customizationRequest.status)}
                            </p>
                        </div>
                    </div>
                </div>

                <SecureAccessPanel
                    customizationRequestId={customizationRequest.id}
                    secureAccesses={secureAccesses}
                />
                
                <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <div>
                        <h2 className="font-semibold text-white">
                            Conversation
                        </h2>

                        <p className="mt-1 text-sm text-gray-400">
                            Messages between BizzSoft and the customer about
                            this customization request.
                        </p>
                    </div>

                    {messages.length === 0 ? (
                        <div className="mt-5 rounded-lg border border-dashed border-gray-700 px-4 py-8 text-center">
                            <p className="text-sm text-gray-500">
                                No conversation messages yet.
                            </p>
                        </div>
                    ) : (
                        <div className="mt-5 space-y-4">
                            {messages.map((message) => (
                                <div
                                    key={message.id}
                                    className="rounded-lg border border-gray-800 bg-gray-950/60 p-4"
                                >
                                    <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm font-semibold text-white">
                                                {message.user?.name ??
                                                    'Unknown user'}
                                            </p>

                                            <p className="text-xs text-gray-500">
                                                {message.user?.email}
                                            </p>
                                        </div>

                                        <p className="text-xs text-gray-500">
                                            {formatDate(message.created_at)}
                                        </p>
                                    </div>

                                    <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-gray-300">
                                        {message.message}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}

                    {!conversationIsReadOnly ? (
                        <form
                            onSubmit={sendConversationMessage}
                            className="mt-6 border-t border-gray-800 pt-6"
                        >
                            <div>
                                <h3 className="text-sm font-semibold text-white">
                                    Send a message
                                </h3>

                                <p className="mt-1 text-sm leading-6 text-gray-400">
                                    Reply to the customer without changing the
                                    customization workflow status.
                                </p>
                            </div>

                            <div className="mt-4">
                                <label
                                    htmlFor="admin-conversation-message"
                                    className="block text-sm font-medium text-gray-300"
                                >
                                    Message
                                </label>

                                <textarea
                                    id="admin-conversation-message"
                                    rows="5"
                                    maxLength="5000"
                                    value={conversationData.message}
                                    onChange={(event) =>
                                        setConversationData(
                                            'message',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Type your message to the customer..."
                                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-3 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-gray-500"
                                />

                                <div className="mt-2 flex items-start justify-between gap-4">
                                    <div>
                                        {conversationErrors.message && (
                                            <p className="text-sm text-red-400">
                                                {conversationErrors.message}
                                            </p>
                                        )}
                                    </div>

                                    <p className="shrink-0 text-xs text-gray-500">
                                        {conversationData.message.length}/5000
                                    </p>
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={processingConversation}
                                className="mt-4 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-black transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processingConversation
                                    ? 'Sending Message...'
                                    : 'Send Message'}
                            </button>
                        </form>
                    ) : (
                        <div className="mt-6 rounded-lg border border-gray-800 bg-gray-950/60 p-4">
                            <p className="text-sm font-semibold text-gray-300">
                                Conversation closed
                            </p>

                            <p className="mt-1 text-sm leading-6 text-gray-500">
                                This conversation is read-only because the
                                customization request is no longer active.
                            </p>
                        </div>
                    )}
                </div>

                <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <h2 className="font-semibold text-white">
                        Admin workflow actions
                    </h2>

                    {customizationRequest.status === 'submitted' ? (
                        <div className="mt-4">
                            <p className="mb-4 text-sm leading-6 text-gray-400">
                                Start reviewing this request before requesting
                                more information, preparing a quotation, or
                                declining it.
                            </p>

                            <button
                                type="button"
                                onClick={startReview}
                                disabled={processingReview}
                                className="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-black transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processingReview
                                    ? 'Starting Review...'
                                    : 'Start Review'}
                            </button>
                        </div>
                    ) : customizationRequest.status === 'under_review' ? (
                        <div className="mt-5 grid gap-6 lg:grid-cols-3">
                            <form
                                onSubmit={requestInformation}
                                className="rounded-xl border border-gray-800 bg-gray-950/40 p-5"
                            >
                                <div>
                                    <h3 className="font-semibold text-white">
                                        Request Information
                                    </h3>

                                    <p className="mt-2 text-sm leading-6 text-gray-400">
                                        Ask the customer for more details before
                                        preparing a quotation. This moves the
                                        request to Needs Information.
                                    </p>
                                </div>

                                <div className="mt-5">
                                    <label
                                        htmlFor="message"
                                        className="block text-sm font-medium text-gray-300"
                                    >
                                        Question for customer
                                    </label>

                                    <textarea
                                        id="message"
                                        rows="6"
                                        maxLength="5000"
                                        value={informationData.message}
                                        onChange={(event) =>
                                            setInformationData(
                                                'message',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Example: What type of graph would you like us to build?"
                                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-3 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-gray-500"
                                    />

                                    <div className="mt-2 flex items-start justify-between gap-4">
                                        <div>
                                            {informationErrors.message && (
                                                <p className="text-sm text-red-400">
                                                    {
                                                        informationErrors.message
                                                    }
                                                </p>
                                            )}
                                        </div>

                                        <p className="shrink-0 text-xs text-gray-500">
                                            {informationData.message.length}
                                            /5000
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={processingInformation}
                                    className="mt-4 rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingInformation
                                        ? 'Sending Request...'
                                        : 'Request Information'}
                                </button>
                            </form>

                            <form
                                onSubmit={sendQuote}
                                className="rounded-xl border border-violet-500/20 bg-violet-500/5 p-5"
                            >
                                <div>
                                    <h3 className="font-semibold text-white">
                                        Prepare Quotation
                                    </h3>

                                    <p className="mt-2 text-sm leading-6 text-gray-400">
                                        Set the price, scope, and estimated
                                        delivery date. Sending the quotation
                                        moves this request to Quote Sent.
                                    </p>
                                </div>

                                <div className="mt-5">
                                    <label
                                        htmlFor="quote-price"
                                        className="block text-sm font-medium text-gray-300"
                                    >
                                        Price
                                    </label>

                                    <div className="relative mt-2">
                                        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">
                                            ₱
                                        </span>

                                        <input
                                            id="quote-price"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={quoteData.price}
                                            onChange={(event) =>
                                                setQuoteData(
                                                    'price',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="0.00"
                                            className="w-full rounded-lg border border-gray-700 bg-gray-950 py-3 pl-8 pr-3 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-violet-500"
                                        />
                                    </div>

                                    {quoteErrors.price && (
                                        <p className="mt-2 text-sm text-red-400">
                                            {quoteErrors.price}
                                        </p>
                                    )}
                                </div>

                                <div className="mt-5">
                                    <label
                                        htmlFor="quote-scope"
                                        className="block text-sm font-medium text-gray-300"
                                    >
                                        Scope / Notes
                                    </label>

                                    <textarea
                                        id="quote-scope"
                                        rows="6"
                                        maxLength="10000"
                                        value={quoteData.scope}
                                        onChange={(event) =>
                                            setQuoteData(
                                                'scope',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Describe exactly what is included in this quotation..."
                                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-3 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-violet-500"
                                    />

                                    <div className="mt-2 flex items-start justify-between gap-4">
                                        <div>
                                            {quoteErrors.scope && (
                                                <p className="text-sm text-red-400">
                                                    {quoteErrors.scope}
                                                </p>
                                            )}
                                        </div>

                                        <p className="shrink-0 text-xs text-gray-500">
                                            {quoteData.scope.length}/10000
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-5">
                                    <label
                                        htmlFor="estimated-delivery"
                                        className="block text-sm font-medium text-gray-300"
                                    >
                                        Estimated Delivery
                                    </label>

                                    <input
                                        id="estimated-delivery"
                                        type="date"
                                        value={quoteData.estimated_delivery}
                                        onChange={(event) =>
                                            setQuoteData(
                                                'estimated_delivery',
                                                event.target.value,
                                            )
                                        }
                                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-3 text-sm text-white outline-none transition focus:border-violet-500"
                                    />

                                    {quoteErrors.estimated_delivery && (
                                        <p className="mt-2 text-sm text-red-400">
                                            {quoteErrors.estimated_delivery}
                                        </p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processingQuote}
                                    className="mt-5 rounded-lg bg-violet-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-400 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingQuote
                                        ? 'Sending Quote...'
                                        : 'Send Quote'}
                                </button>
                            </form>

                            <form
                                onSubmit={declineRequest}
                                className="rounded-xl border border-red-500/20 bg-red-500/5 p-5"
                            >
                                <div>
                                    <h3 className="font-semibold text-white">
                                        Decline Request
                                    </h3>

                                    <p className="mt-2 text-sm leading-6 text-gray-400">
                                        Use this when BizzSoft cannot meet the
                                        customer&apos;s requirements. A reason is
                                        required and will be saved in the
                                        conversation before the request is closed.
                                    </p>
                                </div>

                                <div className="mt-5">
                                    <label
                                        htmlFor="decline-message"
                                        className="block text-sm font-medium text-gray-300"
                                    >
                                        Reason for declining
                                    </label>

                                    <textarea
                                        id="decline-message"
                                        rows="6"
                                        maxLength="5000"
                                        value={declineData.message}
                                        onChange={(event) =>
                                            setDeclineData(
                                                'message',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Explain why this customization request cannot be fulfilled..."
                                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-3 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-red-500"
                                    />

                                    <div className="mt-2 flex items-start justify-between gap-4">
                                        <div>
                                            {declineErrors.message && (
                                                <p className="text-sm text-red-400">
                                                    {declineErrors.message}
                                                </p>
                                            )}
                                        </div>

                                        <p className="shrink-0 text-xs text-gray-500">
                                            {declineData.message.length}/5000
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={processingDecline}
                                    className="mt-5 rounded-lg bg-red-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-400 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingDecline
                                        ? 'Declining Request...'
                                        : 'Decline Request'}
                                </button>
                            </form>
                        </div>
                    ) : customizationRequest.status ===
                        'needs_information' ? (
                        <div className="mt-4 grid gap-6 lg:grid-cols-2">
                            <div className="rounded-lg border border-orange-500/20 bg-orange-500/5 p-4">
                                <p className="text-sm font-medium text-orange-300">
                                    Waiting for customer information
                                </p>

                                <p className="mt-2 text-sm leading-6 text-gray-400">
                                    Your question has been sent. The request will
                                    remain in Needs Information until the customer
                                    responds.
                                </p>
                            </div>

                            <form
                                onSubmit={declineRequest}
                                className="rounded-xl border border-red-500/20 bg-red-500/5 p-5"
                            >
                                <div>
                                    <h3 className="font-semibold text-white">
                                        Decline Request
                                    </h3>

                                    <p className="mt-2 text-sm leading-6 text-gray-400">
                                        If the required information is not provided
                                        and BizzSoft cannot continue reviewing the
                                        request, enter a reason and close it as
                                        Request Declined.
                                    </p>
                                </div>

                                <div className="mt-5">
                                    <label
                                        htmlFor="decline-needs-information-message"
                                        className="block text-sm font-medium text-gray-300"
                                    >
                                        Reason for declining
                                    </label>

                                    <textarea
                                        id="decline-needs-information-message"
                                        rows="6"
                                        maxLength="5000"
                                        value={declineData.message}
                                        onChange={(event) =>
                                            setDeclineData(
                                                'message',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Example: We are unable to continue because the requested information was not provided."
                                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-3 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-red-500"
                                    />

                                    <div className="mt-2 flex items-start justify-between gap-4">
                                        <div>
                                            {declineErrors.message && (
                                                <p className="text-sm text-red-400">
                                                    {declineErrors.message}
                                                </p>
                                            )}
                                        </div>

                                        <p className="shrink-0 text-xs text-gray-500">
                                            {declineData.message.length}/5000
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={processingDecline}
                                    className="mt-5 rounded-lg bg-red-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-400 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingDecline
                                        ? 'Declining Request...'
                                        : 'Decline Request'}
                                </button>
                            </form>
                        </div>
                    ) : customizationRequest.status === 'quote_sent' ? (
                        <div className="mt-4 rounded-lg border border-violet-500/20 bg-violet-500/5 p-4">
                            <p className="text-sm font-medium text-violet-300">
                                Quotation sent
                            </p>

                            <p className="mt-2 text-sm leading-6 text-gray-400">
                                The quotation has been sent. The next step is
                                for the customer to review and accept or decline
                                it.
                            </p>
                        </div>
                    ) : customizationRequest.status === 'accepted' ? (
                        <div className="mt-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-5">
                            <p className="text-sm font-semibold text-emerald-300">
                                Quotation accepted
                            </p>

                            <p className="mt-2 text-sm leading-6 text-gray-400">
                                The customer accepted the quotation. Development
                                can now be started when BizzSoft is ready to
                                begin the work.
                            </p>

                            <button
                                type="button"
                                onClick={startDevelopment}
                                disabled={processingDevelopment}
                                className="mt-5 rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processingDevelopment
                                    ? 'Starting Development...'
                                    : 'Start Development'}
                            </button>
                        </div>
                    ) : customizationRequest.status === 'in_progress' ? (
                        <div className="mt-4 rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-5">
                            <p className="text-sm font-semibold text-indigo-300">
                                Development in progress
                            </p>

                            <p className="mt-2 text-sm leading-6 text-gray-400">
                                Development is currently in progress. When the
                                work is ready for the customer to inspect, mark
                                this customization request ready for review.
                            </p>

                            <button
                                type="button"
                                onClick={markReadyForReview}
                                disabled={processingReadyForReview}
                                className="mt-5 rounded-lg bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-400 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processingReadyForReview
                                    ? 'Marking Ready for Review...'
                                    : 'Mark Ready for Review'}
                            </button>
                        </div>
                    ) : customizationRequest.status ===
                        'ready_for_review' ? (
                        <div className="mt-4 rounded-xl border border-cyan-500/20 bg-cyan-500/5 p-5">
                            <p className="text-sm font-semibold text-cyan-300">
                                Ready for customer review
                            </p>

                            <p className="mt-2 text-sm leading-6 text-gray-400">
                                The work has been marked ready for review. The
                                next step is for the customer to review the work
                                and either request a revision or approve it.
                            </p>
                        </div>
                    ) : customizationRequest.status ===
                        'revision_requested' ? (
                        <div className="mt-4 rounded-xl border border-orange-500/20 bg-orange-500/5 p-5">
                            <p className="text-sm font-semibold text-orange-300">
                                Customer requested revisions
                            </p>

                            <p className="mt-2 text-sm leading-6 text-gray-400">
                                The customer has requested changes to the
                                customization. Review the revision details in the
                                conversation above, then resume development when
                                you are ready to work on the requested changes.
                            </p>

                            <button
                                type="button"
                                onClick={resumeDevelopment}
                                disabled={processingResumeDevelopment}
                                className="mt-5 rounded-lg bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-400 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processingResumeDevelopment
                                    ? 'Resuming Development...'
                                    : 'Resume Development'}
                            </button>
                        </div>
                    ) : (
                        <div className="mt-4">
                            <p className="text-sm leading-6 text-gray-400">
                                Additional workflow actions for this status will
                                be added in the next development batches.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}