import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

function secureAccessTypeLabel(type) {
    const labels = {
        hosting: 'Hosting',
        ssh_sftp: 'SSH / SFTP',
        database: 'Database',
        api_token: 'API Token',
        other: 'Other',
    };

    return labels[type] ?? type;
}

function secureAccessStatusLabel(status) {
    const labels = {
        requested: 'Requested',
        submitted: 'Submitted',
        closed: 'Closed',
    };

    return labels[status] ?? status;
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

function RequestedAccessForm({
    customizationRequestId,
    secureAccess,
}) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm({
        login_url: '',
        username: '',
        secret: '',
        notes: '',
    });

    const [showSecret, setShowSecret] = useState(false);

    const submit = (event) => {
        event.preventDefault();

        const confirmed = window.confirm(
            'Submit these credentials securely to BizzSoft?',
        );

        if (!confirmed) {
            return;
        }

        post(
            `/customizations/${customizationRequestId}/secure-access/${secureAccess.id}/submit`,
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <form
            onSubmit={submit}
            className="mt-5 rounded-xl border border-amber-500/20 bg-amber-500/5 p-5"
        >
            <div>
                <p className="text-sm font-semibold text-amber-300">
                    Secure information requested
                </p>

                <p className="mt-1 text-sm leading-6 text-gray-400">
                    BizzSoft requested secure access for this
                    customization. Submit the credentials here instead of
                    sending them through the normal conversation.
                </p>
            </div>

            <div className="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label
                        htmlFor={`secure-login-url-${secureAccess.id}`}
                        className="block text-sm font-medium text-gray-300"
                    >
                        Login URL
                    </label>

                    <input
                        id={`secure-login-url-${secureAccess.id}`}
                        type="text"
                        maxLength="2000"
                        value={data.login_url}
                        onChange={(event) =>
                            setData('login_url', event.target.value)
                        }
                        placeholder="https://example.com/login"
                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-gray-500"
                    />

                    {errors.login_url && (
                        <p className="mt-1 text-sm text-red-400">
                            {errors.login_url}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor={`secure-username-${secureAccess.id}`}
                        className="block text-sm font-medium text-gray-300"
                    >
                        Username / Email
                    </label>

                    <input
                        id={`secure-username-${secureAccess.id}`}
                        type="text"
                        maxLength="1000"
                        value={data.username}
                        onChange={(event) =>
                            setData('username', event.target.value)
                        }
                        placeholder="Account username or email"
                        className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-gray-500"
                    />

                    {errors.username && (
                        <p className="mt-1 text-sm text-red-400">
                            {errors.username}
                        </p>
                    )}
                </div>
            </div>

            <div className="mt-4">
                <label
                    htmlFor={`secure-secret-${secureAccess.id}`}
                    className="block text-sm font-medium text-gray-300"
                >
                    Password / Token / Secret
                </label>

                <div className="mt-2 flex gap-2">
                    <input
                        id={`secure-secret-${secureAccess.id}`}
                        type={showSecret ? 'text' : 'password'}
                        maxLength="10000"
                        value={data.secret}
                        onChange={(event) =>
                            setData('secret', event.target.value)
                        }
                        placeholder="Enter the requested secret"
                        className="min-w-0 flex-1 rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-gray-500"
                    />

                    <button
                        type="button"
                        onClick={() => setShowSecret((current) => !current)}
                        className="shrink-0 rounded-lg border border-gray-700 bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-300 transition hover:bg-gray-800"
                    >
                        {showSecret ? 'Hide' : 'Show'}
                    </button>
                </div>

                {errors.secret && (
                    <p className="mt-1 text-sm text-red-400">
                        {errors.secret}
                    </p>
                )}
            </div>

            <div className="mt-4">
                <label
                    htmlFor={`secure-notes-${secureAccess.id}`}
                    className="block text-sm font-medium text-gray-300"
                >
                    Secure Notes
                </label>

                <textarea
                    id={`secure-notes-${secureAccess.id}`}
                    rows="4"
                    maxLength="5000"
                    value={data.notes}
                    onChange={(event) =>
                        setData('notes', event.target.value)
                    }
                    placeholder="Optional instructions related to this access..."
                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-3 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-gray-500"
                />

                {errors.notes && (
                    <p className="mt-1 text-sm text-red-400">
                        {errors.notes}
                    </p>
                )}
            </div>

            <div className="mt-5 rounded-lg border border-gray-800 bg-gray-950/60 p-4">
                <p className="text-xs leading-5 text-gray-500">
                    Use temporary or restricted credentials whenever
                    possible. Sensitive values submitted here are stored
                    separately from the normal conversation.
                </p>
            </div>

            <button
                type="submit"
                disabled={processing}
                className="mt-5 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-950 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {processing
                    ? 'Submitting Securely...'
                    : 'Submit Secure Access'}
            </button>
        </form>
    );
}

function AdminHandoffCard({
    customizationRequestId,
    secureAccess,
}) {
    const [revealedAccess, setRevealedAccess] = useState(null);
    const [revealing, setRevealing] = useState(false);
    const [revealError, setRevealError] = useState('');
    const [showSecret, setShowSecret] = useState(false);

    const reveal = async () => {
        const confirmed = window.confirm(
            'Reveal these secure credentials? Make sure nobody else can see your screen.',
        );

        if (!confirmed) {
            return;
        }

        setRevealing(true);
        setRevealError('');

        try {
            const response = await fetch(
                `/customizations/${customizationRequestId}/secure-access/${secureAccess.id}/reveal`,
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

    const hide = () => {
        setRevealedAccess(null);
        setShowSecret(false);
    };

    return (
        <div className="mt-5 rounded-xl border border-cyan-500/20 bg-cyan-500/5 p-5">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p className="text-sm font-semibold text-cyan-300">
                        BizzSoft sent secure access
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-400">
                        These credentials were provided securely by
                        BizzSoft. Reveal them only when you are ready to
                        use them.
                    </p>
                </div>

                {!revealedAccess && (
                    <button
                        type="button"
                        onClick={reveal}
                        disabled={revealing}
                        className="shrink-0 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-950 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {revealing
                            ? 'Revealing...'
                            : 'Reveal Secure Access'}
                    </button>
                )}
            </div>

            {revealError && (
                <p className="mt-4 text-sm text-red-400">
                    {revealError}
                </p>
            )}

            {revealedAccess && (
                <div className="mt-5 rounded-xl border border-cyan-500/20 bg-gray-950/80 p-5">
                    <div className="flex items-center justify-between gap-4">
                        <p className="text-sm font-semibold text-white">
                            Revealed credentials
                        </p>

                        <button
                            type="button"
                            onClick={hide}
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
                                {revealedAccess.login_url || '—'}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Username / Email
                            </p>

                            <p className="mt-1 break-all text-sm text-gray-200">
                                {revealedAccess.username || '—'}
                            </p>
                        </div>
                    </div>

                    <div className="mt-4">
                        <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Password / Token / Secret
                        </p>

                        <div className="mt-2 flex items-center gap-3">
                            <code className="min-w-0 flex-1 break-all rounded-lg border border-gray-800 bg-gray-900 px-3 py-2.5 text-sm text-gray-200">
                                {showSecret
                                    ? revealedAccess.secret
                                    : '••••••••••••'}
                            </code>

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

                    <div className="mt-5 rounded-lg border border-amber-500/20 bg-amber-500/5 p-4">
                        <p className="text-xs leading-5 text-amber-300">
                            If this is a temporary password, change it
                            after signing in when appropriate. Once this
                            Secure Access record is closed, BizzSoft
                            permanently purges the stored sensitive
                            values.
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
}

export default function SecureAccessPanel({
    customizationRequestId,
    secureAccesses = [],
}) {
    if (secureAccesses.length === 0) {
        return null;
    }

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
                            Use this area for passwords, access tokens,
                            hosting logins, database credentials, or
                            other sensitive access information.
                        </p>
                    </div>
                </div>
            </div>

            <div className="space-y-5 p-6">
                <div className="rounded-lg border border-gray-800 bg-gray-950/60 p-4">
                    <p className="text-sm font-semibold text-gray-200">
                        Keep credentials out of Conversation
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        Normal messages are for project communication.
                        Sensitive credentials should only be submitted or
                        revealed through Secure Access.
                    </p>
                </div>

                {secureAccesses.map((secureAccess) => (
                    <div
                        key={secureAccess.id}
                        className="rounded-xl border border-gray-800 bg-gray-950/40 p-5"
                    >
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    {secureAccess.direction ===
                                    'customer_to_admin'
                                        ? 'Requested by BizzSoft'
                                        : 'Provided by BizzSoft'}
                                </p>

                                <h3 className="mt-1 text-base font-semibold text-white">
                                    {secureAccess.label}
                                </h3>

                                <p className="mt-1 text-sm text-gray-500">
                                    {secureAccessTypeLabel(
                                        secureAccess.type,
                                    )}
                                </p>
                            </div>

                            <span
                                className={`inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${statusClasses(
                                    secureAccess.status,
                                )}`}
                            >
                                {secureAccessStatusLabel(
                                    secureAccess.status,
                                )}
                            </span>
                        </div>

                        {secureAccess.submitted_at && (
                            <p className="mt-4 text-xs text-gray-500">
                                Submitted:{' '}
                                {formatDate(
                                    secureAccess.submitted_at,
                                )}
                            </p>
                        )}

                        {secureAccess.viewed_at && (
                            <p className="mt-1 text-xs text-gray-500">
                                First viewed:{' '}
                                {formatDate(secureAccess.viewed_at)}
                            </p>
                        )}

                        {secureAccess.closed_at && (
                            <p className="mt-1 text-xs text-gray-500">
                                Closed:{' '}
                                {formatDate(secureAccess.closed_at)}
                            </p>
                        )}

                        {secureAccess.direction ===
                            'customer_to_admin' &&
                            secureAccess.status === 'requested' && (
                                <RequestedAccessForm
                                    customizationRequestId={
                                        customizationRequestId
                                    }
                                    secureAccess={secureAccess}
                                />
                            )}

                        {secureAccess.direction ===
                            'customer_to_admin' &&
                            secureAccess.status === 'submitted' && (
                                <div className="mt-5 rounded-lg border border-emerald-500/20 bg-emerald-500/5 p-4">
                                    <p className="text-sm font-semibold text-emerald-300">
                                        Secure access submitted
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-gray-400">
                                        Your credentials were submitted
                                        securely to BizzSoft. Sensitive
                                        values are not displayed on this
                                        page.
                                    </p>
                                </div>
                            )}

                        {secureAccess.direction ===
                            'admin_to_customer' &&
                            secureAccess.status === 'submitted' && (
                                <AdminHandoffCard
                                    customizationRequestId={
                                        customizationRequestId
                                    }
                                    secureAccess={secureAccess}
                                />
                            )}

                        {secureAccess.status === 'closed' && (
                            <div className="mt-5 rounded-lg border border-gray-800 bg-gray-900 p-4">
                                <p className="text-sm font-semibold text-gray-300">
                                    Secure access closed
                                </p>

                                <p className="mt-1 text-sm leading-6 text-gray-500">
                                    This Secure Access record is closed.
                                    The stored login URL, username,
                                    secret, and secure notes have been
                                    permanently purged.
                                </p>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </section>
    );
}