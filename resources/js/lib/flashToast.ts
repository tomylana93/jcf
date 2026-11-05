import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

import type { FlashToast } from '@/types';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const data = event.detail.flash.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });
}
