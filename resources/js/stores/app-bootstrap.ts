import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useAppBootstrapStore = defineStore('app-bootstrap', () => {
  const config = ref<AppConfig>(
    window.__APP_CONFIG__ || {
      name: 'Laravel',
      theme_accent: 'neutral',
      env: 'production',
      url: '',
      timezone: 'UTC',
      locale: 'en',
      current_date: new Date().toISOString().split('T')[0],
      current_fulldate: new Date().toISOString(),
      captcha: {
        enabled: false,
      },
      references: {
        permission_types: [],
      },
    },
  );

  // Singleton Server Clock (1 Timer Tunggal untuk seluruh aplikasi)
  let initialServerTime = Date.now();
  if (config.value.current_fulldate) {
    const parsed = new Date(config.value.current_fulldate).getTime();
    if (!isNaN(parsed)) {
      initialServerTime = parsed;
    }
  }

  const timeOffset = initialServerTime - Date.now();
  const now = ref<Date>(new Date(Date.now() + timeOffset));

  let timerId: ReturnType<typeof setInterval> | null = null;

  function startClock() {
    if (timerId) return;
    timerId = setInterval(() => {
      now.value = new Date(Date.now() + timeOffset);
    }, 1000);
  }

  function stopClock() {
    if (timerId) {
      clearInterval(timerId);
      timerId = null;
    }
  }

  // Mulai timer 1 kali secara otomatis untuk seluruh aplikasi
  startClock();

  const formattedTime = computed(() => {
    const h = String(now.value.getHours()).padStart(2, '0');
    const m = String(now.value.getMinutes()).padStart(2, '0');
    const s = String(now.value.getSeconds()).padStart(2, '0');
    return `${h}:${m}:${s}`;
  });

  const formattedDate = computed(() => {
    const y = now.value.getFullYear();
    const m = String(now.value.getMonth() + 1).padStart(2, '0');
    const d = String(now.value.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  });

  const formattedDateTime = computed(() => `${formattedDate.value} ${formattedTime.value}`);

  return {
    config,
    now,
    formattedTime,
    formattedDate,
    formattedDateTime,
    startClock,
    stopClock,
  };
});
