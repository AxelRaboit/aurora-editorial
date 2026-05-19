<script setup>
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import AppImage from "@/shared/components/display/AppImage.vue";
import { ImageOff } from "lucide-vue-next";

const { formatDateShort } = useDateFormat();

defineProps({
    post: { type: Object, required: true },
    postUrl: { type: String, required: true },
});
</script>

<template>
    <article class="bg-surface border border-line/60 rounded-xl overflow-hidden hover:border-accent transition-colors">
        <a :href="postUrl" class="block">
            <div v-if="post.featuredMediaUrl" class="aspect-video bg-surface-2 overflow-hidden">
                <AppImage
                    :src="post.featuredMediaUrl"
                    :alt="post.title ?? ''"
                    object-fit="cover"
                    loading="lazy"
                    :style="post.featuredMediaFocalPosition ? `object-position: ${post.featuredMediaFocalPosition}` : ''"
                />
            </div>
            <div v-else class="aspect-video bg-surface-2 flex items-center justify-center text-muted">
                <ImageOff class="w-6 h-6 opacity-40" :stroke-width="1.5" />
            </div>
            <div class="p-4 space-y-2">
                <h3 class="text-lg font-semibold text-primary">{{ post.title ?? '—' }}</h3>
                <p v-if="post.metaDescription" class="text-sm text-muted line-clamp-3">{{ post.metaDescription }}</p>
                <time v-if="post.publishedAt" class="block text-xs text-muted">
                    {{ formatDateShort(post.publishedAt) }}
                </time>
            </div>
        </a>
    </article>
</template>
