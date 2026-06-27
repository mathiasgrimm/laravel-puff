import { onMounted, onUnmounted } from 'vue';
import { startPuff, type PuffOptions } from './puff';

/**
 * Vue adapter for Puff. Call once in your root/authenticated layout's
 * `<script setup>`:
 *
 *   import { usePuff } from '@/laravel-puff/usePuff';
 *   import { usePage } from '@inertiajs/vue3';
 *
 *   usePuff({ isEnabled: () => !!usePage().props.auth?.user });
 *
 * Starts warming on mount and detaches every listener on unmount.
 */
export function usePuff(options: PuffOptions = {}): void {
    let stop: (() => void) | undefined;

    onMounted(() => {
        stop = startPuff(options);
    });

    onUnmounted(() => {
        stop?.();
    });
}
