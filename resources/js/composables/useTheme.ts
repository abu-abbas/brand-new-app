import { ref, computed, onMounted } from 'vue';
import { useAppBootstrapStore } from '@/stores/app-bootstrap';
import componentsConfig from '../../../components.json';

export const themes = [
  { name: 'amber' as const, label: 'Amber', color: '#f59e0b' },
  { name: 'blue' as const, label: 'Blue', color: '#3b82f6' },
  { name: 'cyan' as const, label: 'Cyan', color: '#06b6d4' },
  { name: 'emerald' as const, label: 'Emerald', color: '#10b981' },
  { name: 'fuchsia' as const, label: 'Fuchsia', color: '#d946ef' },
  { name: 'green' as const, label: 'Green', color: '#22c55e' },
  { name: 'indigo' as const, label: 'Indigo', color: '#6366f1' },
  { name: 'lime' as const, label: 'Lime', color: '#84cc16' },
  { name: 'neutral' as const, label: 'Neutral', color: '#737373' },
  { name: 'orange' as const, label: 'Orange', color: '#f97316' },
  { name: 'pink' as const, label: 'Pink', color: '#ec4899' },
  { name: 'purple' as const, label: 'Purple', color: '#a855f7' },
  { name: 'red' as const, label: 'Red', color: '#ef4444' },
  { name: 'rose' as const, label: 'Rose', color: '#f43f5e' },
  { name: 'sky' as const, label: 'Sky', color: '#0ea5e9' },
  { name: 'teal' as const, label: 'Teal', color: '#14b8a6' },
  { name: 'violet' as const, label: 'Violet', color: '#8b5cf6' },
  { name: 'yellow' as const, label: 'Yellow', color: '#eab308' },
];

export type ThemeName = (typeof themes)[number]['name'];
export type ThemeMeta = (typeof themes)[number];

const defaultBaseTheme = (componentsConfig.tailwind?.baseColor as ThemeName) ?? 'neutral';

const isValidTheme = (name: string | null | undefined): name is ThemeName =>
  themes.some((t) => t.name === name);

export function useTheme() {
  const bootstrapStore = useAppBootstrapStore();
  const activeTheme = ref<ThemeName>(defaultBaseTheme);

  const setTheme = (theme: ThemeName) => {
    themes.forEach((t) => {
      document.documentElement.classList.remove(`theme-${t.name}`);
    });

    document.documentElement.classList.add(`theme-${theme}`);
    activeTheme.value = theme;
    localStorage.setItem('theme-accent', theme);
  };

  const activeThemeMeta = computed(
    () => themes.find((t) => t.name === activeTheme.value) ?? themes[0],
  );

  const otherThemes = computed(() => themes.filter((t) => t.name !== activeTheme.value));

  const activeThemeLabel = computed(() => `${activeThemeMeta.value.label} (Aktif)`);

  const activeThemeColor = computed(() => activeThemeMeta.value.color);

  onMounted(() => {
    const savedTheme = localStorage.getItem('theme-accent');
    const backendDefault = bootstrapStore.config.theme_accent;

    if (isValidTheme(savedTheme)) {
      setTheme(savedTheme);
    } else if (isValidTheme(backendDefault)) {
      setTheme(backendDefault);
    } else {
      setTheme(defaultBaseTheme);
    }
  });

  return {
    activeTheme,
    setTheme,
    otherThemes,
    activeThemeLabel,
    activeThemeColor,
  };
}
