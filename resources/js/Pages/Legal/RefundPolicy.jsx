import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Footer from '../../Components/Footer';

const Section = ({ title, children }) => (
    <section className="border-b border-white/10 pb-8 last:border-b-0 last:pb-0">
        <h2 className="mb-4 text-xl font-semibold text-white sm:text-2xl">
            {title}
        </h2>

        <div className="space-y-4 text-[15px] leading-7 text-white/65 sm:text-base">
            {children}
        </div>
    </section>
);

export default function RefundPolicy() {
    return (
        <AppLayout>
            <Head title="Refund Policy | BizzSoft" />

            <section className="relative overflow-hidden bg-[#02050c] px-5 pb-24 pt-36 sm:px-8">
                <div className="pointer-events-none absolute inset-0">
                    <div className="absolute left-[8%] top-24 h-72 w-72 rounded-full bg-cyan-400/10 blur-[110px]" />
                    <div className="absolute right-[8%] top-40 h-80 w-80 rounded-full bg-violet-500/10 blur-[120px]" />
                </div>

                <div className="relative mx-auto max-w-4xl">
                    <div className="mb-10">
                        <p className="mb-3 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-300">
                            Legal
                        </p>

                        <h1 className="text-4xl font-bold tracking-tight text-white sm:text-5xl">
                            Refund Policy
                        </h1>

                        <p className="mt-4 max-w-2xl text-base leading-7 text-white/60">
                            This policy explains how refund and payment-related
                            requests are handled for BizzSoft products and
                            services.
                        </p>

                        <p className="mt-3 text-sm text-white/45">
                            Last updated: October 8, 2026
                        </p>
                    </div>

                    <div className="space-y-8 rounded-3xl border border-white/10 bg-white/[0.035] p-6 shadow-2xl shadow-black/20 backdrop-blur-xl sm:p-10">
                        <Section title="1. Overview">
                            <p>
                                BizzSoft sells digital software products,
                                downloadable releases, software licenses,
                                customization services, and related technical
                                services.
                            </p>

                            <p>
                                Refund requests are reviewed according to the
                                nature of the purchase, the delivery status,
                                product usage, applicable consumer law, and the
                                payment provider's requirements.
                            </p>
                        </Section>

                        <Section title="2. Paddle-Processed Purchases">
                            <p>
                                Payments made through Paddle are processed by
                                Paddle.com as our authorized online reseller
                                and Merchant of Record.
                            </p>

                            <p>
                                Paddle may handle billing inquiries, payment
                                disputes, refunds, returns, taxes, receipts,
                                and other payment-related matters for
                                Paddle-processed purchases.
                            </p>
                        </Section>

                        <Section title="3. When a Refund May Be Considered">
                            <p>
                                A refund request may be considered where, for
                                example, a customer was charged more than once
                                for the same purchase, payment was completed but
                                the purchased product could not be delivered,
                                or a material product issue cannot reasonably
                                be resolved through support.
                            </p>

                            <p>
                                Other requests may be reviewed on a
                                case-by-case basis considering the
                                circumstances of the purchase and any rights
                                provided by applicable law.
                            </p>
                        </Section>

                        <Section title="4. Digital Products and Downloads">
                            <p>
                                Because BizzSoft products may include
                                immediately accessible digital downloads and
                                software licenses, refund eligibility may be
                                affected once a product has been delivered,
                                downloaded, activated, or substantially used.
                            </p>

                            <p>
                                A change of mind does not automatically
                                guarantee a refund after digital delivery or
                                license activation. However, this does not
                                remove any mandatory consumer rights that
                                apply to the purchase.
                            </p>
                        </Section>

                        <Section title="5. Software Issues">
                            <p>
                                If you experience a technical problem, please
                                contact BizzSoft support with enough
                                information for us to investigate the issue.
                                We may first attempt to provide troubleshooting,
                                a correction, an updated release, or another
                                reasonable solution.
                            </p>

                            <p>
                                A refund may be considered when a material
                                defect prevents the product from functioning
                                substantially as described and the issue cannot
                                reasonably be resolved.
                            </p>
                        </Section>

                        <Section title="6. Customization and Development Services">
                            <p>
                                Custom development or customization work may
                                involve agreed scope, quotations, milestones,
                                revisions, development progress, and delivered
                                work.
                            </p>

                            <p>
                                Refund eligibility for these services may
                                depend on the amount of work already completed,
                                delivered milestones, third-party costs, and
                                the specific terms accepted for the project.
                            </p>
                        </Section>

                        <Section title="7. Refunds, Chargebacks, and Access">
                            <p>
                                When a payment is refunded, reversed, charged
                                back, canceled, or otherwise becomes subject to
                                a payment entitlement hold, access associated
                                with that purchase may be suspended or removed
                                where permitted by applicable law.
                            </p>

                            <p>
                                This may include product ownership, secure
                                downloads, license validation, updates, or
                                services linked to the affected purchase.
                            </p>
                        </Section>

                        <Section title="8. How to Request a Refund">
                            <p>
                                Contact BizzSoft through the support channels
                                available on this website and provide the
                                relevant order information together with the
                                reason for your request.
                            </p>

                            <p>
                                For Paddle-processed purchases, you may also
                                contact Paddle buyer support for
                                payment-related assistance.
                            </p>
                        </Section>

                        <Section title="9. Refund Method">
                            <p>
                                Approved refunds are normally returned through
                                the original payment method or according to the
                                payment provider's refund process.
                            </p>

                            <p>
                                Processing and settlement times may vary
                                depending on Paddle, the payment method,
                                financial institution, and customer location.
                            </p>
                        </Section>

                        <Section title="10. Consumer Rights">
                            <p>
                                Nothing in this Refund Policy excludes,
                                restricts, or overrides any refund, cancellation,
                                warranty, or consumer protection rights that
                                cannot legally be limited under applicable law.
                            </p>
                        </Section>

                        <Section title="11. Policy Updates">
                            <p>
                                We may update this Refund Policy when our
                                products, services, payment arrangements, or
                                legal obligations change. The current version
                                and update date will be published on this page.
                            </p>
                        </Section>

                        <Section title="12. Contact">
                            <p>
                                If you have questions about a BizzSoft product,
                                license, download, customization service, or
                                refund request, please use the contact or
                                customer support options provided on this
                                website.
                            </p>
                        </Section>
                    </div>
                </div>
            </section>

            <Footer />
        </AppLayout>
    );
}
