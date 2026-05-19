<script setup>
import { useI18n } from "vue-i18n";
import AppLink from "@/shared/components/nav/AppLink.vue";

const { t } = useI18n();

defineProps({
    footerText: { type: String, default: "" },
    footerMenuItems: { type: Array, default: () => [] },
});
</script>

<template>
    <footer
        class="border-t mt-12"
        style="background-color: var(--th-footer-bg, var(--th-surface)); border-color: var(--th-footer-border, var(--color-border));"
    >
        <div
            class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-4 text-center text-xs"
            style="color: var(--th-footer-text, var(--th-muted));"
        >
            <ul v-if="footerMenuItems.length" class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
                <li v-for="item in footerMenuItems" :key="item.id">
                    <AppLink
                        :href="item.url"
                        :target="item.openInNewTab ? '_blank' : '_self'"
                        variant="front-nav"
                        :extra-class="['text-xs transition-colors', item.cssClass]"
                    >
                        {{ item.label }}
                    </AppLink>
                    <ul v-if="item.children && item.children.length" class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 ml-2">
                        <li v-for="child in item.children" :key="child.id">
                            <AppLink
                                :href="child.url"
                                :target="child.openInNewTab ? '_blank' : '_self'"
                                variant="front-nav"
                                :extra-class="['text-xs transition-colors', child.cssClass]"
                            >
                                {{ child.label }}
                            </AppLink>
                        </li>
                    </ul>
                </li>
            </ul>
            <p>{{ footerText }}</p>
            <p class="opacity-50">
                <a href="https://github.com/AxelRaboit" target="_blank" rel="noopener" class="hover:opacity-80 transition-opacity">{{ t('shared.common.built_with') }}</a>
            </p>
        </div>
    </footer>
</template>
