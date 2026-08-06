import { ref, computed, onMounted } from 'vue';
import { useAppBootstrapStore } from '@/stores/app-bootstrap';
import componentsConfig from '../../../components.json';

export const themes = [
  { name: 'amber' as const, label: 'Amber', color: '#f1ab34' },
  { name: 'blue' as const, label: 'Blue', color: '#0069a8' },
  { name: 'cyan' as const, label: 'Cyan', color: '#00a9bb' },
  { name: 'emerald' as const, label: 'Emerald', color: '#00a63e' },
  { name: 'fuchsia' as const, label: 'Fuchsia', color: '#b74edd' },
  { name: 'green' as const, label: 'Green', color: '#5aa20d' },
  { name: 'indigo' as const, label: 'Indigo', color: '#1e5fcb' },
  { name: 'lime' as const, label: 'Lime', color: '#b8c700' },
  { name: 'neutral' as const, label: 'Neutral', color: '#0a0a0a' },
  { name: 'orange' as const, label: 'Orange', color: '#e85e00' },
  { name: 'pink' as const, label: 'Pink', color: '#d34bad' },
  { name: 'purple' as const, label: 'Purple', color: '#6c64e6' },
  { name: 'red' as const, label: 'Red', color: '#e7000b' },
  { name: 'rose' as const, label: 'Rose', color: '#e7458f' },
  { name: 'sky' as const, label: 'Sky', color: '#0084d1' },
  { name: 'teal' as const, label: 'Teal', color: '#009c7f' },
  { name: 'violet' as const, label: 'Violet', color: '#4259d4' },
  { name: 'yellow' as const, label: 'Yellow', color: '#f8c718' },
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
