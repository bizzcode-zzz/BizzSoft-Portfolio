import { useEffect, useState } from 'react';

export default function ScrollProgress() {
    const [progress, setProgress] = useState(0);

    useEffect(() => {
        const updateProgress = () => {
            const scrollTop = window.scrollY;
            const documentHeight =
                document.documentElement.scrollHeight -
                window.innerHeight;

            const percentage =
                documentHeight > 0
                    ? (scrollTop / documentHeight) * 100
                    : 0;

            setProgress(Math.min(percentage, 100));
        };

        updateProgress();

        window.addEventListener('scroll', updateProgress, {
            passive: true,
        });

        window.addEventListener('resize', updateProgress);

        return () => {
            window.removeEventListener('scroll', updateProgress);
            window.removeEventListener('resize', updateProgress);
        };
    }, []);

    return (
        <div className="fixed inset-x-0 top-0 z-[100] h-[2px] bg-white/5">
            <div
                className="h-full origin-left bg-white transition-[width] duration-100"
                style={{
                    width: `${progress}%`,
                }}
            />
        </div>
    );
}