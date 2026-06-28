import { useEffect } from 'react';
import { startPuff, type PuffOptions } from './puff';

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
    // startPuff returns a stop() that detaches every listener, so returning it
    // straight from the effect makes React run cleanup on unmount.
    useEffect(() => startPuff(options), []);
}
