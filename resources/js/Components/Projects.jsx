import { motion } from 'framer-motion';
import {
    SiLaravel,
    SiReact,
    SiInertia,
    SiMysql,
} from '@icons-pack/react-simple-icons';

export default function Projects() {
    const technologies = [
        {
            name: 'Laravel',
            icon: SiLaravel,
            className: 'project-tech-laravel',
        },
        {
            name: 'React',
            icon: SiReact,
            className: 'project-tech-react',
        },
        {
            name: 'Inertia',
            icon: SiInertia,
            className: 'project-tech-inertia',
        },
        {
            name: 'MySQL',
            icon: SiMysql,
            className: 'project-tech-mysql',
        },
    ];

    return (
        <section id="projects" className="projects-section">
            <div className="projects-container">

                {/* =====================================================
                    PROJECTS HEADING
                ===================================================== */}

                <div className="projects-heading">
                    <p className="projects-eyebrow">
                        <span className="projects-eyebrow-line"></span>
                        Selected Work
                    </p>

                    <h2 className="projects-title">
                        Featured <span>Projects</span>
                    </h2>

                    <p className="projects-intro">
                        A selection of digital products and systems built
                        with modern technologies, focused on performance,
                        usability, and real-world business needs.
                    </p>
                </div>


                {/* =====================================================
                    FEATURED PROJECT
                ===================================================== */}

                <article className="project-feature">

                    <div className="project-feature-glow"></div>
                    <div className="project-feature-grid"></div>


                    {/* =================================================
                        PROJECT INFORMATION
                    ================================================= */}

                    <div className="project-info">

                        <div className="project-meta">
                            <span className="project-number">
                                01
                            </span>

                            <span className="project-type">
                                Featured Project
                            </span>
                        </div>


                        <h3 className="project-title">
                            BizzSoft <span>V5</span>
                        </h3>


                        <p className="project-description">
                            A production-ready business and inventory
                            management system designed to manage products,
                            categories, suppliers, purchases, sales, stock,
                            reports, and user activity in one modern platform.
                        </p>


                        {/* =================================================
                            TECHNOLOGIES
                        ================================================= */}

                        <div className="project-technologies">

                            {technologies.map(
                                (
                                    {
                                        name,
                                        icon: Icon,
                                        className,
                                    },
                                    index,
                                ) => (
                                    <motion.div
                                        key={name}
                                        className={`project-tech-node ${className}`}
                                        initial={{
                                            opacity: 0,
                                            y: -45,
                                            scale: 0.72,
                                        }}
                                        whileInView={{
                                            opacity: 1,
                                            y: 0,
                                            scale: 1,
                                        }}
                                        viewport={{
                                            once: true,
                                            amount: 0.35,
                                        }}
                                        transition={{
                                            duration: 0.65,
                                            delay: index * 0.18,
                                            ease: [
                                                0.22,
                                                1,
                                                0.36,
                                                1,
                                            ],
                                        }}
                                    >
                                        <span className="project-tech-line"></span>

                                        <span className="project-tech-circle">
                                            <Icon
                                                size={34}
                                                strokeWidth={1.8}
                                            />
                                        </span>

                                        <span className="project-tech-name">
                                            {name}
                                        </span>
                                    </motion.div>
                                ),
                            )}

                        </div>


                        {/* =================================================
                            PROJECT ACTIONS
                        ================================================= */}

                        <div className="project-actions">

                            <a
                                href="https://bizzsoft.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="project-button"
                            >
                                <span>
                                    View Live Project
                                </span>

                                <span className="project-button-arrow">
                                    <span className="project-button-arrow-icon">→</span>
                                </span>
                            </a>

                            <a
                                href="https://github.com/bizzcode-zzz"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="project-button project-button-secondary"
                            >
                                <span>
                                    Admin Panel
                                </span>

                                <span className="project-button-arrow">
                                    <span className="project-button-arrow-icon">→</span>
                                </span>
                            </a>

                        </div>

                    </div>


                    {/* =================================================
                        PROJECT PREVIEW
                    ================================================= */}

                    <div className="project-preview">

                        <div className="project-preview-window">

                            <div className="project-preview-topbar">

                                <div className="project-preview-dots">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>

                                <span className="project-preview-label">
                                    BIZZSOFT V5
                                </span>

                                <span className="project-preview-status">
                                    LIVE
                                </span>

                            </div>


                            <div className="project-preview-screen">

                                <div className="project-dashboard-header">

                                    <div>
                                        <span className="project-dashboard-label">
                                            Dashboard
                                        </span>

                                        <strong>
                                            Business Overview
                                        </strong>
                                    </div>

                                    <div className="project-dashboard-avatar">
                                        BS
                                    </div>

                                </div>


                                <div className="project-dashboard-cards">

                                    <div className="project-dashboard-card">
                                        <span>
                                            Total Products
                                        </span>

                                        <strong>
                                            248
                                        </strong>
                                    </div>

                                    <div className="project-dashboard-card">
                                        <span>
                                            Total Sales
                                        </span>

                                        <strong>
                                            ₱84.6K
                                        </strong>
                                    </div>

                                    <div className="project-dashboard-card">
                                        <span>
                                            Low Stock
                                        </span>

                                        <strong>
                                            12
                                        </strong>
                                    </div>

                                </div>


                                <div className="project-dashboard-chart">

                                    <div className="project-chart-header">
                                        <span>
                                            Sales Overview
                                        </span>

                                        <span>
                                            2026
                                        </span>
                                    </div>


                                    <div className="project-chart-area">

                                        <span className="project-chart-line"></span>
                                        <span className="project-chart-line"></span>
                                        <span className="project-chart-line"></span>
                                        <span className="project-chart-line"></span>


                                        <div className="project-chart-path">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <div className="project-preview-orbit project-preview-orbit-one"></div>
                        <div className="project-preview-orbit project-preview-orbit-two"></div>

                        <div className="project-preview-particle project-preview-particle-one"></div>
                        <div className="project-preview-particle project-preview-particle-two"></div>

                    </div>

                </article>

            </div>
        </section>
    );
}