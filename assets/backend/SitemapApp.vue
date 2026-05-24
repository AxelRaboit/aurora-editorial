<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppChart from "@/shared/components/display/AppChart.vue";
import { ExternalLink, RefreshCw, Globe, Layers, FileText, Tags, EyeOff } from "lucide-vue-next";

const props = defineProps({
    stats: { type: Object, required: true },
    invalidatePath: { type: String, required: true },
    sitemapUrl: { type: String, required: true },
});

const { t } = useI18n();
const { formatDateTime } = useDateFormat();
const { loading: refreshing, request } = useRequest();

const stats = ref({ ...props.stats });

const sections = [
    { key: "home", icon: Globe },
    { key: "archives", icon: Layers },
    { key: "posts", icon: FileText },
    { key: "terms", icon: Tags },
];

// Tailwind-friendly palette mirrored as hex for chart.js
const palette = ["#6366F1", "#10B981", "#F59E0B", "#EC4899", "#06B6D4", "#8B5CF6", "#EF4444", "#84CC16"];

function formatBytes(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

const postTypeChart = computed(() => ({
    labels: stats.value.byPostType.map((row) => row.label),
    datasets: [{
        label: t("backend.sitemap.charts.urls"),
        data: stats.value.byPostType.map((row) => row.count),
        backgroundColor: stats.value.byPostType.map((_, i) => palette[i % palette.length]),
        borderRadius: 6,
        borderSkipped: false,
    }],
}));

const postTypeOptions = {
    indexAxis: "y",
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { color: "#9CA3AF", precision: 0 }, grid: { color: "rgba(148, 163, 184, 0.1)" } },
        y: { ticks: { color: "#D1D5DB" }, grid: { display: false } },
    },
};

const localeChart = computed(() => ({
    labels: stats.value.byLocale.map((row) => row.code.toUpperCase()),
    datasets: [{
        data: stats.value.byLocale.map((row) => row.count),
        backgroundColor: stats.value.byLocale.map((_, i) => palette[i % palette.length]),
        borderColor: "rgba(15, 23, 42, 0.5)",
        borderWidth: 2,
    }],
}));

const localeOptions = {
    plugins: { legend: { position: "bottom", labels: { color: "#D1D5DB", font: { size: 12 } } } },
    cutout: "60%",
};

async function regenerate() {
    const result = await request(props.invalidatePath);
    if (!result) return;
    if (result.success) {
        stats.value = result.stats;
        toast.success(t("backend.sitemap.regenerated"));
    } else {
        toast.error(t("shared.common.error"));
    }
}
</script>

<template>
    <div class="space-y-6">
        <div class="bg-surface border border-line rounded-xl p-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-sm text-muted">{{ t("backend.sitemap.total_urls") }}</p>
                    <p class="text-3xl font-bold text-primary mt-1">{{ stats.total }}</p>
                    <div class="text-xs text-muted mt-2 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-0">
                        <span>{{ t("backend.sitemap.size") }} : <span class="text-primary font-medium">{{ formatBytes(stats.sizeBytes) }}</span></span>
                        <span class="hidden sm:inline mx-2 text-line">·</span>
                        <span>{{ t("backend.sitemap.generated_at") }} : <span class="text-primary font-medium">{{ formatDateTime(stats.generatedAt) }}</span></span>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <AppButton
                        variant="secondary"
                        size="md"
                        :href="sitemapUrl"
                        target="_blank"
                        rel="noopener"
                        class="w-full sm:w-auto"
                    >
                        <ExternalLink class="w-4 h-4" :stroke-width="2" />
                        {{ t("backend.sitemap.view_xml") }}
                    </AppButton>
                    <AppButton
                        type="button"
                        variant="primary"
                        size="md"
                        :loading="refreshing"
                        class="w-full sm:w-auto"
                        v-on:click="regenerate"
                    >
                        <RefreshCw class="w-4 h-4" :stroke-width="2" />
                        {{ t("backend.sitemap.regenerate") }}
                    </AppButton>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                v-for="section in sections"
                :key="section.key"
                class="bg-surface border border-line rounded-xl p-4 flex items-center gap-3"
            >
                <div class="shrink-0 w-10 h-10 rounded-lg bg-surface-2 flex items-center justify-center text-accent">
                    <component :is="section.icon" class="w-5 h-5" :stroke-width="2" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-muted">{{ t(`backend.sitemap.sections.${section.key}`) }}</p>
                    <p class="text-xl font-semibold text-primary">{{ stats.counts[section.key] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-surface border border-line rounded-xl p-5 lg:col-span-2">
                <p class="text-sm font-medium text-primary">{{ t("backend.sitemap.charts.by_post_type") }}</p>
                <p class="text-xs text-muted mb-4">{{ t("backend.sitemap.charts.by_post_type_hint") }}</p>
                <div v-if="stats.byPostType.length === 0" class="h-48 flex items-center justify-center text-sm text-muted italic">
                    {{ t("backend.sitemap.charts.empty") }}
                </div>
                <div v-else class="h-64">
                    <AppChart type="bar" :data="postTypeChart" :options="postTypeOptions" />
                </div>
            </div>

            <div class="bg-surface border border-line rounded-xl p-5">
                <p class="text-sm font-medium text-primary">{{ t("backend.sitemap.charts.by_locale") }}</p>
                <p class="text-xs text-muted mb-4">{{ t("backend.sitemap.charts.by_locale_hint") }}</p>
                <div v-if="stats.byLocale.length === 0" class="h-48 flex items-center justify-center text-sm text-muted italic">
                    {{ t("backend.sitemap.charts.empty") }}
                </div>
                <div v-else class="h-64">
                    <AppChart type="doughnut" :data="localeChart" :options="localeOptions" />
                </div>
            </div>
        </div>

        <div class="bg-surface border border-line rounded-xl p-4 flex items-center gap-3">
            <div class="shrink-0 w-10 h-10 rounded-lg bg-surface-2 flex items-center justify-center" :class="stats.noindex > 0 ? 'text-warning' : 'text-muted'">
                <EyeOff class="w-5 h-5" :stroke-width="2" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-muted">{{ t("backend.sitemap.noindex.label") }}</p>
                <p class="text-xl font-semibold text-primary">{{ stats.noindex }}</p>
            </div>
            <p class="text-xs text-muted hidden sm:block max-w-md text-right">{{ t("backend.sitemap.noindex.hint") }}</p>
        </div>

        <div class="bg-surface border border-line rounded-xl p-4 text-xs text-muted leading-relaxed">
            {{ t("backend.sitemap.cache_hint") }}
        </div>
    </div>
</template>
