export default function Footer() {
    const navigationLinks = [
        {
            label: 'Home',
            href: '#home',
        },
        {
            label: 'Projects',
            href: '#projects',
        },
        {
            label: 'About',
            href: '#about',
        },
        {
            label: 'Services',
            href: '#services',
        },
        {
            label: 'Contact',
            href: '/contact',
        },
    ];

    const services = [
        'Web Development',
        'Business Systems',
        'E-Commerce',
        'Custom Software',
    ];

    return (
        <footer className="site-footer">
            <div className="site-footer-container">

                <div className="site-footer-main">

                    <div className="site-footer-brand">
                        <a
                            href="#home"
                            className="site-footer-logo"
                        >
                            BIZZSOFT
                        </a>

                        <p className="site-footer-tagline">
                            Building modern digital solutions
                            through thoughtful engineering.
                        </p>
                    </div>


                    <div className="site-footer-column">
                        <span className="site-footer-heading">
                            Navigation
                        </span>

                        <nav className="site-footer-links">
                            {navigationLinks.map((link) => (
                                <a
                                    key={link.label}
                                    href={link.href}
                                >
                                    {link.label}
                                </a>
                            ))}
                        </nav>
                    </div>


                    <div className="site-footer-column">
                        <span className="site-footer-heading">
                            Services
                        </span>

                        <div className="site-footer-links">
                            {services.map((service) => (
                                <span key={service}>
                                    {service}
                                </span>
                            ))}
                        </div>
                    </div>


                    <div className="site-footer-column">
                        <span className="site-footer-heading">
                            Connect
                        </span>

                        <nav className="site-footer-links">
                            <a
                                href="https://github.com/bizzcode-zzz"
                                target="_blank"
                                rel="noreferrer"
                            >
                                GitHub
                            </a>

                            <a
                                href="#contact"
                            >
                                Email
                            </a>

                            <a
                                href="#contact"
                            >
                                Let's Talk
                            </a>
                        </nav>
                    </div>

                </div>


                <div className="site-footer-divider"></div>


                <div className="site-footer-bottom">

                    <span className="site-footer-copyright">
                        © 2026 BizzSoft. All rights reserved.
                    </span>

                    <span className="site-footer-signature">
                        BUILT WITH LOVE
                        <span className="site-footer-heart">
                            ♥
                        </span>
                    </span>

                </div>

            </div>
        </footer>
    );
}