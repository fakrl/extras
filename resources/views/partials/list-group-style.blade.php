<style>
    /* Token var(--hp-*) dipakai landing (welcome/tentang), fallback var(--*) dipakai layout app (dashboard). */
    .list-group-label { display: block; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--hp-muted, var(--text-muted)); margin: 0 20px 8px; }
    .list-group { background: var(--hp-card, var(--bg-card)); border-radius: 28px; overflow: hidden; margin: 0 16px 28px; }
    .list-row {
        display: flex; align-items: center; gap: 16px; min-height: 56px; padding: 0 20px;
        border-top: 1px solid var(--hp-line, var(--border-color)); color: var(--hp-fg, var(--text-primary)); text-decoration: none;
        width: 100%; background: none; border-left: none; border-right: none; border-bottom: none; font: inherit; cursor: pointer; text-align: left;
    }
    .list-row:first-child { border-top: none; }
    .list-row > i:first-child { font-size: 24px; color: var(--hp-muted, var(--text-muted)); flex-shrink: 0; }
    .list-row .label { flex: 1; font-size: 16px; }
    .list-row .t, .list-row .s { display: block; flex: 1; }
    .list-row .t { font-size: 16px; }
    .list-row .s { font-size: 12.5px; color: var(--hp-muted, var(--text-muted)); margin-top: 2px; }
    .list-row .chev { font-size: 18px; color: var(--hp-muted, var(--text-muted)); opacity: .7; flex-shrink: 0; }
    .list-row:hover, .list-row:focus-visible { background: var(--hp-bg, var(--bg-page, var(--bg-card-hover))); }
    .list-row:focus-visible { outline: 2px solid var(--hp-accent, var(--accent)); outline-offset: -2px; }
    .list-row--accent > i:first-child, .list-row--accent .label { color: var(--hp-accent, var(--accent-strong)); }
    .list-row--danger > i:first-child, .list-row--danger .label { color: var(--danger); }

    .switch { position: relative; width: 44px; height: 26px; border-radius: 999px; border: none; background: var(--hp-line, var(--border-color)); padding: 0; cursor: pointer; flex-shrink: 0; }
    .switch-thumb { position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: var(--hp-bg, var(--bg-card)); transition: transform .15s ease; }
    .switch[aria-checked="true"] { background: var(--hp-accent, var(--accent)); }
    .switch[aria-checked="true"] .switch-thumb { transform: translateX(18px); background: var(--hp-accent-on, var(--accent-on)); }
    .switch:focus-visible { outline: 2px solid var(--hp-accent, var(--accent)); outline-offset: 2px; }

    /* Dock bawah (pill) + sheet menu, dipakai landing (partials.pill-nav) & app layout (Extras/Korlap). */
    .bottom-dock { display: none; }
    dialog.nav-sheet-dlg { margin: 0; width: 100%; max-width: 480px; border: none; border-radius: 24px 24px 0 0; padding: 0; background: var(--hp-bg, var(--bg-card)); color: var(--hp-fg, var(--text-primary)); position: fixed; left: 50%; bottom: 0; transform: translateX(-50%); max-height: 86vh; overflow-y: auto; }
    dialog.nav-sheet-dlg::backdrop { background: rgba(0, 0, 0, .5); }
    .nav-sheet-head { position: sticky; top: 0; display: flex; align-items: center; justify-content: space-between; padding: 18px 20px 10px; background: var(--hp-bg, var(--bg-card)); }
    .nav-sheet-title { font-size: 16px; font-weight: 700; }
    .nav-sheet-close { width: 36px; height: 36px; border-radius: 50%; border: none; background: var(--hp-card, var(--bg-card-hover)); color: var(--hp-fg, var(--text-primary)); display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .nav-sheet-body { padding-top: 6px; padding-bottom: calc(16px + env(safe-area-inset-bottom)); }
    .nav-sheet-account { display: flex; align-items: center; gap: 14px; }
    .nav-sheet-account img, .nav-sheet-account .avatar-badge { width: 44px; height: 44px; font-size: 15px; }

    @media (max-width: 768px) {
        .bottom-dock {
            display: flex; position: fixed; left: 50%; transform: translateX(-50%);
            bottom: calc(14px + env(safe-area-inset-bottom)); z-index: 200;
            background: color-mix(in srgb, var(--hp-bg, var(--bg-card)) 90%, transparent);
            border: 1px solid var(--hp-line, var(--border-color)); border-radius: 999px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            padding: 4px; gap: 2px; max-width: calc(100% - 24px);
        }
        .bottom-dock-item {
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;
            min-width: 56px; min-height: 48px; padding: 4px 6px; border-radius: 999px;
            color: var(--hp-muted, var(--text-muted)); text-decoration: none; font-size: 10.5px; font-weight: 600;
            border: none; background: none; font-family: inherit; cursor: pointer;
        }
        .bottom-dock-item i { font-size: 19px; }
        .bottom-dock-item:hover, .bottom-dock-item.is-active { color: var(--hp-fg, var(--text-primary)); background: var(--hp-card, var(--bg-card-hover)); }
        .bottom-dock-item:focus-visible { outline: 2px solid var(--hp-accent, var(--accent)); outline-offset: -2px; }
    }

    @media (prefers-reduced-motion: no-preference) {
        .bottom-dock-item { transition: background .15s ease, color .15s ease; }
    }
</style>
