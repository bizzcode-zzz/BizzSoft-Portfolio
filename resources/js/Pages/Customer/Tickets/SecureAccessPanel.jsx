import { useState } from 'react';
import { useForm } from '@inertiajs/react';

function typeLabel(type) {
    const labels = {
        hosting: 'Hosting',
        application_login: 'Application / Script Login',
        ssh_sftp: 'SSH / SFTP',
        database: 'Database',
        api_token: 'API Token',
        other: 'Other',
    };

    return labels[type] ?? type;
}

function directionLabel(direction) {
    if (direction === 'customer_to_admin') {
        return 'You → BizzSoft';
    }

    if (direction === 'admin_to_customer') {
        return 'BizzSoft → You';
    }

    return direction;
}

function statusLabel(status) {
    const labels = {
        requested: 'Requested',
        submitted: 'Submitted',
        closed: 'Closed',
    };

    return labels[status] ?? status;
}

function formatDate(date) {
    if (!date) {
        return null;
    }

    return new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}

function RequestedAccessForm({ ticketId, access }) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm({
        login_url: '',
        username: '',
        secret: '',
        notes: '',
    });

    const submit = (event) => {
        event.preventDefault();

        const confirmed = window.confirm(
            'Submit these credentials securely to BizzSoft?',
        );

        if (!confirmed) {
            return;
        }

        post(
            `/tickets/${ticketId}/secure-access/${access.id}/submit`,
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
            className="mt-5 border-t border-amber-500/20 pt-5"
        >
            <p className="text-sm font-semibold text-amber-300">
                BizzSoft requested secure access
            </p>

            <p className="mt-1 text-sm leading-6 text-gray-400">
                Enter the requested credentials here. Do not send passwords,
                API tokens, database credentials, or other secrets through the
                normal ticket conversation.
            </p>

            <div className="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label
                        htmlFor={`secure-login-url-${access.id}`}
                        className="mb-2 block text-sm font-medium text-gray-300"
                    >
                        Login URL
                    </label>

                    <input
                        id={`secure-login-url-${access.id}`}
                        type="text"
                        value={data.login_url}
                        onChange={(event) =>
                            setData('login_url', event.target.value)
                        }
                        className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm text-white outline-none transition focus:border-gray-500"
                        placeholder="https://example.com/login"
                    />

                    {errors.login_url && (
                        <p className="mt-2 text-sm text-red-400">
                            {errors.login_url}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor={`secure-username-${access.id}`}
                        className="mb-2 block text-sm font-medium text-gray-300"
                    >
                        Username / Email
                    </label>

                    <input
                        id={`secure-username-${access.id}`}
                        type="text"
                        value={data.username}
                        onChange={(event) =>
                            setData('username', event.target.value)
                        }
                        className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm text-white outline-none transition focus:border-gray-500"
                        placeholder="Username or email"
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
                    htmlFor={`secure-secret-${access.id}`}
                    className="mb-2 block text-sm font-medium text-gray-300"
                >
                    Password / Secret
                </label>

                <input
                    id={`secure-secret-${access.id}`}
                    type="password"
                    value={data.secret}
                    onChange={(event) =>
                        setData('secret', event.target.value)
                    }
                    className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm text-white outline-none transition focus:border-gray-500"
                    placeholder="Enter the requested secret"
                />

                {errors.secret && (
                    <p className="mt-2 text-sm text-red-400">
                        {errors.secret}
                    </p>
                )}
            </div>

            <div className="mt-4">
                <label
                    htmlFor={`secure-notes-${access.id}`}
                    className="mb-2 block text-sm font-medium text-gray-300"
                >
                    Secure Notes
                </label>

                <textarea
                    id={`secure-notes-${access.id}`}
                    rows="4"
                    value={data.notes}
                    onChange={(event) =>
                        setData('notes', event.target.value)
                    }
                    className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm text-white outline-none transition focus:border-gray-500"
                    placeholder="Optional secure instructions or details..."
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
                className="mt-5 rounded-lg bg-amber-500 px-5 py-3 text-sm font-semibold text-black transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {processing
                    ? 'Submitting Securely...'
                    : 'Submit Secure Credentials'}
            </button>
        </form>
    );
}

function RevealAccess({ ticketId, access }) {
    const [credentials, setCredentials] = useState(null);
    const [revealing, setRevealing] = useState(false);
    const [error, setError] = useState('');

    const reveal = async () => {
        const confirmed = window.confirm(
            'Reveal these secure credentials now?',
        );

        if (!confirmed) {
            return;
        }

        setRevealing(true);
        setError('');

        try {
            const response = await fetch(
                `/tickets/${ticketId}/secure-access/${access.id}/reveal`,
                {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                },
            );

            if (!response.ok) {
                throw new Error(
                    'The secure credentials could not be revealed.',
                );
            }

            const payload = await response.json();

            setCredentials(payload);
        } catch {
            setError(
                'The secure credentials could not be revealed. Please refresh the page and try again.',
            );
        } finally {
            setRevealing(false);
        }
    };

    if (credentials) {
        return (
            <div className="mt-5 border-t border-emerald-500/20 pt-5">
                <div className="rounded-lg border border-emerald-500/20 bg-emerald-500/5 p-5">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-emerald-300">
                                Secure credentials revealed
                            </p>

                            <p className="mt-1 text-sm leading-6 text-gray-400">
                                These credentials were deliberately retrieved
                                from the secure access endpoint.
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={() => setCredentials(null)}
                            className="w-fit rounded-lg border border-gray-700 px-3 py-2 text-xs font-semibold text-gray-300 transition hover:bg-gray-800"
                        >
                            Hide
                        </button>
                    </div>

                    <dl className="mt-5 space-y-4">
                        {credentials.login_url && (
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Login URL
                                </dt>

                                <dd className="mt-1 break-all text-sm text-white">
                                    {credentials.login_url}
                                </dd>
                            </div>
                        )}

                        {credentials.username && (
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Username / Email
                                </dt>

                                <dd className="mt-1 break-all text-sm text-white">
                                    {credentials.username}
                                </dd>
                            </div>
                        )}

                        <div>
                            <dt className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Password / Secret
                            </dt>

                            <dd className="mt-1 break-all rounded-lg border border-gray-700 bg-gray-950 p-3 font-mono text-sm text-white">
                                {credentials.secret}
                            </dd>
                        </div>

                        {credentials.notes && (
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Secure Notes
                                </dt>

                                <dd className="mt-1 whitespace-pre-wrap text-sm leading-6 text-gray-300">
                                    {credentials.notes}
                                </dd>
                            </div>
                        )}
                    </dl>
                </div>
            </div>
        );
    }

    return (
        <div className="mt-5 border-t border-emerald-500/20 pt-5">
            <p className="text-sm font-semibold text-emerald-300">
                BizzSoft sent secure credentials
            </p>

            <p className="mt-1 text-sm leading-6 text-gray-400">
                The sensitive values are not included in this page. Use the
                button below when you are ready to deliberately reveal them.
            </p>

            {error && (
                <p className="mt-3 text-sm text-red-400">
                    {error}
                </p>
            )}

            <button
                type="button"
                onClick={reveal}
                disabled={revealing}
                className="mt-4 rounded-lg bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {revealing
                    ? 'Revealing...'
                    : 'Reveal Secure Credentials'}
            </button>
        </div>
    );
}

function SecureAccessItem({ ticketId, access }) {
    const isCustomerToAdmin =
        access.direction === 'customer_to_admin';

    const isAdminToCustomer =
        access.direction === 'admin_to_customer';

    const isRequested = access.status === 'requested';
    const isSubmitted = access.status === 'submitted';
    const isClosed = access.status === 'closed';

    return (
        <div className="rounded-xl border border-gray-800 bg-gray-950/60 p-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        {directionLabel(access.direction)}
                    </p>

                    <h3 className="mt-1 font-semibold text-white">
                        {access.label}
                    </h3>

                    <p className="mt-1 text-sm text-gray-400">
                        {typeLabel(access.type)}
                    </p>
                </div>

                <span className="w-fit rounded-full border border-gray-700 bg-gray-900 px-3 py-1 text-xs font-semibold text-gray-300">
                    {statusLabel(access.status)}
                </span>
            </div>

            <div className="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-gray-500">
                {access.submitted_at && (
                    <span>
                        Submitted: {formatDate(access.submitted_at)}
                    </span>
                )}

                {access.viewed_at && (
                    <span>
                        First viewed: {formatDate(access.viewed_at)}
                    </span>
                )}

                {access.closed_at && (
                    <span>
                        Closed: {formatDate(access.closed_at)}
                    </span>
                )}
            </div>

            {isCustomerToAdmin && isRequested && (
                <RequestedAccessForm
                    ticketId={ticketId}
                    access={access}
                />
            )}

            {isCustomerToAdmin && isSubmitted && (
                <div className="mt-5 border-t border-gray-800 pt-5">
                    <p className="text-sm font-semibold text-gray-300">
                        Credentials submitted securely
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        Your credentials were submitted to BizzSoft through
                        Secure Access. Sensitive values are not displayed on
                        this page.
                    </p>
                </div>
            )}

            {isAdminToCustomer && isSubmitted && (
                <RevealAccess
                    ticketId={ticketId}
                    access={access}
                />
            )}

            {isClosed && (
                <div className="mt-5 border-t border-gray-800 pt-5">
                    <p className="text-sm font-semibold text-gray-400">
                        Secure access closed
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        The sensitive credential payload has been permanently
                        purged. Only the non-sensitive audit history remains.
                    </p>
                </div>
            )}
        </div>
    );
}

export default function SecureAccessPanel({
    ticketId,
    secureAccesses = [],
}) {
    if (secureAccesses.length === 0) {
        return null;
    }

    return (
        <section className="mt-8 rounded-xl border border-gray-800 bg-gray-900 p-6">
            <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-amber-300">
                    Secure Access
                </p>

                <h2 className="mt-1 text-xl font-semibold text-white">
                    Temporary Credentials
                </h2>

                <p className="mt-2 text-sm leading-6 text-gray-400">
                    Use Secure Access for passwords, login credentials, API
                    tokens, database access, and other sensitive information.
                    Never place secrets in the normal ticket conversation.
                </p>
            </div>

            <div className="mt-6 space-y-4">
                {secureAccesses.map((access) => (
                    <SecureAccessItem
                        key={access.id}
                        ticketId={ticketId}
                        access={access}
                    />
                ))}
            </div>
        </section>
    );
}