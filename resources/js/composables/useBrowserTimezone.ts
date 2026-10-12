import { router, usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import type { TimezoneSettings } from '@/types';

export function useBrowserTimezone(): void {
    const page = usePage<{ timezone: TimezoneSettings }>();

    onMounted(() => {
        if (page.props.timezone.source === 'profile') {
            return;
        }

        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        document.cookie = `timezone=${encodeURIComponent(timezone)};path=/;max-age=${365 * 24 * 60 * 60};SameSite=Lax`;

        if (timezone !== page.props.timezone.current) {
            router.reload({ only: ['timezone'], preserveErrors: true });
        }
    });
}
