<script setup>
import { useI18n } from "vue-i18n";
import { useUrlPagination } from "@/shared/composables/nav/useUrlPagination.js";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import PostCard from "./PostCard.vue";

const { t } = useI18n();
const { goToPage } = useUrlPagination();

const props = defineProps({
    taxonomy: { type: Object, required: true },
    term: { type: Object, required: true },
    posts: {
        type: Object,
        default: () => ({ items: [], page: 1, totalPages: 1, total: 0 }),
    },
    locale: { type: String, default: "fr" },
    postPathTemplate: { type: String, required: true },
});

function postUrl(post) {
    return props.postPathTemplate
        .replace("__postTypeSlug__", post.postTypeSlug)
        .replace("__slug__", post.slug);
}
</script>

<template>
    <section>
        <p class="text-sm text-muted uppercase tracking-wide">{{ taxonomy.slug }}</p>
        <h1 class="text-3xl font-bold mb-6">{{ term.translation?.name }}</h1>
        <p v-if="term.translation?.description" class="text-secondary mb-6">
            {{ term.translation.description }}
        </p>

        <AppNoData v-if="!posts.items.length" :message="t('frontend.theme.no_content')" />

        <template v-else>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <PostCard
                    v-for="post in posts.items"
                    :key="post.id"
                    :post="post"
                    :post-url="postUrl(post)"
                />
            </div>

            <div v-if="posts.totalPages > 1" class="mt-8">
                <AppPagination
                    :page="posts.page"
                    :total-pages="posts.totalPages"
                    v-on:change="goToPage"
                />
            </div>
        </template>
    </section>
</template>
