import { useEffect, useState } from 'react';

export default function Hero() {
    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        const mediaQuery = window.matchMedia(
            '(max-width: 767px)'
        );

        const updateViewport = () => {
            setIsMobile(mediaQuery.matches);
        };

        updateViewport();

        mediaQuery.addEventListener(
            'change',
            updateViewport
        );

        return () => {
            mediaQuery.removeEventListener(
                'change',
                updateViewport
            );
        };
    }, []);

    /*
    ============================================================
    FLOW PATHS

    DESKTOP
    ------------------------------------------------------------
    The original 760 × 650 master coordinate system is preserved.

    MOBILE
    ------------------------------------------------------------
    The cards move closer to the center, so the connector paths
    use a tighter geometry designed specifically for the mobile
    composition.

    IMPORTANT:
    The moving square particles use the EXACT SAME path as
    their corresponding connector line.
    ============================================================
    */

    const desktopFlows = [
        {
            id: 'hero-flow-idea',
            path: 'M380 90 V265',
            lineClass:
                'hero-flow-line hero-flow-line-gold',
            dotClass:
                'hero-flow-dot hero-flow-dot-gold',
            duration: '3.8s',
        },

        {
            id: 'hero-flow-website',
            path: 'M305 285 H235 V220 H115',
            lineClass:
                'hero-flow-line hero-flow-line-cyan',
            dotClass:
                'hero-flow-dot hero-flow-dot-cyan',
            duration: '4.2s',
        },

        {
            id: 'hero-flow-store',
            path: 'M305 365 H235 V405 H115',
            lineClass:
                'hero-flow-line hero-flow-line-blue',
            dotClass:
                'hero-flow-dot hero-flow-dot-blue',
            duration: '4.6s',
        },

        {
            id: 'hero-flow-business',
            path: 'M455 285 H525 V220 H645',
            lineClass:
                'hero-flow-line hero-flow-line-pink',
            dotClass:
                'hero-flow-dot hero-flow-dot-pink',
            duration: '4.1s',
        },

        {
            id: 'hero-flow-mobile',
            path: 'M455 365 H525 V405 H645',
            lineClass:
                'hero-flow-line hero-flow-line-violet',
            dotClass:
                'hero-flow-dot hero-flow-dot-violet',
            duration: '4.4s',
        },

        {
            id: 'hero-flow-custom',
            path: 'M380 385 V555',
            lineClass:
                'hero-flow-line hero-flow-line-cyan',
            dotClass:
                'hero-flow-dot hero-flow-dot-cyan',
            duration: '4.8s',
        },
    ];

    /*
    ============================================================
    MOBILE FLOW GEOMETRY

    The mobile network is still based on the same 760 × 650
    coordinate system.

    The difference is that the side cards are visually closer
    to the center, so these paths shorten the horizontal reach
    and connect to the actual mobile card area.

    Direction:
        IDEA       → CENTER
        WEBSITE    CENTER → LEFT
        STORE      CENTER → LEFT
        BUSINESS   CENTER → RIGHT
        MOBILE     CENTER → RIGHT
        CUSTOM     CENTER → BOTTOM
    ============================================================
    */

    const mobileFlows = [
        {
            id: 'hero-flow-idea-mobile',
            path: 'M380 90 V245',
            lineClass:
                'hero-flow-line hero-flow-line-gold',
            dotClass:
                'hero-flow-dot hero-flow-dot-gold',
            duration: '3.8s',
        },

        {
            id: 'hero-flow-website-mobile',
            path: 'M305 285 H255 V220 H245',
            lineClass:
                'hero-flow-line hero-flow-line-cyan',
            dotClass:
                'hero-flow-dot hero-flow-dot-cyan',
            duration: '4.2s',
        },

        {
            id: 'hero-flow-store-mobile',
            path: 'M305 365 H255 V405 H245',
            lineClass:
                'hero-flow-line hero-flow-line-blue',
            dotClass:
                'hero-flow-dot hero-flow-dot-blue',
            duration: '4.6s',
        },

        {
            id: 'hero-flow-business-mobile',
            path: 'M455 285 H505 V220 H515',
            lineClass:
                'hero-flow-line hero-flow-line-pink',
            dotClass:
                'hero-flow-dot hero-flow-dot-pink',
            duration: '4.1s',
        },

        {
            id: 'hero-flow-mobile-mobile',
            path: 'M455 365 H505 V405 H515',
            lineClass:
                'hero-flow-line hero-flow-line-violet',
            dotClass:
                'hero-flow-dot hero-flow-dot-violet',
            duration: '4.4s',
        },

        {
            id: 'hero-flow-custom-mobile',
            path: 'M380 405 V555',
            lineClass:
                'hero-flow-line hero-flow-line-cyan',
            dotClass:
                'hero-flow-dot hero-flow-dot-cyan',
            duration: '4.8s',
        },
    ];

    const activeFlows = isMobile
        ? mobileFlows
        : desktopFlows;

    return (
        <section
            className="hero"
            id="home"
        >

            {/* =====================================================
                BACKGROUND
            ====================================================== */}

            <div
                className="hero-background"
                aria-hidden="true"
            >
                <div className="hero-background-grid" />
                <div className="hero-background-scanlines" />

                <div
                    className="
                        hero-background-glow
                        hero-background-glow-one
                    "
                />

                <div
                    className="
                        hero-background-glow
                        hero-background-glow-two
                    "
                />

                <div className="hero-background-vignette" />
            </div>


            {/* =====================================================
                MAIN CONTAINER
            ====================================================== */}

            <div className="hero-container">

                {/* =================================================
                    LEFT CONTENT
                ================================================== */}

                <div className="hero-content">

                    <div className="hero-eyebrow">

                        <span className="hero-eyebrow-line" />

                        <span>
                            Ideas · Solutions · Real Impact
                        </span>

                    </div>


                    <h1 className="hero-title">

                        <span>
                            TURN YOUR
                        </span>

                        <span>
                            IDEAS INTO
                        </span>

                        <span className="hero-title-accent">
                            REAL SOLUTIONS
                            <span className="hero-cursor">
                                _
                            </span>
                        </span>

                    </h1>


                    <p className="hero-description">
                        We build websites, online stores,
                        business systems, and custom digital
                        solutions designed around your goals.
                    </p>


                    {/* =================================================
                        CTA
                    ================================================== */}

                    <div className="hero-actions">

                        <a
                            href="#projects"
                            className="
                                hero-button
                                hero-button-primary
                            "
                        >
                            <span>
                                View Our Work
                            </span>

                            <span className="hero-button-arrow">
                                <span>
                                    →
                                </span>
                            </span>
                        </a>


                        <a
                            href="#contact"
                            className="
                                hero-button
                                hero-button-secondary
                            "
                        >
                            Let's Talk
                        </a>

                    </div>


                    {/* =================================================
                        STATS
                    ================================================== */}

                    <div className="hero-stats">

                        <div className="hero-stat">
                            <strong>
                                20+
                            </strong>

                            <span>
                                Projects
                            </span>
                        </div>


                        <div className="hero-stat-divider" />


                        <div className="hero-stat">
                            <strong>
                                5+
                            </strong>

                            <span>
                                Years
                            </span>
                        </div>


                        <div className="hero-stat-divider" />


                        <div className="hero-stat">
                            <strong>
                                100%
                            </strong>

                            <span>
                                Dedication
                            </span>
                        </div>

                    </div>

                </div>


                {/* =================================================
                    CONNECTED SOLUTIONS VISUAL
                ================================================== */}

                <div className="hero-network">

                    {/* =================================================
                        CONNECTOR LINES + FLOW PARTICLES
                    ================================================== */}

                    <svg
                        className="hero-network-lines"
                        viewBox="0 0 760 650"
                        fill="none"
                        aria-hidden="true"
                    >

                        {activeFlows.map((flow) => (
                            <g key={flow.id}>

                                {/* CONNECTION LINE */}

                                <path
                                    id={flow.id}
                                    d={flow.path}
                                    className={flow.lineClass}
                                />


                                {/* MOVING SQUARE PARTICLE */}

                                <rect
                                    x="-4"
                                    y="-4"
                                    width="8"
                                    height="8"
                                    rx="1"
                                    className={flow.dotClass}
                                >
                                    <animateMotion
                                        dur={flow.duration}
                                        repeatCount="indefinite"
                                        path={flow.path}
                                    />
                                </rect>

                            </g>
                        ))}


                        {/* =================================================
                            STATIC CONNECTION MARKERS
                        ================================================== */}

                        <rect
                            x="376.5"
                            y="86.5"
                            width="7"
                            height="7"
                            className="
                                hero-network-marker
                                hero-network-marker-gold
                            "
                        />

                        <rect
                            x="111.5"
                            y="216.5"
                            width="7"
                            height="7"
                            className="
                                hero-network-marker
                                hero-network-marker-cyan
                            "
                        />

                        <rect
                            x="111.5"
                            y="401.5"
                            width="7"
                            height="7"
                            className="
                                hero-network-marker
                                hero-network-marker-blue
                            "
                        />

                        <rect
                            x="641.5"
                            y="216.5"
                            width="7"
                            height="7"
                            className="
                                hero-network-marker
                                hero-network-marker-pink
                            "
                        />

                        <rect
                            x="641.5"
                            y="401.5"
                            width="7"
                            height="7"
                            className="
                                hero-network-marker
                                hero-network-marker-violet
                            "
                        />

                        <rect
                            x="376.5"
                            y="551.5"
                            width="7"
                            height="7"
                            className="
                                hero-network-marker
                                hero-network-marker-cyan
                            "
                        />

                    </svg>


                    {/* =================================================
                        YOUR IDEA
                    ================================================== */}

                    <div
                        className="
                            hero-solution-card
                            hero-solution-card-idea
                        "
                    >

                        <div
                            className="
                                hero-solution-icon
                                hero-solution-icon-gold
                            "
                        >
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path d="M9 18h6" />
                                <path d="M10 22h4" />
                                <path
                                    d="
                                        M8.2 14.5
                                        C7.45 13.55 7 12.34 7 11
                                        A5 5 0 0 1 17 11
                                        C17 12.34 16.55 13.55 15.8 14.5
                                        C15.25 15.2 15 15.7 15 17
                                        H9
                                        C9 15.7 8.75 15.2 8.2 14.5Z
                                    "
                                />
                            </svg>
                        </div>


                        <div className="hero-solution-copy">

                            <strong>
                                Your Idea
                            </strong>

                            <span>
                                We listen. We plan.
                            </span>

                        </div>

                    </div>


                    {/* =================================================
                        WEBSITE
                    ================================================== */}

                    <div
                        className="
                            hero-solution-card
                            hero-solution-card-website
                        "
                    >

                        <div
                            className="
                                hero-solution-icon
                                hero-solution-icon-cyan
                            "
                        >
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <rect
                                    x="3"
                                    y="4"
                                    width="18"
                                    height="14"
                                    rx="1"
                                />

                                <path d="M8 21h8" />

                                <path d="M12 18v3" />
                            </svg>
                        </div>


                        <div className="hero-solution-copy">

                            <strong>
                                Website
                            </strong>

                            <span>
                                Your brand online.
                            </span>

                        </div>

                    </div>


                    {/* =================================================
                        ONLINE STORE
                    ================================================== */}

                    <div
                        className="
                            hero-solution-card
                            hero-solution-card-store
                        "
                    >

                        <div
                            className="
                                hero-solution-icon
                                hero-solution-icon-blue
                            "
                        >
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    d="
                                        M3 4h2
                                        l2.2 11.2
                                        a2 2 0 0 0 2 1.6
                                        h7.8
                                        a2 2 0 0 0 1.9-1.4
                                        L21 8H6
                                    "
                                />

                                <circle
                                    cx="10"
                                    cy="20"
                                    r="1.5"
                                />

                                <circle
                                    cx="18"
                                    cy="20"
                                    r="1.5"
                                />
                            </svg>
                        </div>


                        <div className="hero-solution-copy">

                            <strong>
                                Online Store
                            </strong>

                            <span>
                                Sell your products online.
                            </span>

                        </div>

                    </div>


                    {/* =================================================
                        BUSINESS SYSTEM
                    ================================================== */}

                    <div
                        className="
                            hero-solution-card
                            hero-solution-card-business
                        "
                    >

                        <div
                            className="
                                hero-solution-icon
                                hero-solution-icon-pink
                            "
                        >
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path d="M12 3v3" />
                                <path d="M12 18v3" />
                                <path d="M3 12h3" />
                                <path d="M18 12h3" />

                                <path d="m5.64 5.64 2.12 2.12" />
                                <path d="m16.24 16.24 2.12 2.12" />

                                <path d="m18.36 5.64-2.12 2.12" />
                                <path d="m7.76 16.24-2.12 2.12" />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="4"
                                />
                            </svg>
                        </div>


                        <div className="hero-solution-copy">

                            <strong>
                                Business System
                            </strong>

                            <span>
                                Streamline your operations.
                            </span>

                        </div>

                    </div>


                    {/* =================================================
                        MOBILE APP
                    ================================================== */}

                    <div
                        className="
                            hero-solution-card
                            hero-solution-card-mobile
                        "
                    >

                        <div
                            className="
                                hero-solution-icon
                                hero-solution-icon-violet
                            "
                        >
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <rect
                                    x="6"
                                    y="2"
                                    width="12"
                                    height="20"
                                    rx="2"
                                />

                                <path d="M10 18h4" />
                            </svg>
                        </div>


                        <div className="hero-solution-copy">

                            <strong>
                                Mobile App
                            </strong>

                            <span>
                                Reach your customers anywhere.
                            </span>

                        </div>

                    </div>


                    {/* =================================================
                        CENTRAL BIZZSOFT HUB
                    ================================================== */}

                    <div className="hero-network-core">

                        <div className="hero-network-core-mark">
                            B
                        </div>


                        <strong>
                            BIZZ<span>SOFT</span>
                        </strong>


                        <small>
                            IDEAS TO REALITY
                        </small>

                    </div>


                    {/* =================================================
                        CUSTOM SOLUTION
                    ================================================== */}

                    <div
                        className="
                            hero-solution-card
                            hero-solution-card-custom
                        "
                    >

                        <div
                            className="
                                hero-solution-icon
                                hero-solution-icon-cyan
                            "
                        >
                            <svg
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    d="
                                        m12 3
                                        8 4.5
                                        v9
                                        L12 21
                                        l-8-4.5
                                        v-9
                                        L12 3Z
                                    "
                                />

                                <path
                                    d="
                                        m4.5 7.5
                                        7.5 4.2
                                        7.5-4.2
                                    "
                                />

                                <path
                                    d="
                                        M12 21
                                        v-9.3
                                    "
                                />
                            </svg>
                        </div>


                        <div className="hero-solution-copy">

                            <strong>
                                Custom Solution
                            </strong>

                            <span>
                                Built around your needs.
                            </span>

                        </div>

                    </div>


                    {/* Process labels removed */}

                </div>

            </div>


            {/* =====================================================
                BOTTOM LABEL
            ====================================================== */}

            <div className="hero-bottom-label">

                <span>
                    BIZZSOFT
                </span>

                <i />

                <span>
                    Your Partner in Digital Growth
                </span>

            </div>

        </section>
    );
}