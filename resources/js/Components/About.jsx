import { motion } from 'framer-motion';

export default function About() {
    const capabilities = [
        {
            number: '01',
            title: 'Backend',
            items: [
                'Laravel 12',
                'PHP',
                'MySQL',
                'Eloquent ORM',
                'REST APIs',
                'Authentication',
                'Form Requests',
                'Database Transactions',
            ],
        },
        {
            number: '02',
            title: 'Frontend',
            items: [
                'React',
                'Inertia.js',
                'JavaScript',
                'Blade',
                'Tailwind CSS',
                'Bootstrap',
                'HTML5',
                'CSS3',
                'Responsive UI',
                'Reusable Components',
            ],
        },
        {
            number: '03',
            title: 'CMS & E-Commerce',
            items: [
                'WordPress',
                'WooCommerce',
            ],
        },
        {
            number: '04',
            title: 'Mobile',
            items: [
                'Flutter',
            ],
        },
        {
            number: '05',
            title: 'Software Engineering',
            items: [
                'MVC Architecture',
                'OOP',
                'RBAC',
                'Business Logic',
                'Activity Logging',
                'Data Integrity',
                'Git',
                'GitHub',
            ],
        },
    ];

    return (
        <section id="about" className="about-section">

            <div className="about-container">

                {/* =====================================================
                    ABOUT HEADING
                ===================================================== */}

                <motion.div
                    className="about-heading"
                    initial={{
                        opacity: 0,
                        y: 35,
                    }}
                    whileInView={{
                        opacity: 1,
                        y: 0,
                    }}
                    viewport={{
                        once: true,
                        amount: 0.25,
                    }}
                    transition={{
                        duration: 0.7,
                        ease: [0.22, 1, 0.36, 1],
                    }}
                >
                    <span className="about-eyebrow">
                        About Me
                    </span>

                    <h2 className="about-title">
                        The developer
                        <span> behind BizzSoft.</span>
                    </h2>

                    <p className="about-intro">
                        I build modern web applications and business systems
                        with a focus on clean architecture, practical
                        engineering, performance, and real-world solutions.
                    </p>
                </motion.div>


                {/* =====================================================
                    PROFILE
                ===================================================== */}

                <div className="about-profile">

                    <motion.div
                        className="about-profile-card"
                        initial={{
                            opacity: 0,
                            x: -45,
                        }}
                        whileInView={{
                            opacity: 1,
                            x: 0,
                        }}
                        viewport={{
                            once: true,
                            amount: 0.2,
                        }}
                        transition={{
                            duration: 0.8,
                            ease: [0.22, 1, 0.36, 1],
                        }}
                    >

                        <div className="about-profile-glow"></div>

                        <div className="about-photo-frame">
                            <div className="about-photo-ring"></div>

                            <img
                                src="/images/alwin-john.jpg"
                                alt="Alwin John"
                                className="about-photo"
                            />
                        </div>

                        <div className="about-profile-info">

                            <span className="about-profile-label">
                                Full-Stack Developer
                            </span>

                            <h3>
                                Alwin John
                            </h3>

                            <p>
                                Full-Stack Developer &amp; Software Engineer
                            </p>

                        </div>

                        <div className="about-profile-line">
                            <span>&lt;/&gt;</span>
                        </div>

                        <p className="about-profile-description">
                            Passionate about building secure, scalable, and
                            user-focused applications that solve practical
                            business problems.
                        </p>

                    </motion.div>


                    {/* =================================================
                        PROFILE STORY
                    ================================================= */}

                    <motion.div
                        className="about-story"
                        initial={{
                            opacity: 0,
                            x: 45,
                        }}
                        whileInView={{
                            opacity: 1,
                            x: 0,
                        }}
                        viewport={{
                            once: true,
                            amount: 0.2,
                        }}
                        transition={{
                            duration: 0.8,
                            delay: 0.1,
                            ease: [0.22, 1, 0.36, 1],
                        }}
                    >

                        <div className="about-story-label">
                            <span></span>
                            Engineering Mindset
                        </div>

                        <h3>
                            From ideas to
                            <span> working systems.</span>
                        </h3>

                        <p>
                            My approach goes beyond simply writing code. I
                            focus on building systems that are structured,
                            maintainable, secure, and designed around real
                            business needs.
                        </p>

                        <p>
                            From backend architecture and database logic to
                            responsive interfaces and user experience, every
                            part of a project should work together as one
                            reliable system.
                        </p>

                        <div className="about-story-points">

                            <div>
                                <strong>01</strong>
                                <span>Clean Architecture</span>
                            </div>

                            <div>
                                <strong>02</strong>
                                <span>Practical Solutions</span>
                            </div>

                            <div>
                                <strong>03</strong>
                                <span>Continuous Growth</span>
                            </div>

                        </div>

                    </motion.div>

                </div>


                {/* =====================================================
                    CAPABILITIES
                ===================================================== */}

                <div className="about-capabilities">

                    <div className="about-capabilities-heading">
                        <span className="about-capabilities-eyebrow">
                            Technical Capabilities
                        </span>

                        <p>
                            Technologies and engineering practices used to build reliable digital products, business systems, and modern web experiences.
                        </p>
                    </div>

                    <div className="about-capabilities-grid">

                        {capabilities.map((capability, index) => (
                            <motion.article
                                key={capability.number}
                                className="about-capability-card"
                                initial={{
                                    opacity: 0,
                                    y: 35,
                                }}
                                whileInView={{
                                    opacity: 1,
                                    y: 0,
                                }}
                                viewport={{
                                    once: true,
                                    amount: 0.2,
                                }}
                                transition={{
                                    duration: 0.6,
                                    delay: index * 0.08,
                                    ease: [0.22, 1, 0.36, 1],
                                }}
                            >

                                <div className="about-capability-top">
                                    <span>
                                        {capability.number}
                                    </span>

                                    <div></div>
                                </div>

                                <h4>
                                    {capability.title}
                                </h4>

                                <div className="about-capability-items">
                                    {capability.items.map((item) => (
                                        <span key={item}>
                                            {item}
                                        </span>
                                    ))}
                                </div>

                            </motion.article>
                        ))}

                    </div>

                </div>

                <div className="about-signature">
                    <span>BUILT WITH LOVE</span>
                    <span className="about-signature-heart">♥</span>
                </div>

            </div>
        </section>
    );
}