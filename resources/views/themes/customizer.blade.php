@extends('layouts.app')

@section('title', 'Theme Customizer')

@section('header')
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold" style="color: var(--theme-text);">🎨 Theme Customizer</h1>
        <a href="{{ route('sprints.index') }}" class="tdb-link text-sm">&larr; Back to Sprints</a>
    </div>
@endsection

@section('content')
<div x-data="themeCustomizer()" x-init="init()" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- ═══════════════════════════════════════════ -->
    <!-- LEFT: Controls Panel                        -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="lg:col-span-1 space-y-6">

        <!-- Preset Themes -->
        <div class="tdb-card tdb-shadow p-5" style="background: var(--theme-card-bg); border: 1px solid var(--theme-card-border);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--theme-text);">Presets</h2>
            <div class="grid grid-cols-1 gap-3">
                <template x-for="(preset, key) in presets" :key="key">
                    <button @click="selectPreset(key)"
                            :class="activeTheme === key ? 'ring-2 ring-offset-2' : 'hover:scale-[1.02]'"
                            :style="activeTheme === key ? 'ring-color: var(--theme-primary);' : ''"
                            class="relative flex items-center gap-3 p-3 rounded-lg border transition-all duration-200 text-left"
                            :style="`border-color: ${activeTheme === key ? 'var(--theme-primary)' : 'var(--theme-card-border)'}; background: var(--theme-surface);`">
                        <!-- Color swatch -->
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center"
                             :style="`background: ${preset.vars['--theme-primary']}; color: ${preset.vars['--theme-accent-text']};`">
                            <span class="text-lg" x-text="activeTheme === key ? '✓' : key.charAt(0).toUpperCase()"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-semibold text-sm" style="color: var(--theme-text);" x-text="preset.label"></div>
                            <div class="text-xs truncate" style="color: var(--theme-text-muted);" x-text="preset.description"></div>
                        </div>
                        <!-- Color strip preview -->
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex gap-0.5">
                            <div class="w-2 h-6 rounded-sm" :style="`background: ${preset.vars['--theme-primary']}`"></div>
                            <div class="w-2 h-6 rounded-sm" :style="`background: ${preset.vars['--theme-accent']}`"></div>
                            <div class="w-2 h-6 rounded-sm" :style="`background: ${preset.vars['--theme-topbar-bg']}`"></div>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        <!-- Color Customization -->
        <div class="tdb-card tdb-shadow p-5" style="background: var(--theme-card-bg); border: 1px solid var(--theme-card-border);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--theme-text);">Customize Colors</h2>
            <div class="space-y-3">
                <template x-for="token in colorTokens" :key="token.key">
                    <div class="flex items-center justify-between gap-3">
                        <label class="text-sm flex-1 min-w-0 truncate" style="color: var(--theme-text-muted);" x-text="token.label"></label>
                        <div class="flex items-center gap-2">
                            <input type="color"
                                   :value="currentVars[token.key] || '#000000'"
                                   @input="updateVar(token.key, $event.target.value)"
                                   class="w-8 h-8 rounded cursor-pointer border-0 p-0">
                            <span class="text-xs font-mono w-16 text-right" style="color: var(--theme-text-muted);"
                                  x-text="currentVars[token.key] || ''"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Font & Shape -->
        <div class="tdb-card tdb-shadow p-5" style="background: var(--theme-card-bg); border: 1px solid var(--theme-card-border);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--theme-text);">Font & Shape</h2>

            <!-- Font selector -->
            <div class="mb-4">
                <label class="block text-sm mb-2" style="color: var(--theme-text-muted);">Font Family</label>
                <select @change="updateVar('--theme-font', $event.target.value)"
                        class="tdb-input w-full rounded-md px-3 py-2 text-sm">
                    <option value="'Figtree', ui-sans-serif, system-ui, sans-serif" :selected="currentFont === 'Figtree'">Figtree (Default)</option>
                    <option value="'Inter', ui-sans-serif, system-ui, sans-serif" :selected="currentFont === 'Inter'">Inter</option>
                    <option value="'Outfit', ui-sans-serif, system-ui, sans-serif" :selected="currentFont === 'Outfit'">Outfit</option>
                    <option value="'JetBrains Mono', 'Fira Code', monospace" :selected="currentFont === 'JetBrains Mono'">JetBrains Mono</option>
                    <option value="'Fira Code', monospace" :selected="currentFont === 'Fira Code'">Fira Code</option>
                    <option value="ui-sans-serif, system-ui, sans-serif" :selected="currentFont === 'system'">System Default</option>
                </select>
            </div>

            <!-- Border radius slider -->
            <div>
                <label class="block text-sm mb-2" style="color: var(--theme-text-muted);">
                    Border Radius: <span class="font-mono" x-text="currentRadius"></span>
                </label>
                <input type="range" min="0" max="1.5" step="0.125"
                       :value="parseFloat(currentRadius)"
                       @input="updateVar('--theme-radius', $event.target.value + 'rem')"
                       class="w-full accent-current" style="accent-color: var(--theme-primary);">
                <div class="flex justify-between text-xs mt-1" style="color: var(--theme-text-muted);">
                    <span>Sharp</span>
                    <span>Rounded</span>
                    <span>Pill</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="tdb-card tdb-shadow p-5" style="background: var(--theme-card-bg); border: 1px solid var(--theme-card-border);">
            <h2 class="text-lg font-semibold mb-4" style="color: var(--theme-text);">Actions</h2>
            <div class="grid grid-cols-2 gap-3">
                <button @click="resetToPreset()" class="tdb-btn-primary px-4 py-2 text-sm font-medium rounded-md">
                    ↺ Reset
                </button>
                <button @click="exportTheme()" class="px-4 py-2 text-sm font-medium rounded-md border transition-colors"
                        style="border-color: var(--theme-card-border); color: var(--theme-text); background: var(--theme-surface);">
                    📥 Export JSON
                </button>
                <label class="col-span-2 cursor-pointer">
                    <div class="px-4 py-2 text-sm font-medium rounded-md border text-center transition-colors hover:opacity-80"
                         style="border-color: var(--theme-card-border); color: var(--theme-text); background: var(--theme-surface);">
                        📤 Import JSON
                    </div>
                    <input type="file" accept=".json" @change="importTheme($event)" class="hidden">
                </label>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- RIGHT: Live Preview Panel                   -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="lg:col-span-2">
        <div class="tdb-card tdb-shadow overflow-hidden" style="background: var(--theme-card-bg); border: 1px solid var(--theme-card-border);">
            <!-- Preview Header -->
            <div class="px-5 py-3 border-b flex items-center justify-between" style="border-color: var(--theme-card-border);">
                <span class="text-sm font-semibold" style="color: var(--theme-text);">Live Preview</span>
                <span class="text-xs px-2 py-0.5 rounded-full" style="background: var(--theme-primary); color: var(--theme-accent-text);">
                    <span x-text="activeTheme === 'custom' ? 'Custom' : presets[activeTheme]?.label || 'Default'"></span>
                </span>
            </div>

            <!-- Mini App Preview -->
            <div class="p-0" style="background: var(--theme-bg);">

                <!-- Preview: Top Bar -->
                <div class="px-4 py-1.5 flex items-center justify-between text-[10px]" style="background: var(--theme-topbar-bg); color: var(--theme-topbar-text);">
                    <span class="font-semibold opacity-70">⚡ TheDevBacklog</span>
                    <div class="flex items-center gap-2">
                        <span>🎨 Themes</span>
                        <span class="opacity-30">|</span>
                        <span>☀️ Light</span>
                    </div>
                </div>

                <!-- Preview: Nav -->
                <div class="px-4 py-3 border-b flex items-center gap-6" style="background: var(--theme-nav-bg); border-color: var(--theme-nav-border);">
                    <span class="font-bold text-sm" style="color: var(--theme-primary);">🛠️ TheDevBacklog</span>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="pb-1 border-b-2" style="color: var(--theme-text); border-color: var(--theme-primary);">Sprints</span>
                        <span style="color: var(--theme-text-muted);">Projects</span>
                        <span style="color: var(--theme-text-muted);">Backlog</span>
                    </div>
                </div>

                <!-- Preview: Page Header -->
                <div class="px-4 py-3" style="background: var(--theme-surface);">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-sm" style="color: var(--theme-text);">Sprints</h3>
                        <button class="text-[10px] font-semibold px-3 py-1.5 rounded" style="background: var(--theme-btn-bg); color: var(--theme-accent-text); border-radius: var(--theme-radius);">
                            + NEW SPRINT
                        </button>
                    </div>
                </div>

                <!-- Preview: Cards Grid -->
                <div class="p-4 grid grid-cols-2 gap-3" style="background: var(--theme-bg);">
                    <!-- Card 1 -->
                    <div class="p-3 border-l-4" style="background: var(--theme-card-bg); border-color: var(--theme-primary); border-radius: var(--theme-radius); box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div class="flex items-start justify-between mb-2">
                            <span class="font-semibold text-xs" style="color: var(--theme-text);">Phase 1 Sprint</span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full"
                                  style="background: color-mix(in srgb, var(--theme-primary) 15%, var(--theme-card-bg)); color: var(--theme-primary);">
                                Active
                            </span>
                        </div>
                        <p class="text-[10px] mb-2" style="color: var(--theme-text-muted);">Workers can claim and complete task assignments...</p>
                        <div class="flex items-center gap-3 text-[10px]" style="color: var(--theme-text-muted);">
                            <span>📋 6 stories</span>
                            <span>⏱️ 34 pts</span>
                        </div>
                        <div class="mt-2 pt-2 border-t flex justify-end gap-2 text-[10px]" style="border-color: var(--theme-card-border);">
                            <span style="color: var(--theme-primary);">View</span>
                            <span style="color: var(--theme-text-muted);">Edit</span>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="p-3 border-l-4" style="background: var(--theme-card-bg); border-color: var(--theme-accent); border-radius: var(--theme-radius); box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div class="flex items-start justify-between mb-2">
                            <span class="font-semibold text-xs" style="color: var(--theme-text);">Phase 2 Sprint</span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full"
                                  style="background: color-mix(in srgb, var(--theme-accent) 15%, var(--theme-card-bg)); color: var(--theme-accent);">
                                Ready
                            </span>
                        </div>
                        <p class="text-[10px] mb-2" style="color: var(--theme-text-muted);">Enhance worker autonomy with decision-making...</p>
                        <div class="flex items-center gap-3 text-[10px]" style="color: var(--theme-text-muted);">
                            <span>📋 9 stories</span>
                            <span>⏱️ 76 pts</span>
                        </div>
                        <div class="mt-2 pt-2 border-t flex justify-end gap-2 text-[10px]" style="border-color: var(--theme-card-border);">
                            <span style="color: var(--theme-primary);">View</span>
                            <span style="color: var(--theme-text-muted);">Edit</span>
                        </div>
                    </div>

                    <!-- Card 3: Form Input Preview -->
                    <div class="col-span-2 p-3" style="background: var(--theme-card-bg); border: 1px solid var(--theme-card-border); border-radius: var(--theme-radius); box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <span class="text-xs font-semibold block mb-2" style="color: var(--theme-text);">Form Preview</span>
                        <div class="flex items-center gap-2">
                            <input type="text" value="Sprint Title..." readonly
                                   class="flex-1 text-xs px-2 py-1.5"
                                   style="background: var(--theme-input-bg); border: 1px solid var(--theme-input-border); color: var(--theme-text); border-radius: var(--theme-radius);">
                            <button class="text-[10px] font-semibold px-3 py-1.5"
                                    style="background: var(--theme-btn-bg); color: var(--theme-accent-text); border-radius: var(--theme-radius);">
                                Save
                            </button>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <span class="text-[9px] px-2 py-0.5 rounded-full" style="background: var(--theme-primary); color: var(--theme-accent-text);">Badge</span>
                            <span class="text-[9px] px-2 py-0.5 rounded-full" style="background: var(--theme-accent); color: var(--theme-accent-text);">Accent</span>
                            <span class="text-[9px] px-2 py-0.5 rounded-full" style="border: 1px solid var(--theme-card-border); color: var(--theme-text-muted);">Outlined</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Info -->
        <div class="mt-4 p-4 rounded-lg text-sm" style="background: var(--theme-surface); border: 1px solid var(--theme-card-border); color: var(--theme-text-muted); border-radius: var(--theme-radius);">
            <p><strong style="color: var(--theme-text);">💡 How it works:</strong> Themes are saved in your browser's localStorage and apply instantly across the entire app. No server-side changes needed.</p>
            <p class="mt-1">Use the color pickers to customize any preset, then export your theme as JSON to share or back up.</p>
        </div>
    </div>
</div>

<script>
function themeCustomizer() {
    return {
        presets: {},
        activeTheme: 'default',
        currentVars: {},
        currentFont: 'Figtree',
        currentRadius: '0.5rem',

        colorTokens: [
            { key: '--theme-primary',       label: 'Primary' },
            { key: '--theme-primary-hover',  label: 'Primary Hover' },
            { key: '--theme-accent',         label: 'Accent' },
            { key: '--theme-bg',             label: 'Background (Light)' },
            { key: '--theme-bg-dark',        label: 'Background (Dark)' },
            { key: '--theme-surface',        label: 'Surface (Light)' },
            { key: '--theme-surface-dark',   label: 'Surface (Dark)' },
            { key: '--theme-nav-bg',         label: 'Nav Background (Light)' },
            { key: '--theme-nav-bg-dark',    label: 'Nav Background (Dark)' },
            { key: '--theme-nav-border',     label: 'Nav Border' },
            { key: '--theme-card-bg',        label: 'Card Background (Light)' },
            { key: '--theme-card-bg-dark',   label: 'Card Background (Dark)' },
            { key: '--theme-card-border',    label: 'Card Border' },
            { key: '--theme-text',           label: 'Text (Light)' },
            { key: '--theme-text-dark',      label: 'Text (Dark)' },
            { key: '--theme-text-muted',     label: 'Muted Text' },
            { key: '--theme-btn-bg',         label: 'Button Background' },
            { key: '--theme-btn-bg-hover',   label: 'Button Hover' },
            { key: '--theme-topbar-bg',      label: 'Top Bar Background' },
            { key: '--theme-topbar-text',    label: 'Top Bar Text' },
            { key: '--theme-input-bg',       label: 'Input Background' },
            { key: '--theme-input-border',   label: 'Input Border' },
        ],

        init() {
            this.presets = ThemeEngine.getPresets();
            this.activeTheme = ThemeEngine.getActive();
            this.currentVars = ThemeEngine.getCurrentVars();
            this.currentRadius = this.currentVars['--theme-radius'] || '0.5rem';
            this.detectFont();

            // React to external theme changes
            window.addEventListener('theme-changed', (e) => {
                this.activeTheme = e.detail.name;
                this.currentVars = ThemeEngine.getCurrentVars();
                this.currentRadius = this.currentVars['--theme-radius'] || '0.5rem';
                this.detectFont();
            });
        },

        detectFont() {
            const font = this.currentVars['--theme-font'] || '';
            if (font.includes('JetBrains')) this.currentFont = 'JetBrains Mono';
            else if (font.includes('Fira')) this.currentFont = 'Fira Code';
            else if (font.includes('Outfit')) this.currentFont = 'Outfit';
            else if (font.includes('Inter')) this.currentFont = 'Inter';
            else if (font.includes('Figtree')) this.currentFont = 'Figtree';
            else this.currentFont = 'system';
        },

        selectPreset(key) {
            ThemeEngine.apply(key);
            this.activeTheme = key;
            this.currentVars = ThemeEngine.getCurrentVars();
            this.currentRadius = this.currentVars['--theme-radius'] || '0.5rem';
            this.detectFont();
        },

        updateVar(key, value) {
            // Clone current vars, update the one that changed, save as custom
            const vars = { ...this.currentVars };
            vars[key] = value;
            this.currentVars = vars;

            if (key === '--theme-radius') {
                this.currentRadius = value;
            }
            if (key === '--theme-font') {
                this.detectFont();
            }

            ThemeEngine.saveCustom(vars);
            this.activeTheme = 'custom';
        },

        resetToPreset() {
            const base = this.activeTheme === 'custom' ? 'default' : this.activeTheme;
            this.selectPreset(base);
        },

        exportTheme() {
            const data = ThemeEngine.exportTheme();
            const blob = new Blob([data], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `tdb-theme-${this.activeTheme}.json`;
            a.click();
            URL.revokeObjectURL(url);
        },

        importTheme(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const success = ThemeEngine.importTheme(e.target.result);
                if (success) {
                    this.activeTheme = 'custom';
                    this.currentVars = ThemeEngine.getCurrentVars();
                    this.currentRadius = this.currentVars['--theme-radius'] || '0.5rem';
                    this.detectFont();
                }
            };
            reader.readAsText(file);
            event.target.value = '';
        }
    };
}
</script>
@endsection
