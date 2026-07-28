import { computed } from 'vue';
import { getActivePinia } from 'pinia';
import { useAppBootstrapStore } from '@/stores/app-bootstrap';

/**
 * Composable global untuk membaca jam/waktu server secara real-time.
 * Menggunakan Singleton State dari Pinia app-bootstrap store (1 timer tunggal).
 */
export function useServerClock() {
  let store: ReturnType<typeof useAppBootstrapStore> | null = null;
  try {
    if (getActivePinia()) {
      store = useAppBootstrapStore();
    }
  } catch {
    store = null;
  }

  const now = computed(() => store?.now || new Date());
  const formattedTime = computed(() => store?.formattedTime || '00:00:00');
  const formattedDate = computed(
    () => store?.formattedDate || new Date().toISOString().split('T')[0],
  );
  const formattedDateTime = computed(
    () => store?.formattedDateTime || `${formattedDate.value} ${formattedTime.value}`,
  );

  return {
    now,
    formattedTime,
    formattedDate,
    formattedDateTime,
    startClock: () => store?.startClock(),
    stopClock: () => store?.stopClock(),
  };
}
