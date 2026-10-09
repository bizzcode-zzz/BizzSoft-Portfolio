import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import Footer from '../Components/Footer';

const faqs = [
    {
        question: 'How do I access a product after purchase?',
        answer:
            'After your payment is verified and the order is fulfilled, eligible products and available releases are accessible from your BizzSoft customer account under My Products.',
    },
    {
        question: 'Where can I find my license key?',
        answer:
            'Issued product licenses are available from your customer account after successful payment and fulfillment. Keep your license key private and do not share it with unauthorized parties.',
    },
    {
        question: 'What should I do if my payment is still pending?',
        answer:
            'A pending payment has not yet been confirmed as successful. Avoid submitting repeated payments if the checkout result is uncertain. Contact support and include your order or payment number so we can review the status.',
    },
    {
        question: 'How do I request a refund?',
        answer:
            'Please review the BizzSoft Refund Policy for eligibility and instructions. You can contact support with your order details if you need help with a refund request.',
    },
    {
        question: 'Can I change the production domain on my license?',
        answer:
            'Products using a single-domain license are normally activated for one production hostname. Contact support if you need help with a legitimate domain reset or transfer.',
    },
    {
        question: 'How do customization requests work?',
        answer:
            'Customization requests are managed through your customer dashboard. You can review messages, quotations, payment status, development progress, revisions, and delivery updates from the relevant customization request.',
    },
];

const fieldClass =
    'mt-2 w-full rounded-xl border border-white/10 bg-white/[0.045] px-4 py-3 text-sm text-white outline-none transition placeholder:text-white/25 focus:border-cyan-300/40 focus:ring-2 focus:ring-cyan-300/10';

export default function Contact() {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
        recentlySuccessful,
    } = useForm({
        name: '',
        email: '',
        subject: '',
        message: '',
    });

    const submit = (event) => {
        event.preventDefault();

        post('/contact', {
            preserveScroll: true,
            onSuccess: () => {
                reset('subject', 'message');
            },
        });
    };

    return (
        <AppLayout>
            <Head title="Contact & Buyer Support | BizzSoft" />

            <section className="relative overflow-hidden bg-[#02050c] px-5 pb-24 pt-36 sm:px-8">
                <div className="pointer-events-none absolute inset-0">
                    <div className="absolute left-[8%] top-24 h-72 w-72 rounded-full bg-cyan-400/10 blur-[110px]" />
                    <div className="absolute right-[8%] top-40 h-80 w-80 rounded-full bg-violet-500/10 blur-[120px]" />
                </div>

                <div className="relative mx-auto max-w-6xl">
                    <div className="mb-10 max-w-3xl">
                        <p className="mb-3 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-300">
                            Support
                        </p>

                        <h1 className="text-4xl font-bold tracking-tight text-white sm:text-5xl">
                            Contact with us.
                        </h1>

                        <p className="mt-4 text-base leading-7 text-white/60">
                            Please feel free to reach out to us in any
                            situation. We value your feedback and will be
                            happy to help with orders, payments, products,
                            licenses, refunds, or customization requests.
                        </p>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                        <div className="rounded-3xl border border-white/10 bg-white/[0.035] p-6 shadow-2xl shadow-black/20 backdrop-blur-xl sm:p-8">
                            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-300">
                                Send a message
                            </p>

                            <h2 className="mt-3 text-2xl font-semibold text-white">
                                How can we help?
                            </h2>

                            <p className="mt-3 text-sm leading-6 text-white/50">
                                Complete the form below and your message will
                                be sent to BizzSoft Buyer Support.
                            </p>

                            {recentlySuccessful && (
                                <div className="mt-6 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm leading-6 text-emerald-200">
                                    Your message has been sent successfully.
                                    BizzSoft support will get back to you as
                                    soon as possible.
                                </div>
                            )}

                            <form
                                onSubmit={submit}
                                className="mt-7 space-y-5"
                            >
                                <div>
                                    <label
                                        htmlFor="name"
                                        className="text-sm font-medium text-white/75"
                                    >
                                        Name
                                    </label>

                                    <input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(event) =>
                                            setData('name', event.target.value)
                                        }
                                        autoComplete="name"
                                        maxLength={100}
                                        className={fieldClass}
                                        placeholder="Your name"
                                    />

                                    {errors.name && (
                                        <p className="mt-2 text-sm text-red-300">
                                            {errors.name}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="email"
                                        className="text-sm font-medium text-white/75"
                                    >
                                        Email
                                    </label>

                                    <input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(event) =>
                                            setData('email', event.target.value)
                                        }
                                        autoComplete="email"
                                        maxLength={254}
                                        className={fieldClass}
                                        placeholder="you@example.com"
                                    />

                                    {errors.email && (
                                        <p className="mt-2 text-sm text-red-300">
                                            {errors.email}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="subject"
                                        className="text-sm font-medium text-white/75"
                                    >
                                        Subject
                                    </label>

                                    <input
                                        id="subject"
                                        type="text"
                                        value={data.subject}
                                        onChange={(event) =>
                                            setData(
                                                'subject',
                                                event.target.value,
                                            )
                                        }
                                        maxLength={150}
                                        className={fieldClass}
                                        placeholder="How can we help?"
                                    />

                                    {errors.subject && (
                                        <p className="mt-2 text-sm text-red-300">
                                            {errors.subject}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="message"
                                        className="text-sm font-medium text-white/75"
                                    >
                                        Message
                                    </label>

                                    <textarea
                                        id="message"
                                        rows={7}
                                        value={data.message}
                                        onChange={(event) =>
                                            setData(
                                                'message',
                                                event.target.value,
                                            )
                                        }
                                        maxLength={5000}
                                        className={`${fieldClass} resize-y`}
                                        placeholder="Tell us how we can help..."
                                    />

                                    {errors.message && (
                                        <p className="mt-2 text-sm text-red-300">
                                            {errors.message}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-3 border-t border-white/10 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="max-w-md text-xs leading-5 text-white/35">
                                        Please do not send passwords, private
                                        keys, license secrets, or other
                                        sensitive credentials through this
                                        form.
                                    </p>

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-black transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {processing
                                            ? 'Sending...'
                                            : 'Submit'}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div className="space-y-6">
                            <div className="rounded-3xl border border-white/10 bg-white/[0.035] p-6 sm:p-8">
                                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-300">
                                    Buyer Support
                                </p>

                                <h2 className="mt-3 text-xl font-semibold text-white">
                                    Contact BizzSoft
                                </h2>

                                <p className="mt-3 text-sm leading-6 text-white/55">
                                    You can also contact us directly by email.
                                </p>

                                <a
                                    href="mailto:support@bizzsoft.dev"
                                    className="mt-4 inline-flex text-sm font-semibold text-cyan-300 transition hover:text-cyan-200"
                                >
                                    support@bizzsoft.dev
                                </a>

                                <p className="mt-5 text-xs leading-5 text-white/35">
                                    Include your order number or payment number
                                    when contacting us about a purchase.
                                </p>
                            </div>

                            <div className="rounded-3xl border border-white/10 bg-white/[0.025] p-6 sm:p-8">
                                <h2 className="text-xl font-semibold text-white">
                                    Policies
                                </h2>

                                <div className="mt-4 flex flex-wrap gap-3 text-sm">
                                    <a
                                        href="/terms"
                                        className="text-cyan-300 transition hover:text-cyan-200"
                                    >
                                        Terms & Conditions
                                    </a>

                                    <span className="text-white/20">•</span>

                                    <a
                                        href="/refund-policy"
                                        className="text-cyan-300 transition hover:text-cyan-200"
                                    >
                                        Refund Policy
                                    </a>

                                    <span className="text-white/20">•</span>

                                    <a
                                        href="/privacy-policy"
                                        className="text-cyan-300 transition hover:text-cyan-200"
                                    >
                                        Privacy Policy
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 rounded-3xl border border-white/10 bg-white/[0.035] p-6 shadow-2xl shadow-black/20 backdrop-blur-xl sm:p-8">
                        <div className="mb-7">
                            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-violet-300">
                                FAQ
                            </p>

                            <h2 className="mt-3 text-2xl font-semibold text-white">
                                Frequently Asked Questions
                            </h2>
                        </div>

                        <div className="grid gap-x-10 md:grid-cols-2">
                            {faqs.map((faq) => (
                                <div
                                    key={faq.question}
                                    className="border-b border-white/10 py-6 first:pt-0"
                                >
                                    <h3 className="font-semibold text-white">
                                        {faq.question}
                                    </h3>

                                    <p className="mt-2 text-sm leading-6 text-white/55">
                                        {faq.answer}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <Footer />
        </AppLayout>
    );
}