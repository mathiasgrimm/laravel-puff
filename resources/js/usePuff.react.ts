import { useEffect } from 'react';
import { startPuff } from './puff';
import type { PuffOptions } from './puff';

/**
 * React adapter for Puff. Call once in your root/authenticated layout:
 *
 *   import { usePuff } from '@/laravel-puff/usePuff';
 *   import { usePage } from '@inertiajs/react';
 *
 *   usePuff({ isEnabled: () => !!usePage().props.auth?.user });
 *
 * Starts warming on mount and detaches every listener on unmount.
 */
export function usePuff(options: PuffOptions = {}): void {
    // Run once on mount and stop on unmount. Like the Vue adapter, options are
    // read once at start (isEnabled is re-read on every event), so later changes
    // are intentionally ignored, hence the empty dependency array.
    // eslint-disable-next-line react-hooks/exhaustive-deps
    useEffect(() => startPuff(options), []);
}
