export type PuffOptions = {
    /** Endpoint to POST to. Defaults to `/puff`. */
    url?: string;
    /** Minimum gap between requests, in milliseconds. Defaults to 30_000. */
    intervalMs?: number;
    /** Activity events that trigger a warm. */
    events?: string[];
    /** HTTP method. Defaults to `POST`. */
    method?: string;
    /**
     * Also warm when the user returns to this tab, i.e. when the page becomes
     * visible again (the `visibilitychange` event). That is often when the stack
     * is most likely to have scaled to zero, so warming then is a good moment.
     * Set to `false` to listen to the `events` above only. Defaults to `true`.
     */
    warmOnVisible?: boolean;
    /** Return false to skip warming (e.g. for guests). Defaults to always-on. */
    isEnabled?: () => boolean;
};

const DEFAULT_EVENTS = ['mousemove', 'keydown', 'scroll', 'touchstart'];

function readCookie(name: string): string | null {
    const escaped = name.replace(/([.*+?^${}()|[\]\\])/g, '\\$1');
    const match = document.cookie.match(new RegExp('(?:^|; )' + escaped + '=([^;]*)'));

    return match ? decodeURIComponent(match[1]) : null;
}

/**
 * Warm a scale-to-zero stack ahead of user interaction.
 *
 * When the user shows intent to act (moving the mouse, typing, scrolling,
 * touching the screen, or returning to the tab), fire a fire-and-forget request
 * to the keep-alive endpoint, throttled to at most one request per `intervalMs`.
 * Every signal funnels through the same throttle, so adding signals widens
 * coverage without ever raising the request rate.
 *
 * Returns a `stop()` function that detaches every listener.
 */
export function startPuff(options: PuffOptions = {}): () => void {
    const url = options.url ?? '/puff';
    const intervalMs = options.intervalMs ?? 30_000;
    const events = options.events ?? DEFAULT_EVENTS;
    const method = options.method ?? 'POST';
    const warmOnVisible = options.warmOnVisible ?? true;
    const isEnabled = options.isEnabled ?? (() => true);

    let lastSentAt = 0;

    const send = (): void => {
        if (!isEnabled()) {
            return;
        }

        const now = Date.now();

        if (now - lastSentAt < intervalMs) {
            return;
        }

        lastSentAt = now;

        const headers: Record<string, string> = { Accept: 'application/json' };
        const token = readCookie('XSRF-TOKEN');

        if (token) {
            headers['X-XSRF-TOKEN'] = token;
        }

        void fetch(url, {
            method,
            credentials: 'same-origin',
            keepalive: true,
            headers,
        }).catch(() => {});
    };

    for (const event of events) {
        window.addEventListener(event, send, { passive: true });
    }

    // Returning to the tab after being away is the moment the stack is most
    // likely to have scaled to zero, so warm it as soon as it becomes visible.
    const onVisibility = (): void => {
        if (document.visibilityState === 'visible') {
            send();
        }
    };

    if (warmOnVisible) {
        document.addEventListener('visibilitychange', onVisibility);
    }

    return (): void => {
        for (const event of events) {
            window.removeEventListener(event, send);
        }

        if (warmOnVisible) {
            document.removeEventListener('visibilitychange', onVisibility);
        }
    };
}
