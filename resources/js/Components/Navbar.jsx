import { Link, usePage } from '@inertiajs/react';

export default function Navbar() {
    const { url } = usePage();

    const pathname = url.split('?')[0];
    const isHome = pathname === '/';
    const isProducts = pathname.startsWith('/products');

    const homeSection = (section) => {
        return isHome ? `#${section}` : `/#${section}`;
    };

    return (
        <header className="fixed inset-x-0 top-0 z-50">
            <nav className="navbar">

                {/* Logo */}

                <a
                    href={homeSection('home')}
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
                        href={homeSection('home')}
                        className={`navbar-link ${
                            isHome ? 'navbar-link-active' : ''
                        }`}
                    >
                        Home
                    </a>

                    <a
                        href={homeSection('projects')}
                        className="navbar-link"
                    >
                        Projects
                    </a>

                    <Link
                        href="/products"
                        className={`navbar-link ${
                            isProducts ? 'navbar-link-active' : ''
                        }`}
                    >
                        Products
                    </Link>

                    <a
                        href={homeSection('about')}
                        className="navbar-link"
                    >
                        About
                    </a>

                    <a
                        href={homeSection('services')}
                        className="navbar-link"
                    >
                        Services
                    </a>

                    <a
                        href={homeSection('blog')}
                        className="navbar-link"
                    >
                        Blog
                    </a>

                    <a
                        href={homeSection('contact')}
                        className="navbar-link"
                    >
                        Contact
                    </a>

                </div>

                {/* CTA */}

                <a
                    href={homeSection('contact')}
                    className="navbar-cta"
                >
                    <span>
                        LET&apos;S TALK
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