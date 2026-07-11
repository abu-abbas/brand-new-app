import { ref, onMounted } from 'vue';

export const themeList = [
  'default', // Sky
  'amber',
  'blue',
  'cyan',
  'emerald',
  'fuchsia',
  'green',
  'indigo',
  'lime',
  'neutral',
  'orange',
  'pink',
  'purple',
  'red',
  'rose',
  'teal',
  'violet',
  'yellow',
] as const;

export type ThemeName = (typeof themeList)[number];

export function useTheme() {
  const activeTheme = ref<ThemeName>('default');

  const setTheme = (theme: ThemeName) => {
    // Hapus semua kelas tema lama
    themeList.forEach((t) => {
      if (t !== 'default') {
        document.documentElement.classList.remove(`theme-${t}`);
      }
    });

    // Tambah kelas tema baru jika bukan default (default adalah Sky)
    if (theme !== 'default') {
      document.documentElement.classList.add(`theme-${theme}`);
    }

    activeTheme.value = theme;
    localStorage.setItem('active-theme', theme);
  };

  onMounted(() => {
    const savedTheme = localStorage.getItem('active-theme') as ThemeName | null;
    if (savedTheme && themeList.includes(savedTheme)) {
      setTheme(savedTheme);
    }
  });

  return {
    activeTheme,
    setTheme,
  };
}
