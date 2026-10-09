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

export default function Terms() {
    return (
        <AppLayout>
            <Head title="Terms & Conditions | BizzSoft" />

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
                            Terms & Conditions
                        </h1>

                        <p className="mt-4 max-w-2xl text-base leading-7 text-white/60">
                            These Terms govern your use of BizzSoft products,
                            services, customer accounts, downloads, licenses,
                            support, and customization services.
                        </p>

                        <p className="mt-3 text-sm text-white/45">
                            Last updated: October 8, 2026
                        </p>
                    </div>

                    <div className="space-y-8 rounded-3xl border border-white/10 bg-white/[0.035] p-6 shadow-2xl shadow-black/20 backdrop-blur-xl sm:p-10">
                        <Section title="1. About BizzSoft">
                            <p>
                                BizzSoft provides digital software products,
                                business systems, web solutions, downloadable
                                releases, software licensing, customization
                                services, and related technical support.
                            </p>

                            <p>
                                By accessing this website, creating an account,
                                purchasing a product, downloading a release,
                                activating a license, or requesting a service,
                                you agree to these Terms.
                            </p>
                        </Section>

                        <Section title="2. Accounts">
                            <p>
                                You are responsible for providing accurate
                                account information and keeping your login
                                credentials secure.
                            </p>

                            <p>
                                You must not use another person's account
                                without permission or attempt to bypass access,
                                ownership, payment, licensing, or security
                                controls.
                            </p>
                        </Section>

                        <Section title="3. Products and Services">
                            <p>
                                Product descriptions, versions, features,
                                compatibility information, pricing, and
                                included deliverables are shown on the relevant
                                product or service page at the time of order.
                            </p>

                            <p>
                                Customization or development work may have
                                separate agreed requirements, scope, pricing,
                                milestones, revisions, delivery terms, or
                                acceptance conditions presented during the
                                request and quotation process.
                            </p>
                        </Section>

                        <Section title="4. Orders and Payments">
                            <p>
                                Prices shown at checkout are presented before
                                payment is completed. Applicable taxes may be
                                calculated and displayed during checkout.
                            </p>

                            <p>
                                Our order process is conducted by our online reseller Paddle.com. Paddle.com is the Merchant of Record for all our orders. Paddle provides all customer service inquiries and handles returns
                            </p>

                            <p>
                                Product ownership, downloads, licenses, and
                                technical product support are provided by
                                BizzSoft after successful payment and
                                fulfillment.
                            </p>
                        </Section>

                        <Section title="5. Software Licenses">
                            <p>
                                Unless a product page or separate agreement
                                states otherwise, purchasing a licensed
                                BizzSoft product grants you a limited,
                                non-exclusive, non-transferable right to use
                                that product subject to its license terms.
                            </p>

                            <p>
                                Where a product uses a single production domain
                                license, the license may be activated for one
                                production hostname only. Validation from a
                                different production domain may be rejected
                                unless BizzSoft authorizes a legitimate reset
                                or transfer.
                            </p>

                            <p>
                                You may not resell, sublicense, redistribute,
                                publish, share, or commercially provide a
                                BizzSoft license key or downloadable product to
                                another person or organization unless BizzSoft
                                expressly permits it in writing.
                            </p>
                        </Section>

                        <Section title="6. Downloads and Product Access">
                            <p>
                                Download access is provided only to eligible
                                customer accounts after verified payment and
                                completed fulfillment. Available releases may
                                include version information, release notes, and
                                upgrade instructions.
                            </p>

                            <p>
                                Access credentials, download links, license
                                keys, and other protected resources must not be
                                shared with unauthorized parties.
                            </p>
                        </Section>

                        <Section title="7. Payment Reversals and Entitlements">
                            <p>
                                If a payment is refunded, reversed, charged
                                back, canceled, or otherwise becomes subject to
                                an entitlement hold, BizzSoft may suspend or
                                restrict the related ownership, download
                                access, license validation, or service
                                entitlement where permitted by applicable law.
                            </p>
                        </Section>

                        <Section title="8. Refunds">
                            <p>
                                Refund eligibility and procedures are described
                                in the BizzSoft Refund Policy.
                                Paddle-processed payment or return requests may
                                also be handled through Paddle as Merchant of
                                Record.
                            </p>

                            <p>
                                Nothing in these Terms limits mandatory
                                consumer rights that cannot legally be excluded
                                or restricted.
                            </p>
                        </Section>

                        <Section title="9. Acceptable Use">
                            <p>
                                You must not use BizzSoft products or services
                                for unlawful activity, malicious software,
                                unauthorized access, fraud, infringement,
                                abusive activity, or purposes prohibited by
                                applicable law.
                            </p>

                            <p>
                                You must not attempt to defeat licensing,
                                authentication, payment verification, access
                                controls, rate limits, or other security
                                safeguards implemented by BizzSoft.
                            </p>
                        </Section>

                        <Section title="10. Intellectual Property">
                            <p>
                                BizzSoft retains ownership of its software,
                                source code, branding, website materials,
                                documentation, designs, and other intellectual
                                property except where a written agreement
                                expressly states otherwise.
                            </p>

                            <p>
                                Purchasing a product grants usage rights only.
                                It does not transfer ownership of BizzSoft
                                intellectual property.
                            </p>
                        </Section>

                        <Section title="11. Availability and Updates">
                            <p>
                                We may maintain, update, improve, replace, or
                                discontinue website features or product
                                releases when reasonably necessary. We do not
                                guarantee uninterrupted access to every online
                                feature at all times.
                            </p>
                        </Section>

                        <Section title="12. Warranty and Liability">
                            <p>
                                BizzSoft provides products and services with
                                reasonable care and according to their
                                description or agreed scope. Software may
                                depend on third-party services, hosting
                                environments, frameworks, or integrations
                                outside BizzSoft's control.
                            </p>

                            <p>
                                To the maximum extent permitted by applicable
                                law, BizzSoft is not responsible for indirect,
                                incidental, or consequential loss resulting
                                from misuse, unauthorized modification,
                                incompatible environments, third-party
                                failures, or circumstances outside our
                                reasonable control.
                            </p>
                        </Section>

                        <Section title="13. Changes to These Terms">
                            <p>
                                We may update these Terms when our products,
                                services, legal obligations, or payment
                                arrangements change. The current version and
                                update date will be published on this page.
                            </p>
                        </Section>

                        <Section title="14. Contact and Support">
                            <p>
                                Questions about BizzSoft products, licensing,
                                downloads, customization services, or these
                                Terms may be sent to{' '}
                                <a
                                    href="mailto:support@bizzsoft.dev"
                                    className="font-medium text-cyan-300 hover:text-cyan-200"
                                >
                                    support@bizzsoft.dev
                                </a>
                                .
                            </p>

                            <p>
                                Questions specifically related to a
                                Paddle-processed payment, receipt, billing
                                issue, or return may also be directed to
                                Paddle's buyer support.
                            </p>
                        </Section>
                    </div>
                </div>
            </section>

            <Footer />
        </AppLayout>
    );
}
