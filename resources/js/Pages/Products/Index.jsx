import { Head, Link } from '@inertiajs/react';
import Footer from '../../Components/Footer';
import AppLayout from '../../Layouts/AppLayout';

function formatCurrency(value) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(value ?? 0));
}

export default function Index({ products }) {
    return (
        <>
            <Head title="Scripts & Products" />

            <AppLayout>
                <section className="border-b border-white/10 bg-neutral-950 px-6 pb-16 pt-36 sm:px-8 lg:px-12">
                    <div className="mx-auto max-w-7xl">
                        <div className="max-w-3xl">
                            <p className="text-sm font-semibold uppercase tracking-[0.22em] text-blue-400">
                                BizzSoft Store
                            </p>

                            <h1 className="mt-5 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                                Scripts & Products
                            </h1>

                            <p className="mt-6 max-w-2xl text-base leading-8 text-neutral-400 sm:text-lg">
                                Explore production-ready BizzSoft software,
                                business systems, and web applications built for
                                real-world use.
                            </p>
                        </div>
                    </div>
                </section>

                <section className="bg-neutral-950 px-6 py-16 sm:px-8 lg:px-12">
                    <div className="mx-auto max-w-7xl">
                        <div className="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-sm font-medium text-neutral-500">
                                    Available Products
                                </p>

                                <h2 className="mt-1 text-2xl font-bold tracking-tight text-white">
                                    Software built by BizzSoft
                                </h2>
                            </div>

                            <p className="text-sm text-neutral-500">
                                {products.length}{' '}
                                {products.length === 1
                                    ? 'product'
                                    : 'products'}{' '}
                                available
                            </p>
                        </div>

                        {products.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-neutral-800 bg-neutral-900/50 px-6 py-20 text-center">
                                <div className="text-4xl">
                                    📦
                                </div>

                                <h3 className="mt-4 text-lg font-semibold text-white">
                                    No products available yet
                                </h3>

                                <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-neutral-500">
                                    New BizzSoft scripts and software products
                                    will appear here once they are published.
                                </p>
                            </div>
                        ) : (
                            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                                {products.map((product) => (
                                    <article
                                        key={product.id}
                                        className="group overflow-hidden rounded-2xl border border-neutral-800 bg-neutral-900 transition duration-300 hover:-translate-y-1 hover:border-neutral-700"
                                    >
                                        <Link
                                            href={`/products/${product.slug}`}
                                            className="block"
                                        >
                                            <div className="flex aspect-video items-center justify-center overflow-hidden border-b border-neutral-800 bg-neutral-950">
                                                {product.thumbnail_url ? (
                                                    <img
                                                        src={
                                                            product.thumbnail_url
                                                        }
                                                        alt={product.name}
                                                        className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                                    />
                                                ) : (
                                                    <div className="text-center">
                                                        <div className="text-5xl">
                                                            📦
                                                        </div>

                                                        <p className="mt-3 text-xs font-medium uppercase tracking-widest text-neutral-600">
                                                            BizzSoft Product
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        </Link>

                                        <div className="p-6">
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="min-w-0">
                                                    {product.version && (
                                                        <span className="inline-flex rounded-full border border-blue-500/20 bg-blue-500/10 px-2.5 py-1 text-xs font-semibold text-blue-300">
                                                            v{product.version}
                                                        </span>
                                                    )}

                                                    <Link
                                                        href={`/products/${product.slug}`}
                                                        className="block"
                                                    >
                                                        <h3 className="mt-3 text-xl font-bold text-white transition group-hover:text-blue-300">
                                                            {product.name}
                                                        </h3>
                                                    </Link>
                                                </div>

                                                <p className="shrink-0 text-lg font-bold text-emerald-400">
                                                    {formatCurrency(
                                                        product.price,
                                                    )}
                                                </p>
                                            </div>

                                            <p className="mt-4 line-clamp-3 min-h-18 text-sm leading-6 text-neutral-400">
                                                {product.short_description ??
                                                    'Explore this BizzSoft software product and view its full features and live demo.'}
                                            </p>

                                            <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                                                <Link
                                                    href={`/products/${product.slug}`}
                                                    className="inline-flex flex-1 items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-black transition hover:bg-neutral-200"
                                                >
                                                    View Details
                                                </Link>

                                                {product.demo_url && (
                                                    <a
                                                        href={
                                                            product.demo_url
                                                        }
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="inline-flex flex-1 items-center justify-center rounded-lg border border-neutral-700 px-4 py-2.5 text-sm font-semibold text-neutral-200 transition hover:border-neutral-600 hover:bg-neutral-800"
                                                    >
                                                        Live Demo ↗
                                                    </a>
                                                )}
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                    </div>
                </section>

                <section className="border-t border-white/10 bg-neutral-950 px-6 py-16 sm:px-8 lg:px-12">
                    <div className="mx-auto max-w-7xl rounded-2xl border border-neutral-800 bg-neutral-900 px-6 py-10 sm:px-10">
                        <div className="max-w-2xl">
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-neutral-500">
                                Custom Solution
                            </p>

                            <h2 className="mt-3 text-2xl font-bold text-white sm:text-3xl">
                                Need something built specifically for your
                                business?
                            </h2>

                            <p className="mt-4 text-sm leading-7 text-neutral-400">
                                BizzSoft also provides custom software and
                                customization services for requirements that go
                                beyond our ready-made products.
                            </p>

                            <Link
                                href="/register"
                                className="mt-6 inline-flex rounded-lg border border-neutral-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800"
                            >
                                Create Customer Account
                            </Link>
                        </div>
                    </div>
                </section>

                <Footer />
            </AppLayout>
        </>
    );
}