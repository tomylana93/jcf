import type { BrandSettings } from '@/types';

/**
 * Applies the brand color and favicon to the document, keeping a single icon link.
 */
export function applyBrand(brand: BrandSettings | undefined): void {
    document.documentElement.dataset.brandColor = brand?.color ?? 'neutral';

    const icons = Array.from(
        document.querySelectorAll<HTMLLinkElement>('link[rel="icon"]'),
    );
    const icon = icons.shift() ?? document.createElement('link');
    icons.forEach((duplicate) => duplicate.remove());
    icon.rel = 'icon';
    icon.href = brand?.favicon_url ?? '/favicon.svg';
    icon.removeAttribute('sizes');
    icon.removeAttribute('type');

    if (!icon.isConnected) {
        document.head.appendChild(icon);
    }
}
