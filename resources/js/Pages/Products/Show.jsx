import {
    Head,
    Link,
    router,
    usePage,
} from '@inertiajs/react';
import { useState } from 'react';
import Footer from '../../Components/Footer';
import AppLayout from '../../Layouts/AppLayout';

export default function Show({ product }) {
    const { auth = {} } = usePage().props;
    const [ordering, setOrdering] = useState(false);

    const user = auth?.user ?? null;

    const roles = Array.isArray(user?.roles)
        ? user.roles
        : [];

    const isCustomer = roles.includes('customer');
    const isAdmin = roles.includes('admin');

    const formatPrice = (price) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2,
        }).format(Number(price ?? 0));
    };

    const formatVersion = (version) => {
        if (!version) {
            return null;
        }

        const value = String(version).trim();

        return value.toLowerCase().startsWith('v')
            ? value
            : `v${value}`;
    };

    const version = formatVersion(product.version);

    const showShortDescription =
        product.short_description &&
        product.short_description.trim().toLowerCase() !==
            product.name.trim().toLowerCase();

    const productDetails = [
        {
            label: 'Current Version',
            value: version,
        },
        {
            label: 'Built With',
            value: product.built_with,
        },
        {
            label: 'Server Requirement',
            value: product.server_requirement,
        },
        {
            label: 'Database',
            value: product.database_system,
        },
        {
            label: 'Browser Support',
            value: product.browser_support,
        },
    ].filter((detail) => detail.value);

    const includedItems = Array.isArray(product.included_items)
        ? product.included_items.filter(Boolean)
        : [];

    const handleOrder = () => {
        if (ordering) {
            return;
        }

        if (!user) {
            router.visit('/login');
            return;
        }

        if (!isCustomer) {
            return;
        }

        router.post(
            `/products/${product.slug}/orders`,
            {},
            {
                preserveScroll: true,

                onStart: () => {
                    setOrdering(true);
                },

                onFinish: () => {
                    setOrdering(false);
                },
            },
        );
    };

    const orderLabel = () => {
        if (ordering) {
            return 'Creating Order...';
        }

        if (!user) {
            return 'Login to Order';
        }

        if (isCustomer) {
            return 'Buy / Order';
        }

        if (isAdmin) {
            return 'Customer Account Required';
        }

        return 'Customer Account Required';
    };

    const orderDisabled =
        ordering || (user !== null && !isCustomer);

    return (
        <AppLayout>
            <Head title={product.name} />

            <main className="min-h-screen bg-slate-950 pb-32 text-white md:pb-0">
                <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
                    {/* Back */}

                    <Link
                        href="/products"
                        className="inline-flex items-center gap-2 text-sm font-medium text-slate-400 transition hover:text-white"
                    >
                        <span aria-hidden="true">←</span>
                        Back to Products
                    </Link>

                    {/* Header */}

                    <div className="mt-8 max-w-4xl">
                        {version && (
                            <div className="mb-4 inline-flex rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-blue-300">
                                {version}
                            </div>
                        )}

                        <h1 className="text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
                            {product.name}
                        </h1>

                        {showShortDescription && (
                            <p className="mt-4 max-w-3xl text-base leading-7 text-slate-400 sm:text-lg">
                                {product.short_description}
                            </p>
                        )}
                    </div>

                    {/* Main Product Area */}

                    <div className="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1.45fr)_minmax(340px,0.75fr)] lg:items-start">
                        {/* Left Column */}

                        <div className="space-y-6">
                            {/* Product Image */}

                            <div className="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/10">
                                {product.thumbnail_url ? (
                                    <div className="aspect-video w-full overflow-hidden bg-slate-950">
                                        <img
                                            src={product.thumbnail_url}
                                            alt={product.name}
                                            className="h-full w-full object-cover"
                                        />
                                    </div>
                                ) : (
                                    <div className="flex aspect-video items-center justify-center bg-slate-900 px-6 text-center">
                                        <div>
                                            <div className="text-4xl font-bold text-slate-700">
                                                B
                                            </div>

                                            <p className="mt-3 text-sm text-slate-500">
                                                Product preview coming soon.
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Description */}

                            {product.description && (
                                <section className="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 sm:p-8">
                                    <h2 className="text-xl font-semibold text-white">
                                        Overview
                                    </h2>

                                    <div className="mt-4 whitespace-pre-line text-sm leading-7 text-slate-300 sm:text-base">
                                        {product.description}
                                    </div>
                                </section>
                            )}
                        </div>

                        {/* Right Column */}

                        <aside className="space-y-5">
                            {/* Price / Actions */}

                            <section className="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl shadow-black/10">
                                <p className="text-sm font-medium text-slate-400">
                                    Product Price
                                </p>

                                <div className="mt-2 text-3xl font-bold tracking-tight text-white">
                                    {formatPrice(product.price)}
                                </div>

                                {/* Desktop Actions */}

                                <div className="mt-6 hidden grid-cols-1 gap-3 md:grid">
                                    {product.demo_url ? (
                                        <a
                                            href={product.demo_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-500"
                                        >
                                            View Live Demo ↗
                                        </a>
                                    ) : (
                                        <button
                                            type="button"
                                            disabled
                                            className="w-full cursor-not-allowed rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 text-sm font-semibold text-slate-500"
                                        >
                                            Live Demo — Coming Soon
                                        </button>
                                    )}

                                    <button
                                        type="button"
                                        onClick={handleOrder}
                                        disabled={orderDisabled}
                                        className={`w-full rounded-xl px-5 py-3 text-sm font-semibold transition ${
                                            orderDisabled
                                                ? 'cursor-not-allowed border border-slate-700 bg-slate-950 text-slate-500'
                                                : 'bg-emerald-600 text-white hover:bg-emerald-500'
                                        }`}
                                    >
                                        {orderLabel()}
                                    </button>
                                </div>
                            </section>

                            {/* Product Details */}

                            {productDetails.length > 0 && (
                                <section className="rounded-2xl border border-slate-800 bg-slate-900/70 p-6">
                                    <h2 className="text-lg font-semibold text-white">
                                        Product Details
                                    </h2>

                                    <div className="mt-5 divide-y divide-slate-800">
                                        {productDetails.map((detail) => (
                                            <div
                                                key={detail.label}
                                                className="grid gap-1 py-4 first:pt-0 last:pb-0 sm:grid-cols-[140px_1fr] sm:gap-4"
                                            >
                                                <dt className="text-sm text-slate-500">
                                                    {detail.label}
                                                </dt>

                                                <dd className="text-sm font-medium leading-6 text-slate-200 sm:text-right">
                                                    {detail.value}
                                                </dd>
                                            </div>
                                        ))}
                                    </div>
                                </section>
                            )}

                            {/* What's Included */}

                            {includedItems.length > 0 && (
                                <section className="rounded-2xl border border-slate-800 bg-slate-900/70 p-6">
                                    <h2 className="text-lg font-semibold text-white">
                                        What&apos;s Included
                                    </h2>

                                    <div className="mt-5 space-y-4">
                                        {includedItems.map(
                                            (item, index) => (
                                                <div
                                                    key={`${item}-${index}`}
                                                    className="flex items-start gap-3"
                                                >
                                                    <div className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-xs font-bold text-emerald-400">
                                                        ✓
                                                    </div>

                                                    <p className="text-sm leading-6 text-slate-300">
                                                        {item}
                                                    </p>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </section>
                            )}
                        </aside>
                    </div>

                    {/* Custom Work */}

                    <section className="mt-12 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 p-7 sm:p-9 lg:mt-16">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="max-w-2xl">
                                <p className="text-sm font-semibold text-blue-400">
                                    Need Custom Work?
                                </p>

                                <h2 className="mt-2 text-2xl font-bold tracking-tight text-white">
                                    Need modifications or a solution tailored
                                    to your business?
                                </h2>

                                <p className="mt-3 text-sm leading-7 text-slate-400 sm:text-base">
                                    Create a BizzSoft customer account to
                                    submit a customization request and discuss
                                    your requirements.
                                </p>
                            </div>

                            <Link
                                href="/register"
                                className="inline-flex shrink-0 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-5 py-3 text-sm font-semibold text-white transition hover:border-slate-600 hover:bg-slate-700"
                            >
                                Create Customer Account
                            </Link>
                        </div>
                    </section>
                </div>
            </main>

            {/* Mobile-only Fixed Action Bar */}

            <div className="fixed inset-x-0 bottom-0 z-50 border-t border-slate-800 bg-slate-950/95 px-4 py-3 shadow-[0_-12px_30px_rgba(0,0,0,0.35)] backdrop-blur md:hidden">
                <div className="mx-auto max-w-lg">
                    <div className="mb-2 flex items-center justify-between gap-3">
                        <span className="text-xs font-medium text-slate-500">
                            Product Price
                        </span>

                        <span className="text-lg font-bold text-white">
                            {formatPrice(product.price)}
                        </span>
                    </div>

                    <div className="grid grid-cols-2 gap-2">
                        {product.demo_url ? (
                            <a
                                href={product.demo_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-3 text-center text-xs font-semibold text-white transition hover:bg-blue-500"
                            >
                                Live Demo ↗
                            </a>
                        ) : (
                            <button
                                type="button"
                                disabled
                                className="min-h-11 cursor-not-allowed rounded-xl border border-slate-700 bg-slate-800 px-3 text-xs font-semibold text-slate-500"
                            >
                                Demo Soon
                            </button>
                        )}

                        <button
                            type="button"
                            onClick={handleOrder}
                            disabled={orderDisabled}
                            className={`min-h-11 rounded-xl px-3 text-xs font-semibold transition ${
                                orderDisabled
                                    ? 'cursor-not-allowed border border-slate-700 bg-slate-900 text-slate-500'
                                    : 'bg-emerald-600 text-white hover:bg-emerald-500'
                            }`}
                        >
                            {ordering
                                ? 'Creating...'
                                : !user
                                  ? 'Login to Order'
                                  : isCustomer
                                    ? 'Buy / Order'
                                    : 'Customer Only'}
                        </button>
                    </div>
                </div>
            </div>

            <Footer />
        </AppLayout>
    );
}