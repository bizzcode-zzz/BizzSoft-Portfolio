import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function Index({ adjustments }) {
    return (
        <AdminLayout>
            <Head title="Payment Adjustments" />
            <div className="mx-auto max-w-7xl space-y-6 text-gray-200">
                <h1 className="text-2xl font-bold">Payment Adjustments</h1>
                <p>Review refunds, chargebacks and reversals. Holds affect only the linked purchase.</p>
                <div className="overflow-x-auto rounded-xl border border-gray-800">
                    <table className="w-full text-left text-sm">
                        <thead><tr>
                            <th className="p-4">Adjustment</th><th>Purchase</th><th>Provider status</th><th>Access hold</th><th>Review</th>
                        </tr></thead>
                        <tbody>
                            {adjustments.data.map((item) => (
                                <tr key={item.id} className="border-t border-gray-800">
                                    <td className="p-4"><Link className="text-blue-300 underline" href={'/admin/payment-adjustments/' + item.id}>{item.provider_adjustment_id}</Link></td>
                                    <td>{item.order_id ? <Link href={'/admin/orders/' + item.order_id}>Order #{item.order_id}</Link> : item.payment_id ? 'Matched payment #' + item.payment_id : 'Unmatched'}</td>
                                    <td>{item.snapshot.action} / {item.snapshot.status}</td>
                                    <td>{item.hold_active ? 'Suspended' : 'No hold from this adjustment'}</td>
                                    <td>{item.review_required ? 'Required' : 'No outstanding review'}</td>
                                </tr>
                            ))}
                            {!adjustments.data.length && <tr><td className="p-4" colSpan={5}>No adjustments received.</td></tr>}
                        </tbody>
                    </table>
                </div>
                <nav className="flex gap-6" aria-label="Pagination">
                    {adjustments.prev_page_url && <Link href={adjustments.prev_page_url}>Previous</Link>}
                    <span>Page {adjustments.current_page} of {adjustments.last_page}</span>
                    {adjustments.next_page_url && <Link href={adjustments.next_page_url}>Next</Link>}
                </nav>
            </div>
        </AdminLayout>
    );
}
