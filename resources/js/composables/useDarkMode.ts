import { ref, onMounted } from 'vue';

export function useDarkMode() {
  const isDark = ref(false);

  const toggleDarkMode = (event?: MouseEvent) => {
    const isAppearanceTransition =
      // @ts-expect-error - document.startViewTransition is experimental
      document.startViewTransition &&
      !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!isAppearanceTransition || !event) {
      isDark.value = !isDark.value;
      updateTheme();
      return;
    }

    const x = event.clientX;
    const y = event.clientY;
    const endRadius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

    const transition = document.startViewTransition(() => {
      isDark.value = !isDark.value;
      updateTheme();
    });

    transition.ready.then(() => {
      const clipPath = [`circle(0px at ${x}px ${y}px)`, `circle(${endRadius}px at ${x}px ${y}px)`];
      document.documentElement.animate(
        {
          clipPath: isDark.value ? [...clipPath].reverse() : clipPath,
        },
        {
          duration: 650,
          easing: 'cubic-bezier(0.65, 0, 0.35, 1)', // Easing yang jauh lebih halus untuk menyamarkan ketegasan transisi
          pseudoElement: isDark.value
            ? '::view-transition-old(root)'
            : '::view-transition-new(root)',
        },
      );
    });
  };

  const updateTheme = () => {
    if (isDark.value) {
      document.documentElement.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    }
  };

  onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
      isDark.value = true;
      document.documentElement.classList.add('dark');
    } else {
      isDark.value = false;
      document.documentElement.classList.remove('dark');
    }
  });

  return {
    isDark,
    toggleDarkMode,
  };
}
