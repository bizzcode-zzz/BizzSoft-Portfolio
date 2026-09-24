import { Head, Link, useForm } from '@inertiajs/react';
import CustomerLayout from '../../../Layouts/CustomerLayout';
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
            'bg-orange-500/50/10 text-orange-300 ring-orange-500/20',
        quote_sent: 'bg-violet-500/50/10 text-violet-300 ring-violet-500/20',
        quote_declined: 'bg-red-500/50/10 text-red-300 ring-red-500/20',
        accepted: 'bg-emerald-500/50/10 text-emerald-300 ring-emerald-500/20',
        in_progress: 'bg-indigo-500/10 text-indigo-300 ring-indigo-500/20',
        ready_for_review: 'bg-cyan-500/50/10 text-cyan-300 ring-cyan-500/20',
        revision_requested:
            'bg-orange-500/50/10 text-orange-300 ring-orange-500/20',
        completed:
            'bg-emerald-500/50/10 text-emerald-300 ring-emerald-500/20',
        request_declined: 'bg-red-500/50/10 text-red-300 ring-red-500/20',
        cancelled: 'bg-gray-950/600/10 text-gray-300 ring-gray-500/20',
    };

    return (
        classes[status] ??
        'bg-gray-950/600/10 text-gray-300 ring-gray-500/20'
    );
}

function statusDescription(status) {
    const descriptions = {
        submitted:
            'Your request has been received and is waiting for administrator review.',
        under_review:
            'Your request is currently being reviewed.',
        needs_information:
            'More information is needed from you before the request can continue.',
        quote_sent:
            'A quotation has been prepared and is waiting for your decision.',
        quote_declined:
            'The quotation for this request was declined.',
        accepted:
            'The quotation was accepted and the request is ready for development.',
        in_progress:
            'Development work on this customization is currently in progress.',
        ready_for_review:
            'The customization is ready for your review.',
        revision_requested:
            'You requested revisions and the request is waiting for further work.',
        completed:
            'This customization request has been completed.',
        request_declined:
            'This customization request was declined during review.',
        cancelled:
            'This customization request has been cancelled.',
    };

    return (
        descriptions[status] ??
        'Track the current status of your request here.'
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

function formatDeliveryDate(date) {
    if (!date) {
        return '';
    }

    const normalizedDate = String(date).slice(0, 10);
    const [year, month, day] = normalizedDate.split('-').map(Number);

    if (!year || !month || !day) {
        return date;
    }

    return new Intl.DateTimeFormat('en', {
        dateStyle: 'long',
    }).format(new Date(year, month - 1, day));
}

function formatPrice(price) {
    const amount = Number(price);

    if (!Number.isFinite(amount)) {
        return price;
    }

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(amount);
}

export default function Show({
    customizationRequest,
    secureAccesses = [],
}) {
    const {
        data,
        setData,
        post,
        processing: processingInformation,
        errors,
        reset,
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
        patch: patchQuoteDecision,
        processing: processingQuoteDecision,
    } = useForm({});

    const {
        data: revisionData,
        setData: setRevisionData,
        post: postRevision,
        processing: processingRevision,
        errors: revisionErrors,
        reset: resetRevision,
    } = useForm({
        message: '',
    });

    const {
        patch: patchApproval,
        processing: processingApproval,
    } = useForm({});

    const {
        patch: patchCancellation,
        processing: processingCancellation,
    } = useForm({});

    const messages = customizationRequest.messages ?? [];
    const quote = customizationRequest.quote ?? null;

    const terminalStatuses = [
        'completed',
        'request_declined',
        'cancelled',
        'quote_declined',
    ];

    const conversationIsReadOnly = terminalStatuses.includes(
        customizationRequest.status,
    );

    const showNormalConversationForm =
        !conversationIsReadOnly &&
        customizationRequest.status !== 'needs_information';

    const cancellableStatuses = [
        'submitted',
        'under_review',
        'needs_information',
        'quote_sent',
        'accepted',
    ];

    const canCancel = cancellableStatuses.includes(
        customizationRequest.status,
    );

    const submitInformation = (event) => {
        event.preventDefault();

        post(`/customizations/${customizationRequest.id}/replies`, {
            preserveScroll: true,
            onSuccess: () => reset('message'),
        });
    };

    const sendConversationMessage = (event) => {
        event.preventDefault();

        postConversation(
            `/customizations/${customizationRequest.id}/messages`,
            {
                preserveScroll: true,
                onSuccess: () => resetConversation('message'),
            },
        );
    };

    const acceptQuote = () => {
        const confirmed = window.confirm(
            'Are you sure you want to accept this quotation?',
        );

        if (!confirmed) {
            return;
        }

        patchQuoteDecision(
            `/customizations/${customizationRequest.id}/quote/accept`,
            {
                preserveScroll: true,
            },
        );
    };

    const declineQuote = () => {
        const confirmed = window.confirm(
            'Are you sure you want to decline this quotation?',
        );

        if (!confirmed) {
            return;
        }

        patchQuoteDecision(
            `/customizations/${customizationRequest.id}/quote/decline`,
            {
                preserveScroll: true,
            },
        );
    };

    const requestRevision = (event) => {
        event.preventDefault();

        const confirmed = window.confirm(
            'Are you sure you want to request these revisions?',
        );

        if (!confirmed) {
            return;
        }

        postRevision(
            `/customizations/${customizationRequest.id}/request-revision`,
            {
                preserveScroll: true,
                onSuccess: () => resetRevision('message'),
            },
        );
    };

    const approveAndComplete = () => {
        const confirmed = window.confirm(
            'Are you sure you want to approve this customization and mark it as completed?',
        );

        if (!confirmed) {
            return;
        }

        patchApproval(
            `/customizations/${customizationRequest.id}/approve`,
            {
                preserveScroll: true,
            },
        );
    };

    const cancelRequest = () => {
        const confirmed = window.confirm(
            'Are you sure you want to cancel this customization request? This action will close the request and make the conversation read-only.',
        );

        if (!confirmed) {
            return;
        }

        patchCancellation(
            `/customizations/${customizationRequest.id}/cancel`,
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <CustomerLayout>
            <Head title={customizationRequest.title} />

            <div className="mx-auto max-w-4xl space-y-6">
                <div>
                    <Link
                        href="/customizations"
                        className="text-sm font-medium text-gray-500 transition hover:text-white"
                    >
                        ← Back to customization requests
                    </Link>
                </div>

                <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-500">
                                Customization Request #{customizationRequest.id}
                            </p>

                            <h1 className="mt-2 text-2xl font-bold tracking-tight text-white">
                                {customizationRequest.title}
                            </h1>
                        </div>

                        <span
                            className={`inline-flex w-fit shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset ${statusClasses(customizationRequest.status)}`}
                        >
                            {statusLabel(customizationRequest.status)}
                        </span>
                    </div>

                    <div className="mt-6 border-t border-gray-800 pt-6">
                        <h2 className="text-sm font-semibold text-white">
                            Request details
                        </h2>

                        <p className="mt-3 whitespace-pre-wrap text-sm leading-7 text-gray-300">
                            {customizationRequest.description}
                        </p>
                    </div>
                </div>

                <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <div className="flex items-start gap-4">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-800 font-semibold text-gray-500">
                            ✓
                        </div>

                        <div>
                            <h2 className="text-sm font-semibold text-white">
                                Current status
                            </h2>

                            <p className="mt-1 text-base font-semibold text-white">
                                {statusLabel(customizationRequest.status)}
                            </p>

                            <p className="mt-1 text-sm leading-6 text-gray-500">
                                {statusDescription(
                                    customizationRequest.status,
                                )}
                            </p>
                        </div>
                    </div>
                </div>

                {customizationRequest.status === 'ready_for_review' && (
                    <div className="overflow-hidden rounded-xl border border-cyan-500/20 bg-gray-900">
                        <div className="border-b border-cyan-500/20 bg-cyan-500/5 px-6 py-5">
                            <p className="text-xs font-semibold uppercase tracking-wide text-cyan-300">
                                Customer Review
                            </p>

                            <h2 className="mt-1 text-lg font-bold text-white">
                                Review your customization
                            </h2>

                            <p className="mt-2 text-sm leading-6 text-cyan-300">
                                BizzSoft has marked the customization ready for
                                review. You can approve the work or request
                                revisions below.
                            </p>
                        </div>

                        <div className="grid gap-6 p-6 lg:grid-cols-2">
                            <form
                                onSubmit={requestRevision}
                                className="rounded-xl border border-orange-500/20 bg-orange-500/5 p-5"
                            >
                                <h3 className="text-sm font-semibold text-orange-300">
                                    Request Revision
                                </h3>

                                <p className="mt-1 text-sm leading-6 text-orange-300">
                                    Describe exactly what you would like BizzSoft
                                    to revise.
                                </p>

                                <div className="mt-4">
                                    <label
                                        htmlFor="revision-message"
                                        className="block text-sm font-medium text-gray-300"
                                    >
                                        Revision details
                                    </label>

                                    <textarea
                                        id="revision-message"
                                        rows="6"
                                        maxLength="5000"
                                        value={revisionData.message}
                                        onChange={(event) =>
                                            setRevisionData(
                                                'message',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Example: Please make the graph labels larger and add a monthly filter."
                                        className="mt-2 w-full rounded-lg border border-orange-500/20 bg-gray-900 px-3 py-3 text-sm text-white outline-none transition placeholder:text-gray-500 focus:border-orange-500"
                                    />

                                    <div className="mt-2 flex items-start justify-between gap-4">
                                        <div>
                                            {revisionErrors.message && (
                                                <p className="text-sm text-red-400">
                                                    {revisionErrors.message}
                                                </p>
                                            )}
                                        </div>

                                        <p className="shrink-0 text-xs text-gray-500">
                                            {revisionData.message.length}/5000
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={processingRevision}
                                    className="mt-4 rounded-lg border border-orange-500/30 bg-gray-900 px-4 py-2.5 text-sm font-semibold text-orange-300 transition hover:bg-orange-500/50/10 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingRevision
                                        ? 'Requesting Revision...'
                                        : 'Request Revision'}
                                </button>
                            </form>

                            <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-5">
                                <h3 className="text-sm font-semibold text-emerald-300">
                                    Approve & Complete
                                </h3>

                                <p className="mt-1 text-sm leading-6 text-emerald-300">
                                    If the customization meets your requirements,
                                    approve the work and mark this request as
                                    completed.
                                </p>

                                <div className="mt-4 rounded-lg border border-emerald-500/20 bg-gray-900 p-4">
                                    <p className="text-sm leading-6 text-gray-500">
                                        Approval completes this customization
                                        request. Only approve when you are
                                        satisfied with the delivered work.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onClick={approveAndComplete}
                                    disabled={processingApproval}
                                    className="mt-4 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500/50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingApproval
                                        ? 'Completing...'
                                        : 'Approve & Complete'}
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {customizationRequest.status === 'revision_requested' && (
                    <div className="rounded-xl border border-orange-500/20 bg-orange-500/5 p-5">
                        <p className="text-sm font-semibold text-orange-300">
                            Revision requested
                        </p>

                        <p className="mt-1 text-sm leading-6 text-orange-300">
                            Your revision request has been sent to BizzSoft. The
                            details are recorded in the conversation below.
                        </p>
                    </div>
                )}

                {customizationRequest.status === 'completed' && (
                    <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-5">
                        <p className="text-sm font-semibold text-emerald-300">
                            Customization completed
                        </p>

                        <p className="mt-1 text-sm leading-6 text-emerald-300">
                            You approved this customization and the request is
                            now completed.
                        </p>
                    </div>
                )}

                {quote && (
                    <div className="overflow-hidden rounded-xl border border-violet-500/20 bg-gray-900">
                        <div className="border-b border-violet-500/20 bg-violet-500/5 px-6 py-5">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-violet-300">
                                        BizzSoft Quotation
                                    </p>

                                    <h2 className="mt-1 text-lg font-bold text-white">
                                        Customization Quote
                                    </h2>
                                </div>

                                <span
                                    className={`inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${statusClasses(customizationRequest.status)}`}
                                >
                                    {statusLabel(
                                        customizationRequest.status,
                                    )}
                                </span>
                            </div>
                        </div>

                        <div className="p-6">
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="rounded-lg border border-gray-800 bg-gray-950/60 p-4">
                                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Price
                                    </p>

                                    <p className="mt-2 text-2xl font-bold tracking-tight text-white">
                                        {formatPrice(quote.price)}
                                    </p>
                                </div>

                                <div className="rounded-lg border border-gray-800 bg-gray-950/60 p-4">
                                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Estimated Delivery
                                    </p>

                                    <p className="mt-2 text-base font-semibold text-white">
                                        {formatDeliveryDate(
                                            quote.estimated_delivery,
                                        )}
                                    </p>
                                </div>
                            </div>

                            <div className="mt-5">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Scope / Notes
                                </p>

                                <div className="mt-2 rounded-lg border border-gray-800 bg-gray-950/60 p-4">
                                    <p className="whitespace-pre-wrap text-sm leading-7 text-gray-300">
                                        {quote.scope}
                                    </p>
                                </div>
                            </div>

                            {customizationRequest.status === 'quote_sent' && (
                                <div className="mt-5 rounded-lg border border-violet-500/20 bg-violet-500/5 p-5">
                                    <p className="text-sm font-semibold text-violet-300">
                                        Your decision is required
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-violet-300">
                                        Please review the price, scope, and
                                        estimated delivery before making your
                                        decision.
                                    </p>

                                    <div className="mt-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                        <button
                                            type="button"
                                            onClick={declineQuote}
                                            disabled={processingQuoteDecision}
                                            className="rounded-lg border border-red-500/20 bg-gray-900 px-4 py-2.5 text-sm font-semibold text-red-300 transition hover:bg-red-500/50/10 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Decline Quote
                                        </button>

                                        <button
                                            type="button"
                                            onClick={acceptQuote}
                                            disabled={processingQuoteDecision}
                                            className="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-black transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {processingQuoteDecision
                                                ? 'Processing...'
                                                : 'Accept Quote'}
                                        </button>
                                    </div>
                                </div>
                            )}

                            {customizationRequest.status === 'accepted' && (
                                <div className="mt-5 rounded-lg border border-emerald-500/20 bg-emerald-500/5 p-4">
                                    <p className="text-sm font-semibold text-emerald-300">
                                        Quotation accepted
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-emerald-300">
                                        You accepted this quotation. BizzSoft
                                        can now proceed with the development
                                        workflow.
                                    </p>
                                </div>
                            )}

                            {customizationRequest.status ===
                                'quote_declined' && (
                                    <div className="mt-5 rounded-lg border border-red-500/20 bg-red-500/5 p-4">
                                        <p className="text-sm font-semibold text-red-300">
                                            Quotation declined
                                        </p>

                                        <p className="mt-1 text-sm leading-6 text-red-300">
                                            You declined this quotation. No further
                                            quote decision is required.
                                        </p>
                                    </div>
                                )}
                        </div>
                    </div>
                )}

                {canCancel && (
                    <div className="rounded-xl border border-red-500/20 bg-red-500/5 p-5">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-sm font-semibold text-red-300">
                                    Cancel Request
                                </h2>

                                <p className="mt-1 text-sm leading-6 text-gray-400">
                                    You can cancel this customization request before
                                    development begins. Cancelling will close the
                                    request and make the conversation read-only.
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={cancelRequest}
                                disabled={processingCancellation}
                                className="shrink-0 rounded-lg bg-red-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-400 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processingCancellation
                                    ? 'Cancelling...'
                                    : 'Cancel Request'}
                            </button>
                        </div>
                    </div>
                )}

                <SecureAccessPanel
                    customizationRequestId={customizationRequest.id}
                    secureAccesses={secureAccesses}
                />

                <div className="rounded-xl border border-gray-800 bg-gray-900 p-6">
                    <div>
                        <h2 className="text-sm font-semibold text-white">
                            Conversation
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-gray-500">
                            Messages between you and BizzSoft about this
                            customization request.
                        </p>
                    </div>

                    {messages.length === 0 ? (
                        <div className="mt-5 rounded-lg border border-dashed border-gray-700 bg-gray-950/60 px-4 py-8 text-center">
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
                                                    'BizzSoft'}
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

                    {customizationRequest.status ===
                        'needs_information' && (
                            <form
                                onSubmit={submitInformation}
                                className="mt-6 rounded-lg border border-orange-500/20 bg-orange-500/5 p-5"
                            >
                                <div>
                                    <h3 className="text-sm font-semibold text-orange-300">
                                        Your response is needed
                                    </h3>

                                    <p className="mt-1 text-sm leading-6 text-orange-300">
                                        Please provide the information requested by
                                        BizzSoft. After you send your response, the
                                        request will return to Under Review.
                                    </p>
                                </div>

                                <div className="mt-4">
                                    <label
                                        htmlFor="information-message"
                                        className="block text-sm font-medium text-gray-300"
                                    >
                                        Your response
                                    </label>

                                    <textarea
                                        id="information-message"
                                        rows="5"
                                        maxLength="5000"
                                        value={data.message}
                                        onChange={(event) =>
                                            setData(
                                                'message',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Provide the requested details..."
                                        className="mt-2 w-full rounded-lg border border-orange-500/20 bg-gray-900 px-3 py-3 text-sm text-white outline-none transition placeholder:text-gray-500 focus:border-orange-500"
                                    />

                                    <div className="mt-2 flex items-start justify-between gap-4">
                                        <div>
                                            {errors.message && (
                                                <p className="text-sm text-red-400">
                                                    {errors.message}
                                                </p>
                                            )}
                                        </div>

                                        <p className="shrink-0 text-xs text-gray-500">
                                            {data.message.length}/5000
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={processingInformation}
                                    className="mt-4 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-950 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {processingInformation
                                        ? 'Sending Information...'
                                        : 'Send Information'}
                                </button>
                            </form>
                        )}

                    {showNormalConversationForm && (
                        <form
                            onSubmit={sendConversationMessage}
                            className="mt-6 border-t border-gray-800 pt-6"
                        >
                            <div>
                                <h3 className="text-sm font-semibold text-white">
                                    Send a message
                                </h3>

                                <p className="mt-1 text-sm leading-6 text-gray-500">
                                    Add another detail, question, or message for
                                    BizzSoft. Sending a normal message does not
                                    change the request status.
                                </p>
                            </div>

                            <div className="mt-4">
                                <label
                                    htmlFor="conversation-message"
                                    className="block text-sm font-medium text-gray-300"
                                >
                                    Message
                                </label>

                                <textarea
                                    id="conversation-message"
                                    rows="5"
                                    maxLength="5000"
                                    value={conversationData.message}
                                    onChange={(event) =>
                                        setConversationData(
                                            'message',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Type your message to BizzSoft..."
                                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-900 px-3 py-3 text-sm text-white outline-none transition placeholder:text-gray-500 focus:border-gray-500"
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
                                className="mt-4 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-950 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processingConversation
                                    ? 'Sending Message...'
                                    : 'Send Message'}
                            </button>
                        </form>
                    )}

                    {conversationIsReadOnly && (
                        <div className="mt-6 rounded-lg border border-gray-800 bg-gray-950/60 p-4">
                            <p className="text-sm font-semibold text-gray-300">
                                Conversation closed
                            </p>

                            <p className="mt-1 text-sm leading-6 text-gray-500">
                                This conversation is now read-only because the
                                customization request is no longer active.
                            </p>
                        </div>
                    )}

                    {customizationRequest.status === 'under_review' &&
                        messages.length > 0 && (
                            <div className="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <p className="text-sm font-semibold text-amber-900">
                                    Response received
                                </p>

                                <p className="mt-1 text-sm leading-6 text-amber-700">
                                    Your request is back under review. BizzSoft
                                    will continue reviewing the information you
                                    provided.
                                </p>
                            </div>
                        )}
                </div>
            </div>
        </CustomerLayout>
    );
}