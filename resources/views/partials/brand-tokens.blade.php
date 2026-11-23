<style>
    {{-- override sementara karena theme-style.blade.php masih WIP; lipat ke sana setelah WIP beres --}}
    :root, :root[data-theme="light"] {
        --md-primary: #0F6B73;
        --md-on-primary: #FFFFFF;
        --md-primary-container: #CDE9EA;
        --md-on-primary-container: #05282B;
        --md-secondary: #5A6663;
        --md-on-secondary: #FFFFFF;
        --md-secondary-container: #E4E9E7;
        --md-on-secondary-container: #151C1B;
        --md-tertiary: #6A5F4D;
        --md-on-tertiary: #FFFFFF;
        --md-tertiary-container: #EFE6D6;
        --md-on-tertiary-container: #241B0B;
        --md-surface: #F7F4EE;
        --md-surface-container-lowest: #FFFDF8;
        --md-surface-container-low: #F2EFE8;
        --md-surface-container: #EDEAE2;
        --md-surface-container-high: #E7E3DA;
        --md-surface-container-highest: #E1DDD3;
        --md-on-surface: #1A1714;
        --md-on-surface-variant: #4A453D;
        --md-outline: #7A7367;
        --md-outline-variant: #D3CDBF;

        --accent: #0F6B73;
        --accent-strong: #0A4F56;
        --accent-on: #FFFFFF;
        --bg-page: #F7F4EE;
        --bg-sidebar: #FFFDF8;
        --bg-card: #F2EFE8;
        --bg-card-hover: #EDEAE2;
        --bg-nav-active: #CDE9EA;
        --text-primary: #1A1714;
        --text-secondary: #4A453D;
        --text-muted: #6A6459;
        --border-color: #D3CDBF;

        --md-surface-dim: #DAD6CC;
        --md-tertiary-dark: #1A1714;
        --md-outline-hairline: rgba(26, 23, 20, 0.08);
        --md-shadow-ambient: 0 4px 16px -2px rgba(26, 23, 20, 0.06), 0 1px 3px 0 rgba(26, 23, 20, 0.04);
    }

    :root[data-theme="dark"] {
        --md-primary: #5CC3CB;
        --md-on-primary: #07282B;
        --md-primary-container: #0F4A50;
        --md-on-primary-container: #BDEFF3;
        --md-secondary: #B6C4C1;
        --md-on-secondary: #1F2A28;
        --md-secondary-container: #36413F;
        --md-on-secondary-container: #D2DFDC;
        --md-tertiary: #D8CBB4;
        --md-on-tertiary: #3A2F1B;
        --md-tertiary-container: #54472F;
        --md-on-tertiary-container: #F1E6D0;
        --md-surface: #131110;
        --md-surface-container-lowest: #0E0C0B;
        --md-surface-container-low: #1B1816;
        --md-surface-container: #201D1A;
        --md-surface-container-high: #2A2623;
        --md-surface-container-highest: #34302C;
        --md-on-surface: #F2EEE6;
        --md-on-surface-variant: #CFC8BB;
        --md-outline: #968F83;
        --md-outline-variant: #433E37;

        --accent: #5CC3CB;
        --accent-strong: #8FDDE3;
        --accent-on: #07282B;
        --bg-page: #131110;
        --bg-sidebar: #0E0C0B;
        --bg-card: #1B1816;
        --bg-card-hover: #201D1A;
        --bg-nav-active: #0F4A50;
        --text-primary: #F2EEE6;
        --text-secondary: #CFC8BB;
        --text-muted: #A39B8E;
        --border-color: #433E37;

        --md-surface-dim: #0B0A09;
        --md-tertiary-dark: #0E0C0B;
        --md-outline-hairline: rgba(255, 255, 255, 0.08);
        --md-shadow-ambient: 0 4px 16px -2px rgba(0, 0, 0, 0.35);
    }
</style>
