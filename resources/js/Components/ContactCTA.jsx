import { motion } from 'framer-motion';

export default function ContactCTA() {
    return (
        <section className="contact-cta-section">
            <div className="contact-cta-container">

                <div className="contact-cta-glow contact-cta-glow-left"></div>
                <div className="contact-cta-glow contact-cta-glow-right"></div>

                <motion.div
                    className="contact-cta-project contact-cta-project-left contact-cta-project-back"
                    initial={{ opacity: 0, x: -35, rotate: -7 }}
                    whileInView={{ opacity: 1, x: 0, rotate: -7 }}
                    viewport={{ once: true, amount: 0.3 }}
                    transition={{ duration: 0.8 }}
                >
                    <div className="contact-cta-project-top">
                        <span>BIZZSOFT</span>
                        <span>01</span>
                    </div>

                    <div className="contact-cta-project-screen">
                        <div className="contact-cta-screen-sidebar"></div>

                        <div className="contact-cta-screen-content">
                            <span></span>
                            <span></span>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                </motion.div>

                <motion.div
                    className="contact-cta-project contact-cta-project-left contact-cta-project-front"
                    initial={{ opacity: 0, x: -20, rotate: 4 }}
                    whileInView={{ opacity: 1, x: 0, rotate: 4 }}
                    viewport={{ once: true, amount: 0.3 }}
                    transition={{ duration: 0.8, delay: 0.1 }}
                >
                    <div className="contact-cta-project-top">
                        <span>V5</span>
                        <span>02</span>
                    </div>

                    <div className="contact-cta-project-screen">
                        <div className="contact-cta-dashboard-card">
                            <small>Dashboard</small>
                            <strong>BizzSoft V5</strong>
                        </div>

                        <div className="contact-cta-dashboard-row">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>

                        <div className="contact-cta-dashboard-chart">
                            <i></i>
                            <i></i>
                            <i></i>
                            <i></i>
                            <i></i>
                        </div>
                    </div>
                </motion.div>


                <motion.div
                    className="contact-cta-content"
                    initial={{ opacity: 0, y: 30 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, amount: 0.35 }}
                    transition={{
                        duration: 0.8,
                        ease: [0.22, 1, 0.36, 1],
                    }}
                >
                    <span className="contact-cta-eyebrow">
                        Ready When You Are
                    </span>

                    <h2 className="contact-cta-title">
                        Have a Project
                        <span>In Mind?</span>
                    </h2>

                    <p className="contact-cta-description">
                        Let's turn your idea into a working digital system.
                    </p>

                    <a
                        href="/contact"
                        className="contact-cta-button"
                    >
                        <span>Contact Us</span>

                        <span className="contact-cta-button-arrow">
                            <span className="contact-cta-button-arrow-icon">
                                →
                            </span>
                        </span>
                    </a>
                </motion.div>


                <motion.div
                    className="contact-cta-project contact-cta-project-right contact-cta-project-back"
                    initial={{ opacity: 0, x: 35, rotate: 7 }}
                    whileInView={{ opacity: 1, x: 0, rotate: 7 }}
                    viewport={{ once: true, amount: 0.3 }}
                    transition={{ duration: 0.8 }}
                >
                    <div className="contact-cta-project-top">
                        <span>BIZZSTORE</span>
                        <span>03</span>
                    </div>

                    <div className="contact-cta-project-screen">
                        <div className="contact-cta-store-heading">
                            <span>Modern Store</span>
                            <small>Explore products</small>
                        </div>

                        <div className="contact-cta-store-products">
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                </motion.div>

                <motion.div
                    className="contact-cta-project contact-cta-project-right contact-cta-project-front"
                    initial={{ opacity: 0, x: 20, rotate: -4 }}
                    whileInView={{ opacity: 1, x: 0, rotate: -4 }}
                    viewport={{ once: true, amount: 0.3 }}
                    transition={{ duration: 0.8, delay: 0.1 }}
                >
                    <div className="contact-cta-project-top">
                        <span>WEB</span>
                        <span>04</span>
                    </div>

                    <div className="contact-cta-project-screen">
                        <div className="contact-cta-web-heading">
                            <span>Digital</span>
                            <strong>Solutions</strong>
                        </div>

                        <div className="contact-cta-web-lines">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </motion.div>


                <div className="contact-cta-services">
                    <span>Web Development</span>
                    <i></i>
                    <span>Business Systems</span>
                    <i></i>
                    <span>E-Commerce</span>
                    <i></i>
                    <span>Custom Software</span>
                </div>

            </div>
        </section>
    );
}

