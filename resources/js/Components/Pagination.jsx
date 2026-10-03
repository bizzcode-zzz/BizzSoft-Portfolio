import { Link } from '@inertiajs/react';

export default function Pagination({ paginator, label = 'items' }) {
    if (!paginator || paginator.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex flex-col gap-3 rounded-xl border border-gray-800 bg-gray-900 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-gray-500">
                Showing {paginator.from ?? 0} to {paginator.to ?? 0} of{' '}
                {paginator.total ?? 0} {label}
            </p>

            <div className="flex items-center gap-2">
                {paginator.prev_page_url ? (
                    <Link
                        href={paginator.prev_page_url}
                        preserveScroll
                        className="rounded-lg border border-gray-700 px-3 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                    >
                        Previous
                    </Link>
                ) : (
                    <span className="cursor-not-allowed rounded-lg border border-gray-800 px-3 py-2 text-sm font-medium text-gray-600">
                        Previous
                    </span>
                )}

                <span className="px-2 text-sm text-gray-500">
                    Page {paginator.current_page} of {paginator.last_page}
                </span>

                {paginator.next_page_url ? (
                    <Link
                        href={paginator.next_page_url}
                        preserveScroll
                        className="rounded-lg border border-gray-700 px-3 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-800 hover:text-white"
                    >
                        Next
                    </Link>
                ) : (
                    <span className="cursor-not-allowed rounded-lg border border-gray-800 px-3 py-2 text-sm font-medium text-gray-600">
                        Next
                    </span>
                )}
            </div>
        </div>
    );
}
