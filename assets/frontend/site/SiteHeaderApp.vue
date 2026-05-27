<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from "vue";
import { useI18n } from "vue-i18n";
import { Menu, ChevronDown, User, ShoppingCart, Package } from "lucide-vue-next";
import LocaleSwitcher from "./LocaleSwitcher.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppLink from "@/shared/components/nav/AppLink.vue";

const { t } = useI18n();

const props = defineProps({
    locale: { type: String, required: true },
    siteName: { type: String, required: true },
    homePath: { type: String, required: true },
    cartPath: { type: String, default: "" },
    accountOrdersPath: { type: String, default: "" },
    cartCount: { type: Number, default: 0 },
    ecommerceEnabled: { type: Boolean, default: false },
    headerLogoUrl: { type: String, default: "" },
    siteLogoUrl: { type: String, default: "" },
    headerCustomText: { type: String, default: "" },
    currentUser: { type: Object, default: null },
    primaryMenuItems: { type: Array, default: () => [] },
    accountMenuItems: { type: Array, default: () => [] },
    localeSwitcher: { type: Object, default: () => ({ enabled: false, locales: [] }) },
});

const logoUrl = computed(() => props.headerLogoUrl || props.siteLogoUrl);
const cartCount = ref(props.cartCount);
const showAccountMenu = computed(() => props.accountMenuItems.length > 0 || cartCount.value > 0 || props.currentUser);

const accountOpen = ref(false);
const accountRef = ref(null);

function toggleAccount() {
    accountOpen.value = !accountOpen.value;
}

function onClickOutside(event) {
    if (accountRef.value && !accountRef.value.contains(event.target)) {
        accountOpen.value = false;
    }
}

function onCartChanged(event) {
    const next = Number(event.detail?.count);
    if (Number.isFinite(next)) cartCount.value = next;
}

onMounted(() => {
    document.addEventListener("cart:changed", onCartChanged);
    document.addEventListener("click", onClickOutside);
});
onBeforeUnmount(() => {
    document.removeEventListener("cart:changed", onCartChanged);
    document.removeEventListener("click", onClickOutside);
});
</script>

<template>
    <header
        class="border-b"
        style="background-color: var(--th-header-bg, var(--th-surface)); border-color: var(--th-header-border, var(--color-border)); color: var(--th-header-text, var(--th-primary));"
    >
        <div class="w-full px-4 sm:px-6 lg:px-8 py-4 flex items-center gap-6 flex-wrap">
            <AppLink
                :href="homePath"
                variant="front-nav"
                extra-class="flex items-center gap-2 text-lg"
            >
                <img v-if="logoUrl" :src="logoUrl" :alt="siteName" class="h-8 w-8 object-cover rounded-xl shrink-0">
                <span>{{ headerCustomText || siteName }}</span>
            </AppLink>

            <nav v-if="primaryMenuItems.length" class="hidden md:flex items-center gap-1">
                <div v-for="item in primaryMenuItems" :key="item.id" class="relative group">
                    <AppLink
                        :href="item.url"
                        :target="item.openInNewTab ? '_blank' : '_self'"
                        variant="front-nav"
                        :extra-class="['inline-flex items-center gap-1 px-3 py-2 rounded-md text-sm transition-colors', item.cssClass]"
                    >
                        {{ item.label }}
                        <ChevronDown v-if="item.children && item.children.length" class="w-3.5 h-3.5" :stroke-width="2.5" />
                    </AppLink>
                    <div
                        v-if="item.children && item.children.length"
                        class="absolute left-0 top-full pt-1 min-w-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-100 z-50"
                    >
                        <div class="rounded-lg border shadow-xl overflow-hidden" style="background-color: var(--th-surface); border-color: var(--color-border);">
                            <component
                                :is="child.targetType === 'frontend_logout' ? 'form' : 'a'"
                                v-for="child in item.children"
                                :key="child.id"
                                :method="child.targetType === 'frontend_logout' ? 'POST' : null"
                                :action="child.targetType === 'frontend_logout' ? child.url : null"
                                :href="child.targetType === 'frontend_logout' ? null : child.url"
                                :target="child.openInNewTab ? '_blank' : null"
                                :rel="child.openInNewTab ? 'noopener' : null"
                                class="block px-4 py-2 text-sm transition-colors hover:bg-surface-2"
                                :class="child.cssClass"
                                style="color: var(--th-primary);"
                            >
                                <AppButton
                                    v-if="child.targetType === 'frontend_logout'"
                                    type="submit"
                                    variant="front-ghost"
                                    size="none"
                                    :class="'w-full text-left'"
                                >
                                    {{ child.label }}
                                </AppButton>
                                <template v-else>{{ child.label }}</template>
                            </component>
                        </div>
                    </div>
                </div>
            </nav>

            <details v-if="primaryMenuItems.length" class="md:hidden order-last basis-full">
                <summary class="list-none flex items-center gap-2 px-3 py-2 cursor-pointer rounded-md hover:opacity-80" style="color: var(--th-header-text, var(--th-primary));">
                    <Menu class="w-5 h-5" :stroke-width="2" />
                    <span class="text-sm">{{ t('frontend.menu.label') }}</span>
                </summary>
                <ul class="mt-2 space-y-1 border-t pt-2" style="border-color: var(--th-header-border, var(--color-border));">
                    <li v-for="item in primaryMenuItems" :key="item.id">
                        <AppLink
                            :href="item.url"
                            :target="item.openInNewTab ? '_blank' : '_self'"
                            variant="front-nav"
                            :extra-class="['block px-3 py-2 rounded-md text-sm', item.cssClass]"
                        >
                            {{ item.label }}
                        </AppLink>
                        <ul v-if="item.children && item.children.length" class="ml-4 mt-1 space-y-1 border-l pl-2" style="border-color: var(--th-header-border, var(--color-border));">
                            <li v-for="child in item.children" :key="child.id">
                                <AppLink
                                    :href="child.url"
                                    :target="child.openInNewTab ? '_blank' : '_self'"
                                    variant="front-nav"
                                    :extra-class="['block px-3 py-1.5 rounded-md text-sm', child.cssClass]"
                                >
                                    {{ child.label }}
                                </AppLink>
                            </li>
                        </ul>
                    </li>
                </ul>
            </details>

            <div class="ml-auto flex items-center gap-2 text-sm">
                <div v-if="showAccountMenu" ref="accountRef" class="relative">
                    <AppButton
                        type="button"
                        variant="front-ghost"
                        size="none"
                        :class="'inline-flex items-center gap-2 px-2 py-1.5 rounded-md text-sm transition-colors hover:opacity-80 relative'"
                        v-on:click.stop="toggleAccount"
                    >
                        <User class="w-4 h-4" :stroke-width="2" />
                        <span class="hidden sm:inline">{{ currentUser ? currentUser.name : t('frontend.menu.account') }}</span>
                        <span
                            v-show="cartCount > 0"
                            class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-accent text-white text-xs tabular-nums"
                        >{{ cartCount }}</span>
                        <ChevronDown class="w-3.5 h-3.5" :stroke-width="2.5" />
                    </AppButton>

                    <div v-if="accountOpen" class="absolute right-0 top-full mt-1 min-w-56 z-50">
                        <div class="rounded-lg border shadow-xl overflow-hidden" style="background-color: var(--th-surface); border-color: var(--color-border);">
                            <template v-if="ecommerceEnabled">
                                <AppLink
                                    :href="cartPath"
                                    variant="front-nav"
                                    extra-class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm transition-colors"
                                >
                                    <span class="flex items-center gap-2">
                                        <ShoppingCart class="w-4 h-4" :stroke-width="2" />
                                        {{ t('frontend.cart.title') }}
                                    </span>
                                    <span
                                        v-if="cartCount > 0"
                                        class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-accent text-white text-xs tabular-nums"
                                    >{{ cartCount }}</span>
                                </AppLink>
                                <AppLink
                                    v-if="currentUser"
                                    :href="accountOrdersPath"
                                    variant="front-nav"
                                    extra-class="flex items-center gap-2 px-4 py-2.5 text-sm transition-colors"
                                >
                                    <Package class="w-4 h-4" :stroke-width="2" />
                                    {{ t('frontend.account.orders') }}
                                </AppLink>
                            </template>
                            <div v-if="ecommerceEnabled && accountMenuItems.length" class="border-t" style="border-color: var(--color-border);" />
                            <component
                                :is="item.targetType === 'frontend_logout' ? 'form' : 'a'"
                                v-for="item in accountMenuItems"
                                :key="item.id"
                                :method="item.targetType === 'frontend_logout' ? 'POST' : null"
                                :action="item.targetType === 'frontend_logout' ? item.url : null"
                                :href="item.targetType === 'frontend_logout' ? null : item.url"
                                :target="item.openInNewTab ? '_blank' : null"
                                :rel="item.openInNewTab ? 'noopener' : null"
                                class="block px-4 py-2 text-sm transition-colors hover:bg-surface-2"
                                :class="item.cssClass"
                                style="color: var(--th-primary);"
                            >
                                <AppButton
                                    v-if="item.targetType === 'frontend_logout'"
                                    type="submit"
                                    variant="front-ghost"
                                    size="none"
                                    :class="'w-full text-left'"
                                >
                                    {{ item.label }}
                                </AppButton>
                                <template v-else>{{ item.label }}</template>
                            </component>
                        </div>
                    </div>
                </div>
            </div>

            <LocaleSwitcher
                v-if="localeSwitcher.enabled"
                :current-locale="locale"
                :locales="localeSwitcher.locales"
            />
        </div>
    </header>
</template>
