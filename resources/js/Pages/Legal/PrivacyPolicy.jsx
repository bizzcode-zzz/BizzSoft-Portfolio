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

export default function PrivacyPolicy() {
    return (
        <AppLayout>
            <Head title="Privacy Policy | BizzSoft" />

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
                            Privacy Policy
                        </h1>

                        <p className="mt-4 max-w-2xl text-base leading-7 text-white/60">
                            This policy explains what information BizzSoft
                            collects, how it is used, and how it is protected
                            when you use our website, products, licenses,
                            support, and customization services.
                        </p>

                        <p className="mt-3 text-sm text-white/45">
                            Last updated: October 8, 2026
                        </p>
                    </div>

                    <div className="space-y-8 rounded-3xl border border-white/10 bg-white/[0.035] p-6 shadow-2xl shadow-black/20 backdrop-blur-xl sm:p-10">
                        <Section title="1. Information We Collect">
                            <p>
                                When you create or use a BizzSoft account, we
                                may collect information such as your name,
                                email address, account credentials, and account
                                activity.
                            </p>

                            <p>
                                When you place an order, we may store order
                                details, product information, price snapshots,
                                payment status, provider transaction
                                references, fulfillment status, ownership
                                records, and related timestamps.
                            </p>
                        </Section>

                        <Section title="2. Payment Information">
                            <p>
                                Payments made through Paddle are processed by
                                Paddle.com as our authorized online reseller
                                and Merchant of Record.
                            </p>

                            <p>
                                BizzSoft does not directly process or store
                                complete payment card details. Paddle handles
                                payment information through its checkout and
                                payment systems.
                            </p>

                            <p>
                                BizzSoft may receive and store payment-related
                                information necessary to manage an order, such
                                as transaction identifiers, payment status,
                                amounts, currency, webhook events, refunds,
                                reversals, chargebacks, and entitlement
                                changes.
                            </p>
                        </Section>

                        <Section title="3. Product Licensing Information">
                            <p>
                                For licensed products, BizzSoft may process
                                license keys, product identifiers, activation
                                status, production domains, activation and
                                validation timestamps, and records related to
                                license activity.
                            </p>

                            <p>
                                License activation and validation requests may
                                also record technical information such as the
                                requesting IP address and user agent for
                                security, fraud prevention, troubleshooting,
                                and enforcement of license restrictions.
                            </p>
                        </Section>

                        <Section title="4. Support and Customization Information">
                            <p>
                                When you submit a support ticket,
                                customization request, reply, project review,
                                or related communication, we may collect the
                                information you choose to provide together
                                with relevant account and request history.
                            </p>

                            <p>
                                If you voluntarily provide temporary or
                                sensitive access information through a secure
                                access workflow, that information is used only
                                as reasonably necessary to investigate,
                                support, develop, test, or complete the
                                requested work.
                            </p>

                            <p>
                                You should provide only the minimum access
                                information reasonably necessary for the
                                requested service.
                            </p>
                        </Section>

                        <Section title="5. How We Use Information">
                            <p>
                                We use information to operate customer
                                accounts, process and verify orders, deliver
                                products, manage licenses, provide downloads,
                                perform customization work, respond to support
                                requests, maintain security, prevent abuse,
                                and administer payment-related entitlements.
                            </p>

                            <p>
                                We may also use relevant records to investigate
                                technical issues, payment disputes, fraudulent
                                activity, unauthorized access, license misuse,
                                chargebacks, or other activity affecting the
                                integrity of BizzSoft services.
                            </p>
                        </Section>

                        <Section title="6. Cookies and Session Data">
                            <p>
                                BizzSoft uses essential cookies and similar
                                technical mechanisms required for account
                                sessions, authentication, security, request
                                validation, and normal website operation.
                            </p>

                            <p>
                                These essential technologies help keep users
                                signed in, protect forms and requests, and
                                maintain secure interaction with the website.
                            </p>
                        </Section>

                        <Section title="7. Service Providers">
                            <p>
                                We may use trusted third-party service
                                providers where reasonably necessary to operate
                                BizzSoft, including hosting infrastructure,
                                payment processing, security, and other
                                technical services.
                            </p>

                            <p>
                                Paddle receives information necessary to
                                process Paddle-based purchases and carries out
                                its own responsibilities as Merchant of Record
                                under its applicable privacy terms.
                            </p>
                        </Section>

                        <Section title="8. Information Sharing">
                            <p>
                                BizzSoft does not disclose personal information
                                except where reasonably necessary to provide
                                the requested service, process a transaction,
                                operate or secure the platform, comply with
                                legal obligations, protect legitimate rights,
                                or respond to valid legal requests.
                            </p>

                            <p>
                                We do not treat customer account information as
                                a product for sale to advertisers.
                            </p>
                        </Section>

                        <Section title="9. Data Security">
                            <p>
                                We use reasonable technical and organizational
                                safeguards designed to protect customer
                                information against unauthorized access,
                                alteration, loss, misuse, or disclosure.
                            </p>

                            <p>
                                No online system can guarantee absolute
                                security. Customers are also responsible for
                                protecting their passwords, license keys,
                                secure access information, and account devices.
                            </p>
                        </Section>

                        <Section title="10. Data Retention">
                            <p>
                                We retain information for as long as reasonably
                                necessary to provide accounts, products,
                                licenses, downloads, support, and customization
                                services, and to maintain appropriate business,
                                payment, security, fraud prevention, and legal
                                records.
                            </p>

                            <p>
                                Different types of information may be retained
                                for different periods depending on their
                                purpose and applicable requirements.
                            </p>
                        </Section>

                        <Section title="11. Your Privacy Rights">
                            <p>
                                Depending on your location and applicable law,
                                you may have rights relating to your personal
                                information, such as requesting access,
                                correction, deletion, restriction, or other
                                permitted privacy actions.
                            </p>

                            <p>
                                Some information may need to be retained where
                                required for legitimate order, payment,
                                licensing, fraud prevention, security, or legal
                                purposes.
                            </p>
                        </Section>

                        <Section title="12. Third-Party Links and Services">
                            <p>
                                BizzSoft may link to or integrate with
                                third-party websites or services. Their privacy
                                practices are governed by their own policies,
                                and BizzSoft does not control how independent
                                third parties process information outside our
                                services.
                            </p>
                        </Section>

                        <Section title="13. Changes to This Policy">
                            <p>
                                We may update this Privacy Policy when our
                                products, services, technical systems, payment
                                arrangements, or legal obligations change.
                                The current version and update date will be
                                published on this page.
                            </p>
                        </Section>

                        <Section title="14. Contact">
                            <p>
                                If you have questions about this Privacy Policy
                                or the personal information associated with
                                your BizzSoft account, please use the contact
                                or customer support channels provided on this
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
