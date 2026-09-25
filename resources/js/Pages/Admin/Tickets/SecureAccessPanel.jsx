import { useState } from 'react';
import { useForm } from '@inertiajs/react';

const credentialTypes = [
    {
        value: 'application_login',
        label: 'Application / Script Login',
    },
    {
        value: 'hosting',
        label: 'Hosting',
    },
    {
        value: 'ssh_sftp',
        label: 'SSH / SFTP',
    },
    {
        value: 'database',
        label: 'Database',
    },
    {
        value: 'api_token',
        label: 'API Token',
    },
    {
        value: 'other',
        label: 'Other',
    },
];

function typeLabel(type) {
    return (
        credentialTypes.find((item) => item.value === type)?.label ??
        type
    );
}

function directionLabel(direction) {
    if (direction === 'customer_to_admin') {
        return 'Customer → BizzSoft';
    }

    if (direction === 'admin_to_customer') {
        return 'BizzSoft → Customer';
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

function RequestSecureAccessForm({ ticketId }) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm({
        type: 'application_login',
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

        post(`/admin/tickets/${ticketId}/secure-access`, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-amber-500/20 bg-amber-500/5 p-5"
        >
            <h3 className="text-sm font-semibold text-amber-300">
                Request Secure Access
            </h3>

            <p className="mt-1 text-sm leading-6 text-gray-400">
                Ask the customer to securely provide temporary credentials.
                Secrets should never be requested through the normal ticket
                conversation.
            </p>

            <div className="mt-4">
                <label
                    htmlFor="request-secure-type"
                    className="mb-2 block text-sm font-medium text-gray-300"
                >
                    Credential Type
                </label>

                <select
                    id="request-secure-type"
                    value={data.type}
                    onChange={(event) =>
                        setData('type', event.target.value)
                    }
                    className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm text-white outline-none transition focus:border-gray-500"
                >
                    {credentialTypes.map((type) => (
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

            <div className="mt-4">
                <label
                    htmlFor="request-secure-label"
                    className="mb-2 block text-sm font-medium text-gray-300"
                >
                    Label
                </label>

                <input
                    id="request-secure-label"
                    type="text"
                    value={data.label}
                    onChange={(event) =>
                        setData('label', event.target.value)
                    }
                    className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm text-white outline-none transition focus:border-gray-500"
                    placeholder="Example: Temporary WordPress Admin Login"
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
                className="mt-5 rounded-lg bg-amber-500 px-5 py-3 text-sm font-semibold text-black transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {processing
                    ? 'Requesting...'
                    : 'Request Secure Access'}
            </button>
        </form>
    );
}

function SecureHandoffForm({ ticketId }) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm({
        type: 'application_login',
        label: '',
        login_url: '',
        username: '',
        secret: '',
        notes: '',
    });

    const submit = (event) => {
        event.preventDefault();

        const confirmed = window.confirm(
            'Send these credentials securely to the customer?',
        );

        if (!confirmed) {
            return;
        }

        post(`/admin/tickets/${ticketId}/secure-access/handoff`, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-5"
        >
            <h3 className="text-sm font-semibold text-emerald-300">
                Send Secure Credentials
            </h3>

            <p className="mt-1 text-sm leading-6 text-gray-400">
                Securely hand temporary credentials to the customer without
                placing sensitive values in the ticket conversation.
            </p>

            <div className="mt-4">
                <label
                    htmlFor="handoff-secure-type"
                    className="mb-2 block text-sm font-medium text-gray-300"
                >
                    Credential Type
                </label>

                <select
                    id="handoff-secure-type"
                    value={data.type}
                    onChange={(event) =>
                        setData('type', event.target.value)
                    }
                    className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm text-white outline-none transition focus:border-gray-500"
                >
                    {credentialTypes.map((type) => (
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

            <div className="mt-4">
                <label
                    htmlFor="handoff-secure-label"
                    className="mb-2 block text-sm font-medium text-gray-300"
                >
                    Label
                </label>

                <input
                    id="handoff-secure-label"
                    type="text"
                    value={data.label}
                    onChange={(event) =>
                        setData('label', event.target.value)
                    }
                    className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm text-white outline-none transition focus:border-gray-500"
                    placeholder="Example: Temporary Staging Login"
                />

                {errors.label && (
                    <p className="mt-2 text-sm text-red-400">
                        {errors.label}
                    </p>
                )}
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label
                        htmlFor="handoff-login-url"
                        className="mb-2 block text-sm font-medium text-gray-300"
                    >
                        Login URL
                    </label>

                    <input
                        id="handoff-login-url"
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
                        htmlFor="handoff-username"
                        className="mb-2 block text-sm font-medium text-gray-300"
                    >
                        Username / Email
                    </label>

                    <input
                        id="handoff-username"
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
                    htmlFor="handoff-secret"
                    className="mb-2 block text-sm font-medium text-gray-300"
                >
                    Password / Secret
                </label>

                <input
                    id="handoff-secret"
                    type="password"
                    value={data.secret}
                    onChange={(event) =>
                        setData('secret', event.target.value)
                    }
                    className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm text-white outline-none transition focus:border-gray-500"
                    placeholder="Enter the secret"
                />

                {errors.secret && (
                    <p className="mt-2 text-sm text-red-400">
                        {errors.secret}
                    </p>
                )}
            </div>

            <div className="mt-4">
                <label
                    htmlFor="handoff-notes"
                    className="mb-2 block text-sm font-medium text-gray-300"
                >
                    Secure Notes
                </label>

                <textarea
                    id="handoff-notes"
                    rows="4"
                    value={data.notes}
                    onChange={(event) =>
                        setData('notes', event.target.value)
                    }
                    className="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-sm text-white outline-none transition focus:border-gray-500"
                    placeholder="Optional secure instructions..."
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
                className="mt-5 rounded-lg bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {processing
                    ? 'Sending Securely...'
                    : 'Send Secure Credentials'}
            </button>
        </form>
    );
}

function RevealCustomerCredentials({ ticketId, access }) {
    const [credentials, setCredentials] = useState(null);
    const [revealing, setRevealing] = useState(false);
    const [error, setError] = useState('');

    const reveal = async () => {
        const confirmed = window.confirm(
            'Reveal the customer credentials now?',
        );

        if (!confirmed) {
            return;
        }

        setRevealing(true);
        setError('');

        try {
            const response = await fetch(
                `/admin/tickets/${ticketId}/secure-access/${access.id}/reveal`,
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
            <div className="mt-5 border-t border-amber-500/20 pt-5">
                <div className="rounded-lg border border-amber-500/20 bg-amber-500/5 p-5">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-amber-300">
                                Customer credentials revealed
                            </p>

                            <p className="mt-1 text-sm leading-6 text-gray-400">
                                These values were deliberately retrieved from
                                the secure reveal endpoint.
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
        <div className="mt-5 border-t border-amber-500/20 pt-5">
            <p className="text-sm font-semibold text-amber-300">
                Customer submitted credentials
            </p>

            <p className="mt-1 text-sm leading-6 text-gray-400">
                Sensitive values are not included in the normal ticket page.
                Reveal them only when you need to use them.
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
                className="mt-4 rounded-lg bg-amber-500 px-5 py-3 text-sm font-semibold text-black transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {revealing
                    ? 'Revealing...'
                    : 'Reveal Customer Credentials'}
            </button>
        </div>
    );
}

function SecureAccessItem({ ticketId, access }) {
    const { patch, processing } = useForm({});

    const isCustomerToAdmin =
        access.direction === 'customer_to_admin';

    const isSubmitted = access.status === 'submitted';
    const isRequested = access.status === 'requested';
    const isClosed = access.status === 'closed';

    const closeAccess = () => {
        const confirmed = window.confirm(
            'Close this Secure Access record? The stored login URL, username, secret, and secure notes will be permanently purged.',
        );

        if (!confirmed) {
            return;
        }

        patch(
            `/admin/tickets/${ticketId}/secure-access/${access.id}/close`,
            {
                preserveScroll: true,
            },
        );
    };

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
                <div className="mt-5 border-t border-gray-800 pt-5">
                    <p className="text-sm font-semibold text-gray-300">
                        Waiting for customer
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        The customer has not submitted the requested secure
                        credentials yet.
                    </p>
                </div>
            )}

            {isCustomerToAdmin && isSubmitted && (
                <RevealCustomerCredentials
                    ticketId={ticketId}
                    access={access}
                />
            )}

            {!isCustomerToAdmin && isSubmitted && (
                <div className="mt-5 border-t border-emerald-500/20 pt-5">
                    <p className="text-sm font-semibold text-emerald-300">
                        Credentials sent to customer
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        The sensitive payload was securely stored for the
                        customer. It is not displayed in this normal page
                        response.
                    </p>
                </div>
            )}

            {!isClosed && (
                <div className="mt-5 border-t border-gray-800 pt-5">
                    <button
                        type="button"
                        onClick={closeAccess}
                        disabled={processing}
                        className="rounded-lg border border-red-500/30 px-4 py-2.5 text-sm font-semibold text-red-300 transition hover:bg-red-500/10 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {processing
                            ? 'Closing...'
                            : 'Close & Purge Secure Access'}
                    </button>
                </div>
            )}

            {isClosed && (
                <div className="mt-5 border-t border-gray-800 pt-5">
                    <p className="text-sm font-semibold text-gray-400">
                        Secure access closed
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        The sensitive payload has been permanently purged.
                        Non-sensitive audit history remains.
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
    return (
        <section className="mt-8 rounded-xl border border-gray-800 bg-gray-900 p-6">
            <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-amber-300">
                    Secure Access
                </p>

                <h2 className="mt-1 text-xl font-semibold text-white">
                    Temporary Credential Exchange
                </h2>

                <p className="mt-2 text-sm leading-6 text-gray-400">
                    Request credentials from the customer or securely send
                    temporary credentials back. Secure Access is separate from
                    the normal support conversation and does not change the
                    ticket status.
                </p>
            </div>

            <div className="mt-6 grid gap-5 lg:grid-cols-2">
                <RequestSecureAccessForm ticketId={ticketId} />

                <SecureHandoffForm ticketId={ticketId} />
            </div>

            <div className="mt-8 border-t border-gray-800 pt-6">
                <h3 className="text-sm font-semibold text-white">
                    Secure Access History
                </h3>

                {secureAccesses.length === 0 ? (
                    <div className="mt-4 rounded-lg border border-dashed border-gray-700 bg-gray-950/60 px-4 py-8 text-center">
                        <p className="text-sm text-gray-500">
                            No Secure Access records for this ticket yet.
                        </p>
                    </div>
                ) : (
                    <div className="mt-4 space-y-4">
                        {secureAccesses.map((access) => (
                            <SecureAccessItem
                                key={access.id}
                                ticketId={ticketId}
                                access={access}
                            />
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}