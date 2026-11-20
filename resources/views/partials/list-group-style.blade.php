<style>
    .list-group-label { display: block; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--hp-muted); margin: 0 20px 8px; }
    .list-group { background: var(--hp-card); border-radius: 28px; overflow: hidden; margin: 0 16px 28px; }
    .list-row {
        display: flex; align-items: center; gap: 16px; min-height: 56px; padding: 0 20px;
        border-top: 1px solid var(--hp-line); color: var(--hp-fg); text-decoration: none;
    }
    .list-row:first-child { border-top: none; }
    .list-row > i:first-child { font-size: 24px; color: var(--hp-muted); flex-shrink: 0; }
    .list-row .label { flex: 1; font-size: 16px; }
    .list-row .t, .list-row .s { display: block; flex: 1; }
    .list-row .t { font-size: 16px; }
    .list-row .s { font-size: 12.5px; color: var(--hp-muted); margin-top: 2px; }
    .list-row .chev { font-size: 18px; color: var(--hp-muted); opacity: .7; flex-shrink: 0; }
    .list-row:hover, .list-row:focus-visible { background: var(--hp-bg); }
    .list-row:focus-visible { outline: 2px solid var(--hp-accent); outline-offset: -2px; }
    .list-row--accent > i:first-child, .list-row--accent .label { color: var(--hp-accent); }

    .switch { position: relative; width: 44px; height: 26px; border-radius: 999px; border: none; background: var(--hp-line); padding: 0; cursor: pointer; flex-shrink: 0; }
    .switch-thumb { position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: var(--hp-bg); transition: transform .15s ease; }
    .switch[aria-checked="true"] { background: var(--hp-accent); }
    .switch[aria-checked="true"] .switch-thumb { transform: translateX(18px); background: var(--hp-accent-on); }
    .switch:focus-visible { outline: 2px solid var(--hp-accent); outline-offset: 2px; }
</style>
