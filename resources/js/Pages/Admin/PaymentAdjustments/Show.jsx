import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function Show({ adjustment }) {
    const form = useForm({
        action: 'review', reason: '', last_event_id: adjustment.last_event_id,
        history_count: adjustment.history.length,
    });
    useEffect(() => {
        form.setData((data) => ({
            ...data, last_event_id: adjustment.last_event_id, history_count: adjustment.history.length,
        }));
    }, [adjustment.last_event_id, adjustment.history.length]);
    return (
        <AdminLayout>
            <Head title="Review Payment Adjustment" />
            <div className="mx-auto max-w-5xl space-y-6 text-gray-200">
                <Link className="text-blue-300" href="/admin/payment-adjustments">Back to adjustments</Link>
                <h1 className="text-2xl font-bold">{adjustment.provider_adjustment_id}</h1>
                <p>Transaction: {adjustment.provider_transaction_id}</p>
                <p>Purchase: {adjustment.order_id ? <Link className="underline" href={'/admin/orders/' + adjustment.order_id}>Order #{adjustment.order_id}</Link> : adjustment.payment_id ? 'Matched payment #' + adjustment.payment_id : 'Unmatched / identity verification required'}</p>
                <p>Hold: <strong>{adjustment.hold_active ? 'Active' : 'Inactive'}</strong> · Review: {adjustment.review_required ? 'Required' : 'Not outstanding'}</p>
                <p>Decision: {adjustment.decision}</p>
                <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4">
                    Record the provider evidence and business reason for your decision. Partial refunds, tax adjustments,
                    missing identity and reversals require review. A review note does not change access.
                    Restore clears only this hold; other holds and manual license revocations remain effective.
                    Missing or conflicting purchase identity cannot be bypassed by a suspension reason.
                </div>
                <form className="space-y-4" onSubmit={(event) => {
                    event.preventDefault();
                    form.post('/admin/payment-adjustments/' + adjustment.id + '/decision', {
                        preserveScroll: true, onSuccess: () => form.reset('reason'),
                    });
                }}>
                    <label className="block">Decision
                        <select className="mt-2 block w-full rounded bg-gray-900 p-3" value={form.data.action} onChange={(event) => form.setData('action', event.target.value)}>
                            <option value="review">Record review note — access unchanged</option>
                            <option value="suspend">Suspend this purchase</option>
                            <option value="restore" disabled={!adjustment.hold_active}>Restore this adjustment's hold</option>
                        </select>
                    </label>
                    <label className="block">Reason and evidence (required)
                        <textarea className="mt-2 block w-full rounded bg-gray-900 p-3" required minLength={3} maxLength={2000}
                            value={form.data.reason} onChange={(event) => form.setData('reason', event.target.value)} />
                    </label>
                    {Object.entries(form.errors).map(([key, error]) => <p key={key} className="text-red-300" role="alert">{error}</p>)}
                    <button disabled={form.processing} className="rounded bg-blue-600 px-5 py-3 disabled:opacity-50">Record decision</button>
                </form>
                <details><summary className="cursor-pointer font-semibold">Current provider details</summary>
                    <pre className="mt-3 overflow-auto rounded bg-gray-900 p-4 text-xs">{JSON.stringify(adjustment.snapshot, null, 2)}</pre>
                </details>
                <h2 className="text-xl font-semibold">Audit history</h2>
                {adjustment.history.map((entry, index) => (
                    <details key={index} className="rounded border border-gray-800 p-4">
                        <summary className="cursor-pointer">
                            {entry.kind === 'admin'
                                ? 'Admin #' + entry.admin_id + ': ' + entry.action + ' · ' + entry.at
                                : entry.event_type + ' · ' + entry.event_id}
                        </summary>
                        <pre className="mt-3 overflow-auto text-xs">{JSON.stringify(entry, null, 2)}</pre>
                    </details>
                ))}
            </div>
        </AdminLayout>
    );
}
