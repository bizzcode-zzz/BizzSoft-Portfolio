import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

const defaultIncludedItems = [
    'Full source code',
    'Database files',
    'Frontend/backend assets',
    'Basic product documentation',
    'Secure digital delivery',
];

export default function Edit({ product, statuses }) {
    const form = useForm({
        _method: 'put',

        name: product.name ?? '',
        slug: product.slug ?? '',
        short_description: product.short_description ?? '',
        description: product.description ?? '',
        price: product.price ?? '',
        status: product.status ?? 'draft',
        version: product.version ?? '',
        demo_url: product.demo_url ?? '',
        thumbnail: null,

        built_with: product.built_with ?? '',
        server_requirement: product.server_requirement ?? '',
        database_system: product.database_system ?? '',
        browser_support: product.browser_support ?? '',

        included_items:
            product.included_items?.length > 0
                ? [...product.included_items]
                : [...defaultIncludedItems],
    });

    const submit = (event) => {
        event.preventDefault();

        form.post(`/admin/products/${product.slug}`, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const updateIncludedItem = (index, value) => {
        const items = [...form.data.included_items];

        items[index] = value;

        form.setData('included_items', items);
    };

    const addIncludedItem = () => {
        if (form.data.included_items.length >= 20) {
            return;
        }

        form.setData('included_items', [
            ...form.data.included_items,
            '',
        ]);
    };

    const removeIncludedItem = (index) => {
        form.setData(
            'included_items',
            form.data.included_items.filter(
                (_, itemIndex) => itemIndex !== index,
            ),
        );
    };

    return (
        <AdminLayout>
            <Head title={`Edit ${product.name}`} />

            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-gray-400">
                            Scripts / Products
                        </p>

                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-white">
                            Edit Product
                        </h1>

                        <p className="mt-2 text-sm leading-6 text-gray-400">
                            Update product information, technical details,
                            package contents, pricing, and availability.
                        </p>
                    </div>

                    <Link
                        href={`/admin/products/${product.slug}`}
                        className="text-sm font-medium text-gray-400 transition hover:text-white"
                    >
                        ← Back to Product
                    </Link>
                </div>

                <form
                    onSubmit={submit}
                    className="space-y-8"
                >
                    {/* General Information */}

                    <section className="space-y-6 rounded-xl border border-gray-800 bg-gray-900 p-6">
                        <div>
                            <p className="text-sm font-medium text-gray-500">
                                General Information
                            </p>

                            <h2 className="mt-1 text-lg font-semibold text-white">
                                Product Information
                            </h2>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="name"
                                    className="text-sm font-medium text-gray-300"
                                >
                                    Product Name
                                </label>

                                <input
                                    id="name"
                                    type="text"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition focus:border-blue-500"
                                />

                                {form.errors.name && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {form.errors.name}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="slug"
                                    className="text-sm font-medium text-gray-300"
                                >
                                    Slug
                                </label>

                                <input
                                    id="slug"
                                    type="text"
                                    value={form.data.slug}
                                    onChange={(event) =>
                                        form.setData(
                                            'slug',
                                            event.target.value,
                                        )
                                    }
                                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition focus:border-blue-500"
                                />

                                <p className="mt-2 text-xs text-gray-500">
                                    Public URL: /products/{form.data.slug}
                                </p>

                                {form.errors.slug && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {form.errors.slug}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div>
                            <label
                                htmlFor="short_description"
                                className="text-sm font-medium text-gray-300"
                            >
                                Short Description
                            </label>

                            <textarea
                                id="short_description"
                                rows="3"
                                value={form.data.short_description}
                                onChange={(event) =>
                                    form.setData(
                                        'short_description',
                                        event.target.value,
                                    )
                                }
                                className="mt-2 w-full resize-none rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition focus:border-blue-500"
                            />

                            {form.errors.short_description && (
                                <p className="mt-2 text-sm text-red-400">
                                    {form.errors.short_description}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="description"
                                className="text-sm font-medium text-gray-300"
                            >
                                Full Description
                            </label>

                            <textarea
                                id="description"
                                rows="8"
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition focus:border-blue-500"
                            />

                            {form.errors.description && (
                                <p className="mt-2 text-sm text-red-400">
                                    {form.errors.description}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-6 md:grid-cols-3">
                            <div>
                                <label
                                    htmlFor="price"
                                    className="text-sm font-medium text-gray-300"
                                >
                                    Price
                                </label>

                                <div className="relative mt-2">
                                    <span className="pointer-events-none absolute inset-y-0 left-4 flex items-center text-gray-500">
                                        ₱
                                    </span>

                                    <input
                                        id="price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.data.price}
                                        onChange={(event) =>
                                            form.setData(
                                                'price',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-lg border border-gray-700 bg-gray-950 py-3 pl-9 pr-4 text-white outline-none transition focus:border-blue-500"
                                    />
                                </div>

                                {form.errors.price && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {form.errors.price}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="version"
                                    className="text-sm font-medium text-gray-300"
                                >
                                    Version
                                </label>

                                <input
                                    id="version"
                                    type="text"
                                    value={form.data.version}
                                    onChange={(event) =>
                                        form.setData(
                                            'version',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="5.0.0"
                                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition focus:border-blue-500"
                                />

                                {form.errors.version && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {form.errors.version}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="status"
                                    className="text-sm font-medium text-gray-300"
                                >
                                    Status
                                </label>

                                <select
                                    id="status"
                                    value={form.data.status}
                                    onChange={(event) =>
                                        form.setData(
                                            'status',
                                            event.target.value,
                                        )
                                    }
                                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition focus:border-blue-500"
                                >
                                    {statuses.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </select>

                                {form.errors.status && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {form.errors.status}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div>
                            <label
                                htmlFor="demo_url"
                                className="text-sm font-medium text-gray-300"
                            >
                                Live Demo URL
                            </label>

                            <input
                                id="demo_url"
                                type="url"
                                value={form.data.demo_url}
                                onChange={(event) =>
                                    form.setData(
                                        'demo_url',
                                        event.target.value,
                                    )
                                }
                                placeholder="https://v5.bizzsoft.com"
                                className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500"
                            />

                            {form.errors.demo_url && (
                                <p className="mt-2 text-sm text-red-400">
                                    {form.errors.demo_url}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="thumbnail"
                                className="text-sm font-medium text-gray-300"
                            >
                                Product Thumbnail
                            </label>

                            {product.thumbnail_url && (
                                <div className="mt-3 overflow-hidden rounded-lg border border-gray-800 bg-gray-950 p-3">
                                    <img
                                        src={product.thumbnail_url}
                                        alt={product.name}
                                        className="max-h-72 w-full object-contain"
                                    />
                                </div>
                            )}

                            <div className="mt-3 rounded-lg border border-dashed border-gray-700 bg-gray-950 p-4">
                                <input
                                    id="thumbnail"
                                    type="file"
                                    accept="image/*"
                                    onChange={(event) =>
                                        form.setData(
                                            'thumbnail',
                                            event.target.files[0] ?? null,
                                        )
                                    }
                                    className="block w-full text-sm text-gray-400 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-800 file:px-4 file:py-2.5 file:font-medium file:text-gray-200 hover:file:bg-gray-700"
                                />

                                <p className="mt-3 text-xs text-gray-500">
                                    Recommended: 1920 × 1080 (16:9). Leave
                                    empty to keep the current thumbnail.
                                </p>
                            </div>

                            {form.errors.thumbnail && (
                                <p className="mt-2 text-sm text-red-400">
                                    {form.errors.thumbnail}
                                </p>
                            )}
                        </div>
                    </section>

                    {/* Product Details */}

                    <section className="space-y-6 rounded-xl border border-gray-800 bg-gray-900 p-6">
                        <div>
                            <p className="text-sm font-medium text-gray-500">
                                Customer Information
                            </p>

                            <h2 className="mt-1 text-lg font-semibold text-white">
                                Product Details
                            </h2>

                            <p className="mt-2 text-sm leading-6 text-gray-400">
                                Technical information displayed on the public
                                product page.
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="built_with"
                                    className="text-sm font-medium text-gray-300"
                                >
                                    Built With
                                </label>

                                <input
                                    id="built_with"
                                    type="text"
                                    value={form.data.built_with}
                                    onChange={(event) =>
                                        form.setData(
                                            'built_with',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Laravel + React"
                                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500"
                                />

                                {form.errors.built_with && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {form.errors.built_with}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="server_requirement"
                                    className="text-sm font-medium text-gray-300"
                                >
                                    Server Requirement
                                </label>

                                <input
                                    id="server_requirement"
                                    type="text"
                                    value={form.data.server_requirement}
                                    onChange={(event) =>
                                        form.setData(
                                            'server_requirement',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="PHP 8.x"
                                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500"
                                />

                                {form.errors.server_requirement && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {form.errors.server_requirement}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="database_system"
                                    className="text-sm font-medium text-gray-300"
                                >
                                    Database
                                </label>

                                <input
                                    id="database_system"
                                    type="text"
                                    value={form.data.database_system}
                                    onChange={(event) =>
                                        form.setData(
                                            'database_system',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="MySQL"
                                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500"
                                />

                                {form.errors.database_system && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {form.errors.database_system}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="browser_support"
                                    className="text-sm font-medium text-gray-300"
                                >
                                    Browser Support
                                </label>

                                <input
                                    id="browser_support"
                                    type="text"
                                    value={form.data.browser_support}
                                    onChange={(event) =>
                                        form.setData(
                                            'browser_support',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Chrome, Edge, Firefox, Safari"
                                    className="mt-2 w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500"
                                />

                                {form.errors.browser_support && (
                                    <p className="mt-2 text-sm text-red-400">
                                        {form.errors.browser_support}
                                    </p>
                                )}
                            </div>
                        </div>
                    </section>

                    {/* What's Included */}

                    <section className="space-y-6 rounded-xl border border-gray-800 bg-gray-900 p-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">
                                    Customer Information
                                </p>

                                <h2 className="mt-1 text-lg font-semibold text-white">
                                    What&apos;s Included
                                </h2>

                                <p className="mt-2 text-sm leading-6 text-gray-400">
                                    Items included with the digital product
                                    package.
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={addIncludedItem}
                                disabled={
                                    form.data.included_items.length >= 20
                                }
                                className="inline-flex items-center justify-center rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-semibold text-gray-300 transition hover:bg-gray-800 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                + Add Item
                            </button>
                        </div>

                        <div className="space-y-3">
                            {form.data.included_items.map((item, index) => (
                                <div
                                    key={index}
                                    className="flex items-center gap-3"
                                >
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 font-bold text-emerald-400">
                                        ✓
                                    </div>

                                    <input
                                        type="text"
                                        value={item}
                                        onChange={(event) =>
                                            updateIncludedItem(
                                                index,
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Included item"
                                        className="min-w-0 flex-1 rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white outline-none transition placeholder:text-gray-600 focus:border-blue-500"
                                    />

                                    <button
                                        type="button"
                                        onClick={() =>
                                            removeIncludedItem(index)
                                        }
                                        className="rounded-lg border border-gray-700 px-3 py-3 text-sm font-medium text-gray-400 transition hover:border-red-500/30 hover:bg-red-500/10 hover:text-red-400"
                                    >
                                        Remove
                                    </button>
                                </div>
                            ))}
                        </div>

                        {form.data.included_items.length === 0 && (
                            <div className="rounded-lg border border-dashed border-gray-700 bg-gray-950 p-6 text-center">
                                <p className="text-sm text-gray-500">
                                    No included items added.
                                </p>
                            </div>
                        )}

                        {form.errors.included_items && (
                            <p className="text-sm text-red-400">
                                {form.errors.included_items}
                            </p>
                        )}
                    </section>

                    {/* Save */}

                    <div className="flex flex-col-reverse gap-3 rounded-xl border border-gray-800 bg-gray-900 p-6 sm:flex-row sm:justify-end">
                        <Link
                            href={`/admin/products/${product.slug}`}
                            className="inline-flex items-center justify-center rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-semibold text-gray-300 transition hover:bg-gray-800 hover:text-white"
                        >
                            Cancel
                        </Link>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {form.processing
                                ? 'Saving...'
                                : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}