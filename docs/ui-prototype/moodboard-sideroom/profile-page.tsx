'use client'

import { useState } from 'react'
import { ArrowLeft, ArrowUpRight, Edit3, Moon, Share2, Sun } from 'lucide-react'

const details = [
  ['Usia', '30 tahun'],
  ['Jenis Kelamin', 'Perempuan'],
  ['Tinggi Badan', '162 cm'],
  ['Ukuran Baju', 'M'],
  ['Warna Kulit', 'Kuning langsat'],
]

export default function ProfilePage() {
  const [lightMode, setLightMode] = useState(false)

  return (
    <main className={`site-shell min-h-screen ${lightMode ? 'light-mode' : 'dark-mode'}`}>
      <header className="relative z-20 flex items-center justify-between border-b border-white/15 px-5 py-5 md:px-10">
        <a href="/" className="flex items-center gap-2 text-sm font-semibold tracking-[-0.04em]"><span className="h-2.5 w-2.5 rounded-full bg-[#b7ff3c]" /> SIDER<span className="text-[#b7ff3c]">OOM</span></a>
        <div className="flex items-center gap-5"><button onClick={() => setLightMode(!lightMode)} className="flex items-center gap-2 text-[10px] uppercase tracking-[0.16em] text-white/60 hover:text-[#b7ff3c]" aria-label={lightMode ? 'Switch to dark mode' : 'Switch to light mode'}>{lightMode ? <Moon size={14} /> : <Sun size={14} />}<span className="hidden sm:inline">{lightMode ? 'Dark' : 'Light'}</span></button><a href="/" className="hidden text-[10px] uppercase tracking-[0.16em] text-white/60 hover:text-[#b7ff3c] sm:flex sm:items-center sm:gap-2"><ArrowLeft size={14} /> Back to roster</a></div>
      </header>

      <section className="border-b border-white/15 px-5 py-8 md:px-10 md:py-12">
        <div className="mb-10 flex items-center justify-between"><p className="text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">Talent profile / 001</p><div className="flex gap-4 text-[10px] uppercase tracking-[0.16em] text-white/55"><button className="flex items-center gap-2 hover:text-[#b7ff3c]"><Share2 size={14} /> Share</button><button className="flex items-center gap-2 hover:text-[#b7ff3c]"><Edit3 size={14} /> Edit profil</button></div></div>
        <div className="grid gap-10 md:grid-cols-[minmax(260px,420px)_1fr] md:items-end md:gap-16">
          <div className="relative aspect-[0.82] overflow-hidden bg-white/10"><img src="https://images.unsplash.com/photo-1531123897727-8f129e1688ce?auto=format&fit=crop&w=1000&q=85" alt="Aisyah, talent profile" className="h-full w-full object-cover grayscale" /><span className="absolute bottom-4 left-4 bg-[#b7ff3c] px-3 py-2 text-[10px] font-semibold uppercase tracking-[0.15em] text-[#111311]">Available</span></div>
          <div><p className="mb-4 text-[10px] uppercase tracking-[0.18em] text-white/45">Actor / Extra</p><h1 className="font-serif text-7xl leading-[0.82] tracking-[-0.07em] md:text-[9rem]">aisyah<span className="text-[#b7ff3c]">_f</span></h1><div className="mt-8 flex flex-wrap gap-3 text-[10px] uppercase tracking-[0.15em] text-white/60"><span className="border border-white/20 px-3 py-2">Jakarta</span><span className="border border-white/20 px-3 py-2">Bahasa Indonesia</span><span className="border border-white/20 px-3 py-2">Bahasa Arab</span></div><div className="mt-10 flex items-center gap-3"><span className="h-2 w-2 rounded-full bg-[#b7ff3c]" /><span className="text-[10px] uppercase tracking-[0.18em] text-white/55">Grade belum dinilai</span></div></div>
        </div>
      </section>

      <section className="grid border-b border-white/15 md:grid-cols-2">
        <div className="border-b border-white/15 p-5 md:border-b-0 md:border-r md:p-10"><p className="mb-14 text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">01 / Data diri & ciri fisik</p><div className="grid grid-cols-2 gap-x-8 gap-y-8">{details.map(([label, value]) => <div key={label}><p className="mb-2 text-[10px] uppercase tracking-[0.15em] text-white/40">{label}</p><p className="font-serif text-2xl tracking-[-0.03em]">{value}</p></div>)}</div></div>
        <div className="p-5 md:p-10"><p className="mb-14 text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">02 / Pengalaman & kemampuan</p><div className="space-y-8"><div><p className="mb-2 text-[10px] uppercase tracking-[0.15em] text-white/40">Pengalaman main / kerja</p><p className="max-w-md text-sm leading-relaxed text-white/75">Guru les privat. Pernah jadi extras iklan Ramadan.</p></div><div><p className="mb-2 text-[10px] uppercase tracking-[0.15em] text-white/40">Bahasa</p><p className="font-serif text-2xl">Indonesia, Arab</p></div><div><p className="mb-2 text-[10px] uppercase tracking-[0.15em] text-white/40">Tautan tambahan</p><p className="text-sm text-white/55">-</p></div></div></div>
      </section>

      <section className="grid border-b border-white/15 md:grid-cols-[1fr_1fr]"><div className="p-5 md:p-10"><p className="mb-14 text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">03 / Showreel</p><div className="flex aspect-video items-center justify-center border border-dashed border-white/25 bg-white/[0.03]"><div className="text-center"><p className="mb-3 font-serif text-3xl">Video profil</p><p className="text-[10px] uppercase tracking-[0.15em] text-white/40">Belum ada video</p></div></div></div><div className="border-t border-white/15 p-5 md:border-l md:border-t-0 md:p-10"><p className="mb-14 text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">04 / Tarif</p><p className="mb-3 text-[10px] uppercase tracking-[0.15em] text-white/40">Tarif harapan</p><p className="font-serif text-5xl tracking-[-0.05em]">Rp 250.000</p><p className="mt-3 text-sm text-white/45">per project / dapat dibicarakan</p><a href="mailto:casting@sideroom.agency?subject=Talent%20aisyah_f" className="mt-12 flex w-fit items-center gap-3 border-b border-[#b7ff3c] pb-2 text-[10px] uppercase tracking-[0.18em] text-[#b7ff3c]">Contact talent <ArrowUpRight size={14} /></a></div></section>

      <section className="px-5 py-16 md:px-10 md:py-24"><div className="mb-8 flex items-end justify-between"><div><p className="mb-3 text-[10px] uppercase tracking-[0.2em] text-[#b7ff3c]">05 / Gallery</p><h2 className="font-serif text-5xl tracking-[-0.05em]">Selected frames</h2></div><span className="text-[10px] uppercase tracking-[0.16em] text-white/40">Gallery masih kosong</span></div><div className="flex min-h-48 items-center justify-center border border-dashed border-white/20 bg-white/[0.03] text-[10px] uppercase tracking-[0.16em] text-white/35">No images added yet</div></section>

      <footer className="border-t border-white/15 px-5 py-6 md:px-10"><div className="flex justify-between text-[9px] uppercase tracking-[0.16em] text-white/35"><span>© Sideroom 2026</span><span>Made for moving images</span></div></footer>
    </main>
  )
}
