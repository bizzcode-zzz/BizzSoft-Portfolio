import { Head, Link, useForm } from '@inertiajs/react';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();

        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Login" />

            <main className="min-h-screen bg-gray-950 text-white flex items-center justify-center px-6">
                <div className="w-full max-w-md">
                    <h1 className="text-3xl font-bold">Welcome back</h1>

                    <p className="mt-2 text-gray-400">
                        Log in to your BizzSoft account.
                    </p>

                    <form onSubmit={submit} className="mt-8 space-y-5">
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
                                autoFocus
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
                                autoComplete="current-password"
                            />

                            {errors.password && (
                                <p className="mt-2 text-sm text-red-400">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) =>
                                    setData('remember', e.target.checked)
                                }
                            />

                            <span className="text-gray-300">Remember me</span>
                        </label>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-lg bg-white text-black px-4 py-3 font-semibold disabled:opacity-50"
                        >
                            {processing ? 'Logging in...' : 'Log in'}
                        </button>
                    </form>

                    <p className="mt-6 text-gray-400">
                        Need an account?{' '}
                        <Link href="/register" className="text-white underline">
                            Register
                        </Link>
                    </p>
                </div>
            </main>
        </>
    );
}