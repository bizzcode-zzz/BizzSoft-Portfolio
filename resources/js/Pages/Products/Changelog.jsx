import { Head, Link } from '@inertiajs/react';
import Footer from '../../Components/Footer';
import AppLayout from '../../Layouts/AppLayout';

export default function Changelog({ product }) {
    const formatVersion = (version) => {
        if (!version) {
            return null;
        }

        const value = String(version).trim();

        return value.toLowerCase().startsWith('v')
            ? value
            : `v${value}`;
    };

    const formatDate = (value) => {
        if (!value) {
            return null;
        }

        return new Intl.DateTimeFormat('en-PH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }).format(new Date(value));
    };

    const releases = Array.isArray(product.releases)
        ? product.releases
        : [];

    return (
        <AppLayout>
            <Head title={`${product.name} Changelog`} />

            <main className="mx-auto w-full max-w-5xl px-4 pb-10 pt-24 sm:px-6 lg:px-8 lg:pb-14 lg:pt-28">
                <div className="mb-8">
                    <Link
                        href={`/products/${product.slug}`}
                        className="text-sm font-semibold text-blue-400 transition hover:text-blue-300"
                    >
                        &#8592; Back to Product
                    </Link>

                    <div className="mt-5">
                        <p className="text-sm font-semibold uppercase tracking-wide text-blue-400">
                            Release History
                        </p>

                        <h1 className="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            {product.name} Changelog
                        </h1>

                        <p className="mt-3 max-w-2xl text-sm leading-7 text-slate-400 sm:text-base">
                            Published updates, improvements, and fixes for {product.name}.
                        </p>
                    </div>
                </div>

                {releases.length > 0 ? (
                    <div className="space-y-5">
                        {releases.map((release) => {
                            const releaseVersion = formatVersion(
                                release.version,
                            );
                            const releaseDate = formatDate(
                                release.released_at,
                            );

                            return (
                                <section
                                    key={`${release.version}-${release.released_at ?? ''}`}
                                    className="rounded-2xl border border-slate-800 bg-slate-900/70 p-6"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className="text-xl font-semibold text-white">
                                                {releaseVersion}
                                            </h2>

                                            {release.is_latest && (
                                                <span className="inline-flex rounded-full bg-blue-500/10 px-2.5 py-1 text-xs font-semibold text-blue-300 ring-1 ring-inset ring-blue-500/20">
                                                    Latest
                                                </span>
                                            )}
                                        </div>

                                        {releaseDate && (
                                            <p className="text-sm text-slate-500">
                                                Released {releaseDate}
                                            </p>
                                        )}
                                    </div>

                                    {release.release_notes ? (
                                        <div className="mt-5 border-t border-slate-800 pt-5">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                What&apos;s New
                                            </p>

                                            <p className="mt-3 whitespace-pre-line text-sm leading-7 text-slate-300">
                                                {release.release_notes}
                                            </p>
                                        </div>
                                    ) : (
                                        <p className="mt-5 border-t border-slate-800 pt-5 text-sm text-slate-500">
                                            No public release notes were added for this version.
                                        </p>
                                    )}
                                </section>
                            );
                        })}
                    </div>
                ) : (
                    <section className="rounded-2xl border border-slate-800 bg-slate-900/70 p-6">
                        <p className="text-sm text-slate-400">
                            No published releases are available yet.
                        </p>
                    </section>
                )}
            </main>

            <Footer />
        </AppLayout>
    );
}