import { useState } from 'react';

export default function MoreProjects() {
    const [isOpen, setIsOpen] = useState(false);

    const projectCategories = [
        'Laravel',
        'WordPress',
        'Flutter',
    ];

    return (
        <section className="more-projects-section">
            <div className="more-projects-container">

                <div className="more-projects-content">

                    <div className="more-projects-intro">
                        <span className="more-projects-eyebrow">
                            Explore More
                        </span>

                        <p className="more-projects-description">
                            Discover more projects across different
                            technologies and platforms.
                        </p>
                    </div>

                    <div className="more-projects-selector">

                        <button
                            type="button"
                            className={`more-projects-trigger ${
                                isOpen ? 'is-open' : ''
                            }`}
                            onClick={() => setIsOpen(!isOpen)}
                            aria-expanded={isOpen}
                        >
                            <span>
                                View More Projects
                            </span>

                            <span className="more-projects-trigger-icon">
                                <span className="more-projects-chevron">
                                    ↓
                                </span>
                            </span>
                        </button>

                        <div
                            className={`more-projects-dropdown ${
                                isOpen ? 'is-open' : ''
                            }`}
                        >
                            {projectCategories.map((category) => (
                                <button
                                    key={category}
                                    type="button"
                                    className="more-projects-option"
                                >
                                    <span>{category}</span>

                                    <span className="more-projects-option-arrow">
                                        →
                                    </span>
                                </button>
                            ))}
                        </div>

                    </div>

                </div>

            </div>
        </section>
    );
}