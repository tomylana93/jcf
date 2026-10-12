import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { formatDateTime } from '@/lib/dateTime';
import type { TimezoneSettings } from '@/types';

export function useDateTime() {
    const page = usePage<{ timezone: TimezoneSettings }>();

    const { locale } = useTranslations();

    const timezone = computed(() => page.props.timezone.current);

    return {
        timezone,
        formatDateTime: (timestamp: string) =>
            formatDateTime(timestamp, locale.value, timezone.value),
    };
}
