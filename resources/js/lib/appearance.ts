import type { Appearance } from '@/types';

const colorSchemeQuery = '(prefers-color-scheme: dark)';

export function prefersDark(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia(colorSchemeQuery).matches;
}

export function getStoredAppearance(): Appearance | null {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
}

/**
 * Persists the appearance in localStorage for the client and in a cookie for SSR.
 */
export function storeAppearance(value: Appearance, days = 365): void {
    if (typeof document === 'undefined') {
        return;
    }

    localStorage.setItem('appearance', value);
    document.cookie = `appearance=${value};path=/;max-age=${days * 24 * 60 * 60};SameSite=Lax`;
}

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    const isDark = value === 'system' ? prefersDark() : value === 'dark';

    document.documentElement.classList.toggle('dark', isDark);
}

/**
 * Applies the saved appearance and follows system color scheme changes.
 */
export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    updateTheme(getStoredAppearance() ?? 'system');

    window
        .matchMedia(colorSchemeQuery)
        .addEventListener('change', () =>
            updateTheme(getStoredAppearance() ?? 'system'),
        );
}
