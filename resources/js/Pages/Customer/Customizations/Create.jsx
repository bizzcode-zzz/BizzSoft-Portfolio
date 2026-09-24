import { Head, Link, useForm } from '@inertiajs/react';
import CustomerLayout from '../../../Layouts/CustomerLayout';

export default function Create() {
    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm({
        title: '',
        description: '',
    });

    const submit = (event) => {
        event.preventDefault();

        post('/customizations');
    };

    return (
        <CustomerLayout>
            <Head title="New Customization Request" />

            <div className="mx-auto max-w-3xl">
                <div className="mb-6">
                    <Link
                        href="/customizations"
                        className="text-sm font-medium text-gray-400 transition hover:text-white"
                    >
                        ← Back to customization requests
                    </Link>

                    <h1 className="mt-4 text-2xl font-bold tracking-tight text-white">
                        New Customization Request
                    </h1>

                    <p className="mt-2 text-sm leading-6 text-gray-400">
                        Tell us what you would like customized. You can discuss
                        the details, quotation, and development process after
                        the request is reviewed.
                    </p>
                </div>

                <form
                    onSubmit={submit}
                    className="rounded-xl border border-gray-800 bg-gray-900 p-6"
                >
                    <div>
                        <label
                            htmlFor="title"
                            className="block text-sm font-semibold text-white"
                        >
                            Request title
                        </label>

                        <p className="mt-1 text-sm text-gray-500">
                            Give your customization request a clear, short name.
                        </p>

                        <input
                            id="title"
                            type="text"
                            value={data.title}
                            onChange={(event) =>
                                setData('title', event.target.value)
                            }
                            maxLength={255}
                            placeholder="Example: Custom reporting dashboard"
                            className="mt-3 block w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-white outline-none transition placeholder:text-gray-600 focus:border-gray-500 focus:ring-1 focus:ring-gray-500"
                        />

                        {errors.title && (
                            <p className="mt-2 text-sm font-medium text-red-400">
                                {errors.title}
                            </p>
                        )}
                    </div>

                    <div className="mt-6">
                        <label
                            htmlFor="description"
                            className="block text-sm font-semibold text-white"
                        >
                            What do you need?
                        </label>

                        <p className="mt-1 text-sm text-gray-500">
                            Describe the feature, workflow, integration, or
                            change you want us to review.
                        </p>

                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(event) =>
                                setData('description', event.target.value)
                            }
                            rows={9}
                            maxLength={5000}
                            placeholder="Describe your customization request, expected behavior, and any important requirements..."
                            className="mt-3 block w-full resize-y rounded-lg border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm leading-6 text-white outline-none transition placeholder:text-gray-600 focus:border-gray-500 focus:ring-1 focus:ring-gray-500"
                        />

                        <div className="mt-2 flex items-start justify-between gap-4">
                            <div>
                                {errors.description && (
                                    <p className="text-sm font-medium text-red-400">
                                        {errors.description}
                                    </p>
                                )}
                            </div>

                            <span className="shrink-0 text-xs text-gray-500">
                                {data.description.length}/5000
                            </span>
                        </div>
                    </div>

                    <div className="mt-8 rounded-lg border border-gray-800 bg-gray-950/60 p-4">
                        <h2 className="text-sm font-semibold text-white">
                            What happens next?
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-gray-400">
                            Your request will start as Submitted. An
                            administrator will review the requirements before
                            moving it through the customization workflow.
                        </p>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Link
                            href="/customizations"
                            className="inline-flex items-center justify-center rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700"
                        >
                            Cancel
                        </Link>

                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-black transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {processing
                                ? 'Submitting...'
                                : 'Submit Request'}
                        </button>
                    </div>
                </form>
            </div>
        </CustomerLayout>
    );
}