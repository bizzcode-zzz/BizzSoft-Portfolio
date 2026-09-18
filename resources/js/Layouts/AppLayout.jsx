import Navbar from '../Components/Navbar';
import ScrollProgress from '../Components/ScrollProgress';

export default function AppLayout({ children }) {
    return (
        <div className="min-h-screen bg-neutral-950 text-white">
            <ScrollProgress />
            <Navbar />

            <main>
                {children}
            </main>
        </div>
    );
}