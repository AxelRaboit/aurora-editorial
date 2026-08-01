<script setup>
/**
 * "Provided by Aurora, you can't delete it" — the single rendering of that idea
 * across the admin.
 *
 * It used to be spelled three different ways: a gray badge with a lock on menu
 * entries, a bare muted lock with only a tooltip in the post type and taxonomy
 * lists, and an amber badge in their detail panels. Amber was the worst of it —
 * these same screens use amber for warnings (a broken target, a restricted
 * visibility), so a neutral fact was reading as something to fix.
 *
 * Gray, because this is information rather than an alert. Always with the lock,
 * always with the word, so the signal is recognisable at a glance wherever it
 * appears. A component rather than three copies, so it cannot drift again.
 */
import { useI18n } from "vue-i18n";
import { Lock } from "lucide-vue-next";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppTooltip from "@/shared/components/overlay/AppTooltip.vue";

defineProps({
    /** Explains what the badge implies here — deletion refused, and what to do instead. */
    hint: { type: String, default: "" },
});

const { t } = useI18n();
</script>

<template>
    <AppTooltip v-if="hint" :text="hint">
        <AppBadge color="gray" class="shrink-0">
            <Lock class="w-3 h-3" :stroke-width="2.5" /> {{ t("backend.system_badge") }}
        </AppBadge>
    </AppTooltip>
    <AppBadge v-else color="gray" class="shrink-0">
        <Lock class="w-3 h-3" :stroke-width="2.5" /> {{ t("backend.system_badge") }}
    </AppBadge>
</template>
