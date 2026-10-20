<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import type { ChatUser } from '@/types/chat';
defineProps<{ user: ChatUser }>();
</script>
<template>
    <Avatar class="size-9 shrink-0">
        <AvatarImage
            v-if="user.avatar"
            :src="user.avatar"
            alt=""
        />
        <AvatarFallback>{{ getInitials(user.name) }}</AvatarFallback>
    </Avatar>
</template>
