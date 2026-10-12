<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/shared/Heading.vue';
import InputError from '@/components/shared/InputError.vue';
import PasswordInput from '@/components/shared/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import type { DisableAccountForm } from '@/types';

const { t } = useTranslations();

const passwordInput = useTemplateRef('passwordInput');

const form = useForm<DisableAccountForm>({ password: '' });

const submit = () => {
    form.submit(ProfileController.destroy(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => passwordInput.value?.focus(),
    });
};
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('account.disable_account.card.title')"
            :description="t('account.disable_account.card.description')"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">
                    {{ t('account.disable_account.helper.warning') }}
                </p>
                <p class="text-sm">
                    {{ t('account.disable_account.helper.access_disabled') }}
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                        >{{
                            t('account.disable_account.button.disable')
                        }}</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <form novalidate @submit.prevent="submit" class="space-y-6">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>{{
                                t('account.disable_account.dialog.title')
                            }}</DialogTitle>
                            <DialogDescription>
                                {{
                                    t(
                                        'account.disable_account.dialog.description',
                                    )
                                }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password">{{
                                t('user.label.password')
                            }}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                v-model="form.password"
                                ref="passwordInput"
                                :placeholder="t('user.placeholder.password')"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    type="button"
                                    @click="form.resetAndClearErrors()"
                                >
                                    {{ t('common.button.cancel') }}
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="form.processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{
                                    t('account.disable_account.button.disable')
                                }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
