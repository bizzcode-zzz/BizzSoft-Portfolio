import { useEffect, useRef, useState } from 'react';

import {
    SiPhp,
    SiLaravel,
    SiReact,
    SiInertia,
    SiWordpress,
    SiTailwindcss,
    SiMysql,
    SiVite,
} from '@icons-pack/react-simple-icons';


export default function TechnologyStack() {
    const sectionRef = useRef(null);

    const [typedLines, setTypedLines] = useState([
        '',
        '',
        '',
        '',
    ]);

    const [activeLine, setActiveLine] = useState(0);


    const terminalLines = [
        'Building digital products...',
        'Connecting modern technologies...',
        'Turning ideas into real-world solutions...',
        'This is the stack I use.',
    ];


    useEffect(() => {
        const section = sectionRef.current;

        if (!section) return;

        let typingTimer;


        const observer = new IntersectionObserver(
            ([entry]) => {
                if (!entry.isIntersecting) return;

                section.classList.add('is-visible');


                let lineIndex = 0;
                let characterIndex = 0;


                setActiveLine(0);


                const typeNextCharacter = () => {
                    const currentLine =
                        terminalLines[lineIndex];


                    if (
                        characterIndex <
                        currentLine.length
                    ) {
                        setTypedLines((previous) => {
                            const updated = [
                                ...previous,
                            ];


                            updated[lineIndex] =
                                currentLine.slice(
                                    0,
                                    characterIndex + 1
                                );


                            return updated;
                        });


                        characterIndex += 1;


                        typingTimer = setTimeout(
                            typeNextCharacter,
                            32
                        );


                        return;
                    }


                    lineIndex += 1;
                    characterIndex = 0;


                    if (
                        lineIndex <
                        terminalLines.length
                    ) {
                        setActiveLine(lineIndex);


                        typingTimer = setTimeout(
                            typeNextCharacter,
                            280
                        );
                    }
                };


                typingTimer = setTimeout(
                    typeNextCharacter,
                    450
                );


                observer.disconnect();
            },
            {
                threshold: 0.2,
            }
        );


        observer.observe(section);


        return () => {
            observer.disconnect();
            clearTimeout(typingTimer);
        };
    }, []);


    /*
    ============================================================
    TECHNOLOGY DATA
    ============================================================
    */

    const technologies = [
        {
            name: 'PHP',
            icon: SiPhp,
            className: 'tech-node-php',
        },

        {
            name: 'Laravel',
            icon: SiLaravel,
            className: 'tech-node-laravel',
        },

        {
            name: 'React',
            icon: SiReact,
            className: 'tech-node-react',
        },

        {
            name: 'Inertia',
            icon: SiInertia,
            className: 'tech-node-inertia',
        },

        {
            name: 'WordPress',
            icon: SiWordpress,
            className: 'tech-node-wordpress',
        },

        {
            name: 'Tailwind',
            icon: SiTailwindcss,
            className: 'tech-node-tailwind',
        },

        {
            name: 'MySQL',
            icon: SiMysql,
            className: 'tech-node-mysql',
        },

        {
            name: 'Vite',
            icon: SiVite,
            className: 'tech-node-vite',
        },
    ];


    return (
        <section
            ref={sectionRef}
            id="technology"
            className="technology-stack relative overflow-hidden"
        >

            {/* ==================================================
                BACKGROUND ATMOSPHERE
                ================================================== */}

            <div className="technology-atmosphere pointer-events-none absolute inset-0">

                <div className="technology-glow technology-glow-one" />

                <div className="technology-glow technology-glow-two" />

            </div>


            {/* ==================================================
                SECTION INTRODUCTION
                ================================================== */}

            <div className="technology-heading relative z-10 mx-auto max-w-7xl px-6 sm:px-10 lg:px-12">

                {/* Eyebrow */}

                <span className="technology-eyebrow">
                    Technology Stack
                </span>


                {/* Main Title */}

                <h2 className="technology-title">

                    The tools

                    <br />

                    behind the

                    <span> systems.</span>

                </h2>


                {/* Terminal Introduction */}

                <div className="technology-terminal">

                    {typedLines.map((line, index) => (

                        <div
                            key={index}
                            className="technology-terminal-line"
                        >

                            <span className="technology-terminal-prompt">
                                &gt;
                            </span>


                            <span>
                                {line}
                            </span>


                            {index === activeLine && (

                                <span className="technology-terminal-cursor" />

                            )}

                        </div>

                    ))}

                </div>


                {/* Supporting Description */}

                <p className="technology-description">

                    I combine modern web technologies to build
                    scalable applications, business systems, and
                    digital experiences designed for real-world use.

                </p>


                {/* Capability Highlights */}

                <div className="technology-highlights">

                    <div className="technology-highlight">

                        <span className="technology-highlight-icon">
                            ◇
                        </span>

                        <span className="technology-highlight-label">

                            Clean

                            <br />

                            Architecture

                        </span>

                    </div>


                    <div className="technology-highlight">

                        <span className="technology-highlight-icon">
                            ◇
                        </span>

                        <span className="technology-highlight-label">

                            Modern

                            <br />

                            Development

                        </span>

                    </div>


                    <div className="technology-highlight">

                        <span className="technology-highlight-icon">
                            ◇
                        </span>

                        <span className="technology-highlight-label">

                            Real-World

                            <br />

                            Solutions

                        </span>

                    </div>

                </div>

            </div>


            {/* ==================================================
                TECHNOLOGY CONSTELLATION
                ================================================== */}

            <div
    className="technology-constellation relative z-10"
    style={{
        position: 'relative',
        width: '100%',
        height: '760px',
    }}
>
    <div className="technology-constellation-inner">

                {/* Orbit Rings */}

                <div className="technology-orbit technology-orbit-one" />

                <div className="technology-orbit technology-orbit-two" />

                <div className="technology-orbit technology-orbit-three" />


                {/* Connection Lines */}

                <div className="technology-line technology-line-one" />

                <div className="technology-line technology-line-two" />

                <div className="technology-line technology-line-three" />

                <div className="technology-line technology-line-four" />

                <div className="technology-line technology-line-five" />

                <div className="technology-line technology-line-six" />

                <div className="technology-line technology-line-seven" />

                <div className="technology-line technology-line-eight" />


                {/* Central Core */}

                <div className="technology-core">

                    <div className="technology-core-ring" />


                    <div className="technology-core-content">

                        <span>
                            BIZZSOFT
                        </span>

                        <small>
                            Digital Systems
                        </small>

                    </div>

                </div>


                {/* ==================================================
                    TECHNOLOGY NODES
                    ================================================== */}

                {technologies.map((technology) => {

                    const Icon = technology.icon;


                    return (
                        <div
                            key={technology.name}
                            className={`technology-node ${technology.className}`}
                            style={{
                                position: 'absolute',
                            }}
                        >

                            <div className="technology-node-inner">

                                <span className="technology-node-mark">

                                    <Icon
                                        size={42}
                                        strokeWidth={1.8}
                                    />

                                </span>


                                <span className="technology-node-name">

                                    {technology.name}

                                </span>

                            </div>

                        </div>
                    );

                })}


                {/* Ambient Particles */}

                <span className="technology-particle technology-particle-one" />

                <span className="technology-particle technology-particle-two" />

                <span className="technology-particle technology-particle-three" />

                <span className="technology-particle technology-particle-four" />

                <span className="technology-particle technology-particle-five" />

                <span className="technology-particle technology-particle-six" />

            </div>
            </div>



            {/* ==================================================
                BOTTOM STATEMENT
                ================================================== */}

            <div className="technology-footer relative z-10">

                <span>
                    DESIGN · DEVELOPMENT · INTERACTION
                </span>

            </div>

        </section>
    );
}