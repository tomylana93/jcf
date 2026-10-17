<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import {
    computed,
    ref,
    useTemplateRef,
    watchEffect,
    watchPostEffect,
} from 'vue';

import { store } from '@/actions/Laravel/Fortify/Http/Controllers/TwoFactorAuthenticatedSessionController';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup } from '@/components/ui/field';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { useTranslations } from '@/composables/useTranslations';
import type {
    TwoFactorCodeForm,
    TwoFactorConfigContent,
    TwoFactorRecoveryForm,
} from '@/types';

const { t } = useTranslations();

const showRecoveryInput = ref<boolean>(false);

const codeInputContainer = useTemplateRef('codeInputContainer');

const codeForm = useForm<TwoFactorCodeForm>({
    code: '',
});

const recoveryForm = useForm<TwoFactorRecoveryForm>({
    recovery_code: '',
});

const submitCode = () => {
    codeForm.submit(store(), {
        onError: () => codeForm.reset(),
    });
};

const submitRecoveryCode = () => {
    recoveryForm.submit(store(), {
        onError: () => recoveryForm.reset(),
    });
};

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: t('auth.two_factor.challenge.recovery.title'),
            description: t('auth.two_factor.challenge.recovery.description'),
            buttonText: t('auth.two_factor.button.use_code'),
        };
    }

    return {
        title: t('auth.two_factor.challenge.code.title'),
        description: t('auth.two_factor.challenge.code.description'),
        buttonText: t('auth.two_factor.button.use_recovery'),
    };
});

watchEffect(() => {
    setLayoutProps({
        title: authConfigContent.value.title,
        description: authConfigContent.value.description,
    });
});

watchPostEffect(() => {
    codeInputContainer.value?.querySelector('input')?.focus();
});

const toggleRecoveryMode = (): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    codeForm.resetAndClearErrors();
    recoveryForm.resetAndClearErrors();
};
</script>

<template>
    <div>
        <Head :title="t('auth.two_factor.title')" />

        <div class="flex flex-col gap-6">
            <template v-if="!showRecoveryInput">
                <form
                    @submit.prevent="submitCode"
                    class="flex flex-col gap-4"
                >
                    <FieldGroup class="gap-4">
                        <Field
                            :data-invalid="!!codeForm.errors.code"
                            class="items-center text-center"
                        >
                            <div
                                ref="codeInputContainer"
                                class="flex w-full items-center justify-center"
                            >
                                <InputOTP
                                    id="otp"
                                    v-model="codeForm.code"
                                    :aria-label="
                                        t(
                                            'auth.two_factor.challenge.code.title',
                                        )
                                    "
                                    :aria-invalid="!!codeForm.errors.code"
                                    :aria-describedby="
                                        codeForm.errors.code
                                            ? 'code-error'
                                            : undefined
                                    "
                                    :maxlength="6"
                                    :disabled="codeForm.processing"
                                >
                                    <InputOTPGroup>
                                        <InputOTPSlot
                                            v-for="index in 6"
                                            :key="index"
                                            :index="index - 1"
                                        />
                                    </InputOTPGroup>
                                </InputOTP>
                            </div>

                            <FieldError
                                id="code-error"
                                :errors="[codeForm.errors.code]"
                            />
                        </Field>
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="codeForm.processing"
                        >
                            {{ t('common.button.continue') }}
                        </Button>
                    </FieldGroup>

                    <div class="text-center text-sm text-muted-foreground">
                        <span>{{ t('common.helper.or') }}</span>
                        <button
                            type="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            @click="toggleRecoveryMode"
                        >
                            {{ authConfigContent.buttonText }}
                        </button>
                    </div>
                </form>
            </template>

            <template v-else>
                <form
                    @submit.prevent="submitRecoveryCode"
                    class="flex flex-col gap-4"
                >
                    <FieldGroup class="gap-4">
                        <Field
                            :data-invalid="!!recoveryForm.errors.recovery_code"
                        >
                            <InputGroup>
                                <InputGroupInput
                                    id="recovery_code"
                                    name="recovery_code"
                                    v-model="recoveryForm.recovery_code"
                                    type="text"
                                    :aria-label="
                                        t(
                                            'auth.two_factor.challenge.recovery.title',
                                        )
                                    "
                                    :aria-invalid="
                                        !!recoveryForm.errors.recovery_code
                                    "
                                    :aria-describedby="
                                        recoveryForm.errors.recovery_code
                                            ? 'recovery-code-error'
                                            : undefined
                                    "
                                    :placeholder="
                                        t(
                                            'auth.two_factor.placeholder.recovery_code',
                                        )
                                    "
                                    v-focus
                                    required
                                />

                                <InputGroupAddon>
                                    <KeyRound aria-hidden="true" />
                                </InputGroupAddon>
                            </InputGroup>

                            <FieldError
                                id="recovery-code-error"
                                :errors="[recoveryForm.errors.recovery_code]"
                            />
                        </Field>
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="recoveryForm.processing"
                        >
                            {{ t('common.button.continue') }}
                        </Button>
                    </FieldGroup>

                    <div class="text-center text-sm text-muted-foreground">
                        <span>{{ t('common.helper.or') }}</span>
                        <button
                            type="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            @click="toggleRecoveryMode"
                        >
                            {{ authConfigContent.buttonText }}
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</template>
