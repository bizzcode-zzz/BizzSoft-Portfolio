import AppLayout from '../Layouts/AppLayout';
import Hero from '../Components/Hero';
import TechnologyStack from '../Components/TechnologyStack';
import Projects from '../Components/Projects';
import MoreProjects from '../Components/MoreProjects';
import About from '../Components/About';
import ContactCTA from '../Components/ContactCTA';
import Footer from '../Components/Footer';

export default function Home() {
    return (
        <AppLayout>
            <Hero />

            <TechnologyStack />

            <Projects />

            <MoreProjects />

            <About />

            <ContactCTA />

            <Footer />
        </AppLayout>
    );
}