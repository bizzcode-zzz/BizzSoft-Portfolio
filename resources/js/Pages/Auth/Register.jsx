import { Head, Link, useForm } from '@inertiajs/react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();

        post('/register', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Register" />

            <main className="min-h-screen bg-gray-950 text-white flex items-center justify-center px-6">
                <div className="w-full max-w-md">
                    <h1 className="text-3xl font-bold">Create your account</h1>

                    <p className="mt-2 text-gray-400">
                        Register for your BizzSoft account.
                    </p>

                    <form onSubmit={submit} className="mt-8 space-y-5">
                        <div>
                            <label htmlFor="name" className="block mb-2">
                                Name
                            </label>

                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full rounded-lg bg-gray-900 border border-gray-700 px-4 py-3"
                                autoComplete="name"
                                autoFocus
                            />

                            {errors.name && (
                                <p className="mt-2 text-sm text-red-400">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="email" className="block mb-2">
                                Email
                            </label>

                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="w-full rounded-lg bg-gray-900 border border-gray-700 px-4 py-3"
                                autoComplete="username"
                            />

                            {errors.email && (
                                <p className="mt-2 text-sm text-red-400">
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="block mb-2">
                                Password
                            </label>

                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="w-full rounded-lg bg-gray-900 border border-gray-700 px-4 py-3"
                                autoComplete="new-password"
                            />

                            {errors.password && (
                                <p className="mt-2 text-sm text-red-400">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="password_confirmation"
                                className="block mb-2"
                            >
                                Confirm Password
                            </label>

                            <input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                className="w-full rounded-lg bg-gray-900 border border-gray-700 px-4 py-3"
                                autoComplete="new-password"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-lg bg-white text-black px-4 py-3 font-semibold disabled:opacity-50"
                        >
                            {processing ? 'Creating account...' : 'Register'}
                        </button>
                    </form>

                    <p className="mt-6 text-gray-400">
                        Already registered?{' '}
                        <Link href="/login" className="text-white underline">
                            Log in
                        </Link>
                    </p>
                </div>
            </main>
        </>
    );
}