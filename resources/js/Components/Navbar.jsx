export default function Navbar() {
    return (
        <header className="fixed inset-x-0 top-0 z-50">
            <nav className="navbar">

                {/* Logo */}

                <a
                    href="#home"
                    className="navbar-logo"
                >
                    <img
                        src="/images/bizzsoft-logo.png"
                        alt="BizzSoft"
                        className="navbar-logo-image"
                    />
                </a>

                {/* Navigation */}

                <div className="navbar-links">

                    <a
                        href="#home"
                        className="navbar-link navbar-link-active"
                    >
                        Home
                    </a>

                    <a
                        href="#projects"
                        className="navbar-link"
                    >
                        Projects
                    </a>

                    <a
                        href="#about"
                        className="navbar-link"
                    >
                        About
                    </a>

                    <a
                        href="#services"
                        className="navbar-link"
                    >
                        Services
                    </a>

                    <a
                        href="#blog"
                        className="navbar-link"
                    >
                        Blog
                    </a>

                    <a
                        href="#contact"
                        className="navbar-link"
                    >
                        Contact
                    </a>

                </div>

                {/* CTA */}

                <a
                    href="#contact"
                    className="navbar-cta"
                >
                    <span>
                        LET'S TALK
                    </span>

                    <span className="navbar-cta-arrow">
                        →
                    </span>
                </a>

                {/* MOBILE MENU */}

                <button
                    type="button"
                    className="navbar-mobile-menu"
                    aria-label="Open navigation menu"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

            </nav>
        </header>
    );
}