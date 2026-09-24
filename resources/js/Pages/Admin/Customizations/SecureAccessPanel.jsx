import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const ACCESS_TYPES = [
    { value: 'hosting', label: 'Hosting' },
    { value: 'ssh_sftp', label: 'SSH / SFTP' },
    { value: 'database', label: 'Database' },
    { value: 'api_token', label: 'API Token' },
    { value: 'other', label: 'Other' },
];

function typeLabel(type) {
    return (
        ACCESS_TYPES.find((item) => item.value === type)?.label ??
        type
    );
}

function statusLabel(status) {
    const labels = {
        requested: 'Requested',
        submitted: 'Submitted',
        closed: 'Closed',
    };

    return labels[status] ?? status;
}

function statusClasses(status) {
    const classes = {
        requested:
            'bg-amber-500/10 text-amber-300 ring-amber-500/20',
        submitted:
            'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20',
        closed:
            'bg-gray-800 text-gray-400 ring-gray-700',
    };

    return (
        classes[status] ??
        'bg-gray-800 text-gray-400 ring-gray-700'
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

function RequestSecureAccessForm({ customizationRequestId }) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm({
        type: 'hosting',
        label: '',
    });

    const submit = (event) => {
        event.preventDefault();

        const confirmed = window.confirm(
            'Request secure credentials from this customer?',
        );

        if (!confirmed) {
            return;
        }

        post(
            `/admin/customizations/${customizationRequestId}/secure-access`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    reset();
                },
            },
        );
    };

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-amber-500/20 bg-amber-500/5 p-5"
        >
            <div>
                <p className="text-sm font-semibold text-amber-300">
                    Request Secure Access
                </p>

                <p className="mt-2 text-sm leading-6 text-gray-400">
                    Ask the customer to securely provide temporary
                    hosting, SSH/SFTP, database, API, or other access
                    required for the work.
                </p>
            </div>

            <div className="mt-5">
                <label
                    htmlFor="secure-request-type"
                    className="block text-sm font-medium text-gray-300"
                >
                    Access Type
                </label>

                <select
                    id="secure-request-type"
                    value={data.type}
                    onChange={(event) =>
                        setData('type', event.target.value)
                    }
                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition focus:border-amber-500"
                >
                    {ACCESS_TYPES.map((type) => (
                        <option key={type.value} value={type.value}>
                            {type.label}
                        </option>
                    ))}
                </select>

                {errors.type && (
                    <p className="mt-2 text-sm text-red-400">
                        {errors.type}
                    </p>
                )}
            </div>

            <div className="mt-4">
                <label
                    htmlFor="secure-request-label"
                    className="block text-sm font-medium text-gray-300"
                >
                    Label
                </label>

                <input
                    id="secure-request-label"
                    type="text"
                    value={data.label}
                    onChange={(event) =>
                        setData('label', event.target.value)
                    }
                    placeholder="Example: Production hosting access"
                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-amber-500"
                />

                {errors.label && (
                    <p className="mt-2 text-sm text-red-400">
                        {errors.label}
                    </p>
                )}
            </div>

            <button
                type="submit"
                disabled={processing}
                className="mt-5 rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-gray-950 transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {processing
                    ? 'Requesting...'
                    : 'Request Secure Access'}
            </button>
        </form>
    );
}

function SecureHandoffForm({ customizationRequestId }) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm({
        type: 'hosting',
        label: '',
        login_url: '',
        username: '',
        secret: '',
        notes: '',
    });

    const [showSecret, setShowSecret] = useState(false);

    const submit = (event) => {
        event.preventDefault();

        const confirmed = window.confirm(
            'Send these credentials securely to the customer?',
        );

        if (!confirmed) {
            return;
        }

        post(
            `/admin/customizations/${customizationRequestId}/secure-access/handoff`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    reset();
                    setShowSecret(false);
                },
            },
        );
    };

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-cyan-500/20 bg-cyan-500/5 p-5"
        >
            <div>
                <p className="text-sm font-semibold text-cyan-300">
                    Send Secure Access
                </p>

                <p className="mt-2 text-sm leading-6 text-gray-400">
                    Securely provide a temporary account, password,
                    token, or other access to the customer.
                </p>
            </div>

            <div className="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label
                        htmlFor="handoff-type"
                        className="block text-sm font-medium text-gray-300"
                    >
                        Access Type
                    </label>

                    <select
                        id="handoff-type"
                        value={data.type}
                        onChange={(event) =>
                            setData('type', event.target.value)
                        }
                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition focus:border-cyan-500"
                    >
                        {ACCESS_TYPES.map((type) => (
                            <option
                                key={type.value}
                                value={type.value}
                            >
                                {type.label}
                            </option>
                        ))}
                    </select>

                    {errors.type && (
                        <p className="mt-2 text-sm text-red-400">
                            {errors.type}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor="handoff-label"
                        className="block text-sm font-medium text-gray-300"
                    >
                        Label
                    </label>

                    <input
                        id="handoff-label"
                        type="text"
                        value={data.label}
                        onChange={(event) =>
                            setData('label', event.target.value)
                        }
                        placeholder="Example: Temporary hosting login"
                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-cyan-500"
                    />

                    {errors.label && (
                        <p className="mt-2 text-sm text-red-400">
                            {errors.label}
                        </p>
                    )}
                </div>
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label
                        htmlFor="handoff-login-url"
                        className="block text-sm font-medium text-gray-300"
                    >
                        Login URL
                    </label>

                    <input
                        id="handoff-login-url"
                        type="text"
                        maxLength="2000"
                        value={data.login_url}
                        onChange={(event) =>
                            setData('login_url', event.target.value)
                        }
                        placeholder="https://example.com/login"
                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-cyan-500"
                    />

                    {errors.login_url && (
                        <p className="mt-2 text-sm text-red-400">
                            {errors.login_url}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor="handoff-username"
                        className="block text-sm font-medium text-gray-300"
                    >
                        Username / Email
                    </label>

                    <input
                        id="handoff-username"
                        type="text"
                        maxLength="1000"
                        value={data.username}
                        onChange={(event) =>
                            setData('username', event.target.value)
                        }
                        placeholder="Temporary username or email"
                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-cyan-500"
                    />

                    {errors.username && (
                        <p className="mt-2 text-sm text-red-400">
                            {errors.username}
                        </p>
                    )}
                </div>
            </div>

            <div className="mt-4">
                <label
                    htmlFor="handoff-secret"
                    className="block text-sm font-medium text-gray-300"
                >
                    Password / Token / Secret
                </label>

                <div className="mt-2 flex gap-2">
                    <input
                        id="handoff-secret"
                        type={showSecret ? 'text' : 'password'}
                        maxLength="10000"
                        value={data.secret}
                        onChange={(event) =>
                            setData('secret', event.target.value)
                        }
                        placeholder="Temporary password or secret"
                        className="min-w-0 flex-1 rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-cyan-500"
                    />

                    <button
                        type="button"
                        onClick={() =>
                            setShowSecret((current) => !current)
                        }
                        className="shrink-0 rounded-lg border border-gray-700 bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-300 transition hover:bg-gray-800"
                    >
                        {showSecret ? 'Hide' : 'Show'}
                    </button>
                </div>

                {errors.secret && (
                    <p className="mt-2 text-sm text-red-400">
                        {errors.secret}
                    </p>
                )}
            </div>

            <div className="mt-4">
                <label
                    htmlFor="handoff-notes"
                    className="block text-sm font-medium text-gray-300"
                >
                    Secure Notes
                </label>

                <textarea
                    id="handoff-notes"
                    rows="4"
                    maxLength="5000"
                    value={data.notes}
                    onChange={(event) =>
                        setData('notes', event.target.value)
                    }
                    placeholder="Optional secure instructions..."
                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-3 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-cyan-500"
                />

                {errors.notes && (
                    <p className="mt-2 text-sm text-red-400">
                        {errors.notes}
                    </p>
                )}
            </div>

            <button
                type="submit"
                disabled={processing}
                className="mt-5 rounded-lg bg-cyan-500 px-4 py-2.5 text-sm font-semibold text-gray-950 transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {processing
                    ? 'Sending Securely...'
                    : 'Send Secure Access'}
            </button>
        </form>
    );
}

function ExistingSecureAccess({
    customizationRequestId,
    secureAccess,
}) {
    const [revealedAccess, setRevealedAccess] = useState(null);
    const [revealing, setRevealing] = useState(false);
    const [revealError, setRevealError] = useState('');
    const [showSecret, setShowSecret] = useState(false);
    const [closing, setClosing] = useState(false);

    const canReveal =
        secureAccess.direction === 'customer_to_admin' &&
        secureAccess.status === 'submitted';

    const reveal = async () => {
        const confirmed = window.confirm(
            'Reveal these customer credentials? Make sure nobody else can see your screen.',
        );

        if (!confirmed) {
            return;
        }

        setRevealing(true);
        setRevealError('');

        try {
            const response = await fetch(
                `/admin/customizations/${customizationRequestId}/secure-access/${secureAccess.id}/reveal`,
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    'Secure credentials could not be revealed.',
                );
            }

            const payload = await response.json();

            setRevealedAccess(payload.secure_access);
            setShowSecret(false);

            router.reload({
                only: ['secureAccesses'],
                preserveScroll: true,
            });
        } catch (error) {
            setRevealError(
                error instanceof Error
                    ? error.message
                    : 'Secure credentials could not be revealed.',
            );
        } finally {
            setRevealing(false);
        }
    };

    const closeAccess = () => {
        if (closing) {
            return;
        }

        const confirmed = window.confirm(
            'Close this Secure Access record? The stored login URL, username, secret, and secure notes will be permanently purged and cannot be revealed again.',
        );

        if (!confirmed) {
            return;
        }

        setClosing(true);
        setRevealedAccess(null);
        setShowSecret(false);

        router.patch(
            `/admin/customizations/${customizationRequestId}/secure-access/${secureAccess.id}/close`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setClosing(false),
            },
        );
    };

    return (
        <div className="rounded-xl border border-gray-800 bg-gray-950/50 p-5">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                        {secureAccess.direction ===
                        'customer_to_admin'
                            ? 'Customer → BizzSoft'
                            : 'BizzSoft → Customer'}
                    </p>

                    <h3 className="mt-1 font-semibold text-white">
                        {secureAccess.label}
                    </h3>

                    <p className="mt-1 text-sm text-gray-500">
                        {typeLabel(secureAccess.type)}
                    </p>
                </div>

                <span
                    className={`inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${statusClasses(
                        secureAccess.status,
                    )}`}
                >
                    {statusLabel(secureAccess.status)}
                </span>
            </div>

            <div className="mt-4 space-y-1 text-xs text-gray-500">
                {secureAccess.created_at && (
                    <p>
                        Created: {formatDate(secureAccess.created_at)}
                    </p>
                )}

                {secureAccess.submitted_at && (
                    <p>
                        Submitted:{' '}
                        {formatDate(secureAccess.submitted_at)}
                    </p>
                )}

                {secureAccess.viewed_at && (
                    <p>
                        First viewed:{' '}
                        {formatDate(secureAccess.viewed_at)}
                    </p>
                )}

                {secureAccess.closed_at && (
                    <p>
                        Closed: {formatDate(secureAccess.closed_at)}
                    </p>
                )}
            </div>

            {secureAccess.direction === 'customer_to_admin' &&
                secureAccess.status === 'requested' && (
                    <div className="mt-5 rounded-lg border border-amber-500/20 bg-amber-500/5 p-4">
                        <p className="text-sm font-semibold text-amber-300">
                            Waiting for customer
                        </p>

                        <p className="mt-1 text-sm leading-6 text-gray-400">
                            The secure access request has been sent.
                            The customer can now submit the requested
                            credentials through their Secure Access
                            panel.
                        </p>
                    </div>
                )}

            {canReveal && (
                <div className="mt-5">
                    {!revealedAccess && (
                        <button
                            type="button"
                            onClick={reveal}
                            disabled={revealing}
                            className="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-950 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {revealing
                                ? 'Revealing...'
                                : 'Reveal Secure Access'}
                        </button>
                    )}

                    {revealError && (
                        <p className="mt-3 text-sm text-red-400">
                            {revealError}
                        </p>
                    )}

                    {revealedAccess && (
                        <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-5">
                            <div className="flex items-center justify-between gap-4">
                                <p className="text-sm font-semibold text-emerald-300">
                                    Revealed customer credentials
                                </p>

                                <button
                                    type="button"
                                    onClick={() => {
                                        setRevealedAccess(null);
                                        setShowSecret(false);
                                    }}
                                    className="text-sm font-semibold text-gray-400 transition hover:text-white"
                                >
                                    Hide
                                </button>
                            </div>

                            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Login URL
                                    </p>

                                    <p className="mt-1 break-all text-sm text-gray-200">
                                        {revealedAccess.login_url ||
                                            '—'}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Username / Email
                                    </p>

                                    <p className="mt-1 break-all text-sm text-gray-200">
                                        {revealedAccess.username ||
                                            '—'}
                                    </p>
                                </div>
                            </div>

                            <div className="mt-4">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Password / Token / Secret
                                </p>

                                <div className="mt-2 flex items-center gap-3">
                                    <code className="min-w-0 flex-1 break-all rounded-lg border border-gray-800 bg-gray-950 px-3 py-2.5 text-sm text-gray-200">
                                        {showSecret
                                            ? revealedAccess.secret
                                            : '••••••••••••'}
                                    </code>

                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowSecret(
                                                (current) =>
                                                    !current,
                                            )
                                        }
                                        className="shrink-0 rounded-lg border border-gray-700 bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-300 transition hover:bg-gray-800"
                                    >
                                        {showSecret
                                            ? 'Hide'
                                            : 'Show'}
                                    </button>
                                </div>
                            </div>

                            {revealedAccess.notes && (
                                <div className="mt-4">
                                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Secure Notes
                                    </p>

                                    <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-gray-300">
                                        {revealedAccess.notes}
                                    </p>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            )}

            {secureAccess.direction === 'admin_to_customer' &&
                secureAccess.status === 'submitted' && (
                    <div className="mt-5 rounded-lg border border-cyan-500/20 bg-cyan-500/5 p-4">
                        <p className="text-sm font-semibold text-cyan-300">
                            Secure access sent to customer
                        </p>

                        <p className="mt-1 text-sm leading-6 text-gray-400">
                            The customer can deliberately reveal these
                            credentials from their Secure Access panel.
                            Sensitive values are not displayed here.
                        </p>

                        {secureAccess.viewed_at && (
                            <p className="mt-2 text-xs text-cyan-400">
                                Customer first viewed this access on{' '}
                                {formatDate(
                                    secureAccess.viewed_at,
                                )}
                                .
                            </p>
                        )}
                    </div>
                )}

            {secureAccess.status === 'closed' && (
                <div className="mt-5 rounded-lg border border-gray-800 bg-gray-900 p-4">
                    <p className="text-sm font-semibold text-gray-300">
                        Secure access closed
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        The sensitive login URL, username, secret, and
                        secure notes were permanently purged. The
                        non-sensitive audit record remains.
                    </p>
                </div>
            )}

            {secureAccess.status !== 'closed' && (
                <div className="mt-5 border-t border-gray-800 pt-5">
                    <button
                        type="button"
                        onClick={closeAccess}
                        disabled={closing}
                        className="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-2.5 text-sm font-semibold text-red-300 transition hover:bg-red-500/20 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {closing
                            ? 'Closing Secure Access...'
                            : 'Close Secure Access'}
                    </button>

                    <p className="mt-2 text-xs leading-5 text-gray-500">
                        Closing permanently purges all stored sensitive
                        values for this record.
                    </p>
                </div>
            )}
        </div>
    );
}

export default function SecureAccessPanel({
    customizationRequestId,
    secureAccesses = [],
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-cyan-500/20 bg-gray-900">
            <div className="border-b border-cyan-500/20 bg-cyan-500/5 px-6 py-5">
                <div className="flex items-start gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyan-500/10 text-lg">
                        🔐
                    </div>

                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-cyan-300">
                            Secure Access
                        </p>

                        <h2 className="mt-1 text-lg font-bold text-white">
                            Protected credential exchange
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-gray-400">
                            Request temporary credentials from the
                            customer or securely provide credentials to
                            them. Never use the normal conversation for
                            passwords, tokens, or other secrets.
                        </p>
                    </div>
                </div>
            </div>

            <div className="p-6">
                <div className="grid gap-6 xl:grid-cols-2">
                    <RequestSecureAccessForm
                        customizationRequestId={
                            customizationRequestId
                        }
                    />

                    <SecureHandoffForm
                        customizationRequestId={
                            customizationRequestId
                        }
                    />
                </div>

                {secureAccesses.length > 0 && (
                    <div className="mt-8 border-t border-gray-800 pt-6">
                        <div>
                            <h3 className="font-semibold text-white">
                                Secure Access History
                            </h3>

                            <p className="mt-1 text-sm text-gray-400">
                                Existing requests and secure handoffs for
                                this customization.
                            </p>
                        </div>

                        <div className="mt-5 space-y-4">
                            {secureAccesses.map((secureAccess) => (
                                <ExistingSecureAccess
                                    key={secureAccess.id}
                                    customizationRequestId={
                                        customizationRequestId
                                    }
                                    secureAccess={secureAccess}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}