'use client'

import { useState } from 'react'
import { ArrowUpRight, ChevronDown, Menu, Moon, Play, Sun, X } from 'lucide-react'

const talent = [
  { name: 'Maya Okafor', role: 'Actor / Model', location: 'London', image: 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?auto=format&fit=crop&w=900&q=85' },
  { name: 'Jules Hart', role: 'Actor', location: 'Los Angeles', image: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=900&q=85' },
  { name: 'Amara Lee', role: 'Model / Dancer', location: 'New York', image: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=900&q=85' },
  { name: 'Theo Bennett', role: 'Actor / Musician', location: 'Berlin', image: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=900&q=85' },
]

const categories = ['All talent', 'Actors', 'Models', 'Dancers']

const portfolio = [
  { title: 'After the Rain', type: 'Feature film', year: '2025', image: 'https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&w=1200&q=85' },
  { title: 'Second Nature', type: 'Branded film · Nike', year: '2024', image: 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?auto=format&fit=crop&w=1200&q=85' },
  { title: 'The Quiet Hour', type: 'Television series', year: '2024', image: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1200&q=85' },
]

export default function Page() {
  const [menuOpen, setMenuOpen] = useState(false)
  const [lightMode, setLightMode] = useState(false)
  const [category, setCategory] = useState('All talent')
  const [showreel, setShowreel] = useState(false)

  return (
    <main className={`site-shell min-h-screen overflow-hidden text-[#f2f1eb] ${lightMode ? 'light-mode' : 'dark-mode'}`}>
      <header className="relative z-20 flex items-center justify-between border-b border-white/15 px-5 py-5 md:px-10">
        <a href="#top" className="flex items-center gap-2 text-sm font-semibold tracking-[-0.04em]" aria-label="Sideroom home">
          <span className="h-2.5 w-2.5 rounded-full bg-[#b7ff3c]" />
          SIDER<span className="text-[#b7ff3c]">OOM</span>
        </a>
        <nav className="hidden items-center gap-10 text-[11px] uppercase tracking-[0.16em] text-white/65 md:flex">
          <a href="#talent" className="transition-colors hover:text-[#b7ff3c]">Talent</a>
          <a href="#services" className="transition-colors hover:text-[#b7ff3c]">What we do</a><a href="#portfolio" className="transition-colors hover:text-[#b7ff3c]">Portfolio</a>
          <a href="#about" className="transition-colors hover:text-[#b7ff3c]">About</a>
        </nav>
        <div className="flex items-center gap-4"><button onClick={() => setLightMode(!lightMode)} className="flex items-center gap-2 text-[10px] uppercase tracking-[0.16em] text-white/55 transition-colors hover:text-[#b7ff3c]" aria-label={lightMode ? 'Switch to dark mode' : 'Switch to light mode'}>{lightMode ? <Moon size={14} /> : <Sun size={14} />}<span className="hidden lg:inline">{lightMode ? 'Dark' : 'Light'}</span></button><a href="#contact" className="hidden border border-white/25 px-4 py-2 text-[10px] uppercase tracking-[0.18em] transition-colors hover:border-[#b7ff3c] hover:text-[#b7ff3c] md:block">Get in touch ↗</a></div>
        <button className="md:hidden" aria-label={menuOpen ? 'Close menu' : 'Open menu'} onClick={() => setMenuOpen(!menuOpen)}>
          {menuOpen ? <X size={20} /> : <Menu size={20} />}
        </button>
        {menuOpen && <div className="absolute left-0 right-0 top-full border-b border-white/15 bg-[#111311] p-6 md:hidden"><div className="flex flex-col gap-5 text-xs uppercase tracking-[0.16em]"><a href="#talent" onClick={() => setMenuOpen(false)}>Talent</a><a href="#services" onClick={() => setMenuOpen(false)}>What we do</a><a href="#about" onClick={() => setMenuOpen(false)}>About</a><a href="#contact" onClick={() => setMenuOpen(false)} className="text-[#b7ff3c]">Get in touch ↗</a></div></div>}
      </header>

      <section id="top" className="hero-backdrop relative border-b border-white/15 bg-cover bg-center px-5 pb-8 pt-16 md:px-10 md:pb-12 md:pt-24" style={{ backgroundImage: "linear-gradient(90deg, rgba(17, 19, 17, 0.98) 0%, rgba(17, 19, 17, 0.82) 52%, rgba(17, 19, 17, 0.5) 100%), url('/sideroom-cinematic-bg.png')" }}>
        <div className="pointer-events-none absolute right-[7%] top-10 hidden select-none text-[10px] uppercase tracking-[0.2em] text-white/35 md:block">Independent casting / 01—26</div>
        <div className="max-w-5xl">
          <p className="mb-8 text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">A casting agency for the moving image</p>
          <h1 className="font-serif text-[clamp(4.5rem,13vw,12rem)] leading-[0.78] tracking-[-0.07em]">Faces<br /><span className="ml-[12vw] italic text-white/70">with</span><br /><span className="text-[#b7ff3c]">range.</span></h1>
        </div>
        <div className="mt-16 flex flex-col justify-between gap-8 md:mt-24 md:flex-row md:items-end">
          <p className="max-w-xs text-sm leading-relaxed text-white/60">We represent an unruly mix of actors, models and real people. The ones who make a frame feel like something.</p>
          <button onClick={() => setShowreel(true)} className="group flex items-center gap-3 self-start text-[10px] uppercase tracking-[0.18em] md:self-auto"><span className="flex h-12 w-12 items-center justify-center rounded-full border border-[#b7ff3c] text-[#b7ff3c] transition-colors group-hover:bg-[#b7ff3c] group-hover:text-[#111311]"><Play size={13} fill="currentColor" /></span> Play showreel <ArrowUpRight size={14} className="text-[#b7ff3c]" /></button>
        </div>
      </section>

      <section id="talent" className="px-5 py-16 md:px-10 md:py-24">
        <div className="mb-8 flex flex-col justify-between gap-5 md:flex-row md:items-end"><div><p className="mb-3 text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">01 / The roster</p><h2 className="font-serif text-4xl tracking-[-0.04em] md:text-6xl">Meet the cast</h2></div><div className="flex gap-5 text-[10px] uppercase tracking-[0.16em] text-white/45">{categories.map((item) => <button key={item} onClick={() => setCategory(item)} className={category === item ? 'text-[#b7ff3c]' : 'hover:text-white'}>{item}</button>)}</div></div>
        <div className="grid grid-cols-2 gap-px bg-white/15 md:grid-cols-4">{talent.map((person, i) => <a href="/profile" key={person.name} className="group bg-[#111311] pb-5"><div className="relative aspect-[0.78] overflow-hidden bg-white/10"><img src={person.image} alt={`${person.name}, ${person.role}`} className="h-full w-full object-cover grayscale transition duration-500 group-hover:scale-105 group-hover:grayscale-0" /><span className="absolute left-3 top-3 text-[10px] text-white/60">0{i + 1}</span><span className="absolute bottom-3 right-3 rounded-full bg-[#b7ff3c] px-2 py-1 text-[9px] font-semibold uppercase tracking-widest text-[#111311] opacity-0 transition-opacity group-hover:opacity-100">View ↗</span></div><div className="pt-4"><h3 className="font-serif text-xl tracking-[-0.03em]">{person.name}</h3><p className="mt-1 text-[10px] uppercase tracking-[0.14em] text-white/45">{person.role} · {person.location}</p></div></a>)}</div>
        <button className="mx-auto mt-12 flex items-center gap-3 border-b border-[#b7ff3c] pb-2 text-[10px] uppercase tracking-[0.18em] text-[#b7ff3c]">Explore full roster <ArrowUpRight size={13} /></button>
      </section>

      <section id="services" className="grid border-y border-white/15 md:grid-cols-2"><div className="border-b border-white/15 p-5 md:border-b-0 md:border-r md:p-10"><p className="mb-16 text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">02 / The work</p><h2 className="max-w-lg font-serif text-4xl leading-[0.92] tracking-[-0.05em] md:text-6xl">A good casting<br /><span className="italic text-white/55">changes everything.</span></h2></div><div className="p-5 md:p-10"><div className="divide-y divide-white/15">{['Feature film & television', 'Commercials & branded content', 'Editorial & music video'].map((item, i) => <div key={item} className="flex items-center justify-between py-6 text-sm"><span><span className="mr-5 text-[10px] text-[#b7ff3c]">0{i + 1}</span>{item}</span><ChevronDown size={15} className="rotate-[-45deg] text-white/40" /></div>)}</div><p className="mt-16 max-w-sm text-sm leading-relaxed text-white/55">From first brief to final frame, our team brings intuition, precision and a genuinely human eye to every search.</p></div></section>

      <section id="portfolio" className="border-t border-white/15 px-5 py-16 md:px-10 md:py-24"><div className="mb-8 flex items-end justify-between"><div><p className="mb-3 text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">03 / Selected work</p><h2 className="font-serif text-4xl tracking-[-0.04em] md:text-6xl">On screen</h2></div><span className="text-[10px] uppercase tracking-[0.16em] text-white/40">Films we&apos;ve cast</span></div><div className="grid gap-px bg-white/15 md:grid-cols-3">{portfolio.map((project, i) => <article key={project.title} className="group bg-[#111311]"><div className="relative aspect-[1.28] overflow-hidden"><img src={project.image} alt={`${project.title} ${project.type}`} className="h-full w-full object-cover grayscale transition duration-500 group-hover:scale-105 group-hover:grayscale-0" /><span className="absolute left-3 top-3 text-[10px] text-white/65">0{i + 1}</span></div><div className="flex items-start justify-between gap-4 py-5"><div><h3 className="font-serif text-2xl tracking-[-0.03em]">{project.title}</h3><p className="mt-1 text-[10px] uppercase tracking-[0.14em] text-white/45">{project.type}</p></div><span className="text-[10px] text-[#b7ff3c]">{project.year}</span></div></article>)}</div></section>

      <section id="about" className="px-5 py-20 md:px-10 md:py-28"><div className="flex flex-col gap-10 md:flex-row md:justify-between"><p className="text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">04 / About the company</p><div className="max-w-2xl"><p className="font-serif text-3xl leading-tight tracking-[-0.04em] md:text-5xl">Sideroom is an independent casting agency built around instinct. We look beyond the obvious to find the people you can&apos;t stop watching.</p><p className="mt-8 max-w-lg text-sm leading-relaxed text-white/55">From our studio in London, we work with directors, production companies and brands across the world. Our roster is deliberately considered: distinctive faces, honest performances and talent with a point of view.</p></div></div></section>

      <footer id="contact" className="border-t border-white/15 px-5 pb-8 pt-12 md:px-10 md:pt-16"><div className="flex flex-col justify-between gap-10 md:flex-row"><div><p className="mb-4 text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">Start a conversation</p><a href="mailto:hello@sideroom.agency" className="font-serif text-3xl tracking-[-0.04em] transition-colors hover:text-[#b7ff3c] md:text-5xl">hello@sideroom.agency</a></div><div className="flex gap-16 text-[10px] uppercase tracking-[0.16em] text-white/55"><div><p className="mb-3 text-white">Find us</p><p>London · Los Angeles<br />New York · Berlin</p></div><div><p className="mb-3 text-white">Follow</p><p>Instagram<br />Vimeo</p></div></div></div><div className="mt-20 flex justify-between border-t border-white/15 pt-5 text-[9px] uppercase tracking-[0.16em] text-white/35"><span>© Sideroom 2026</span><span>Made for moving images</span></div></footer>

      {showreel && <div className="fixed inset-0 z-50 flex items-center justify-center bg-[#111311]/95 p-5" role="dialog" aria-modal="true" aria-label="Sideroom showreel"><button onClick={() => setShowreel(false)} className="absolute right-5 top-5 text-white/70 hover:text-[#b7ff3c]" aria-label="Close showreel"><X /></button><div className="relative flex aspect-video w-full max-w-4xl items-center justify-center overflow-hidden bg-[#252a23]"><img src="https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&w=1600&q=85" alt="Film set with a cinema camera" className="absolute inset-0 h-full w-full object-cover opacity-50" /><div className="relative text-center"><span className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#b7ff3c] text-[#111311]"><Play fill="currentColor" /></span><p className="text-[10px] uppercase tracking-[0.25em]">Showreel loading</p></div></div></div>}
    </main>
  )
}


