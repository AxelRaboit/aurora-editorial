<script setup>
import { useI18n } from "vue-i18n";
import { useMenuItemForm } from "./composables/useMenuItemForm.js";
import { Search, Check, X, Save, Menu, Pencil } from "lucide-vue-next";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppCheckbox from "@/shared/components/form/toggle/AppCheckbox.vue";
import AppListItemButton from "@/shared/components/action/AppListItemButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, required: true },
    editing: { type: Object, default: null },
    targetTypes: { type: Array, default: () => [] },
    visibilities: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
    pickerPostsPath: { type: String, required: true },
    pickerTermsPath: { type: String, required: true },
    pickerPostTypesPath: { type: String, required: true },
    pickerTaxonomiesPath: { type: String, required: true },
});

const emit = defineEmits(["close", "save"]);

const {
    form, targetLabel, activeLocale, saving, errors,
    requiresTargetId, requiresCustomUrl, requiresTranslationOverride,
    pickerQuery, pickerResults, pickerLoading, pickerOpen,
    postTypeFilter, taxonomyFilter, postTypeOptions, taxonomyOptions,
    debouncedSearch, onPickerFocus, pickResult, clearTarget,
    archiveOptions, visibilityOptions, targetTypeOptions,
    buildPayload, setTranslation,
} = useMenuItemForm(props);

function save() {
    const payload = buildPayload();
    if (payload) emit("save", payload);
}

function close() { emit("close"); }



</script>

<template>
    <AppModal
        :show="show"
        max-width="lg"
        :title="editing ? t('backend.menus.editItem') : t('backend.menus.addItem')"
        :icon="editing ? Pencil : Menu"
        :closeable="false"
        v-on:close="close"
    >
        <form class="space-y-4" v-on:submit.prevent="save">
            <AppMultiselect
                v-model="form.targetType"
                :options="targetTypeOptions"
                :label="t('backend.menus.targetType')"
                :allow-empty="false"
                :searchable="false"
            />

            <div v-if="form.targetType === 'post'" class="space-y-2">
                <label class="block text-xs font-semibold text-secondary uppercase tracking-wide">
                    {{ t("backend.menus.target") }}
                </label>
                <AppMultiselect
                    v-if="postTypeOptions.length"
                    v-model="postTypeFilter"
                    :options="postTypeOptions.map((pt) => ({ value: pt.id, label: pt.label }))"
                    :placeholder="t('backend.menus.allTypes')"
                    track-by="value"
                />
                <div v-if="form.targetId && targetLabel" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-surface-2 border border-line text-sm">
                    <Check class="w-4 h-4 text-emerald-400 shrink-0" :stroke-width="2" />
                    <span class="text-primary flex-1 truncate">{{ targetLabel }}</span>
                    <AppIconButton v-on:click="clearTarget">
                        <X class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                </div>
                <div v-else class="relative">
                    <div class="relative">
                        <AppInput
                            v-model="pickerQuery"
                            :placeholder="t('backend.menus.searchPostsPlaceholder')"
                            v-on:input="debouncedSearch"
                            v-on:focus="onPickerFocus"
                        >
                            <template #prefix>
                                <Search class="w-4 h-4 text-muted" :stroke-width="2" />
                            </template>
                        </AppInput>
                    </div>
                    <div v-if="pickerOpen && pickerResults.length" class="absolute left-0 right-0 mt-1 max-h-64 overflow-y-auto bg-surface border border-line rounded-md shadow-lg z-10">
                        <AppListItemButton
                            v-for="result in pickerResults"
                            :key="result.id"
                            v-on:click="pickResult(result)"
                        >
                            <p class="text-primary truncate">{{ result.label }}</p>
                            <p v-if="result.hint" class="text-xs text-muted truncate">{{ result.hint }}</p>
                        </AppListItemButton>
                    </div>
                </div>
                <p v-if="errors.target" class="text-xs text-rose-400">{{ errors.target }}</p>
            </div>

            <div v-else-if="form.targetType === 'term'" class="space-y-2">
                <label class="block text-xs font-semibold text-secondary uppercase tracking-wide">
                    {{ t("backend.menus.target") }}
                </label>
                <AppMultiselect
                    v-if="taxonomyOptions.length"
                    v-model="taxonomyFilter"
                    :options="taxonomyOptions.map((tx) => ({ value: tx.id, label: tx.label }))"
                    :placeholder="t('backend.menus.allTaxonomies')"
                    track-by="value"
                />
                <div v-if="form.targetId && targetLabel" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-surface-2 border border-line text-sm">
                    <Check class="w-4 h-4 text-emerald-400 shrink-0" :stroke-width="2" />
                    <span class="text-primary flex-1 truncate">{{ targetLabel }}</span>
                    <AppIconButton v-on:click="clearTarget">
                        <X class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                </div>
                <div v-else class="relative">
                    <div class="relative">
                        <AppInput
                            v-model="pickerQuery"
                            :placeholder="t('backend.menus.searchTermsPlaceholder')"
                            v-on:input="debouncedSearch"
                            v-on:focus="onPickerFocus"
                        >
                            <template #prefix>
                                <Search class="w-4 h-4 text-muted" :stroke-width="2" />
                            </template>
                        </AppInput>
                    </div>
                    <div v-if="pickerOpen && pickerResults.length" class="absolute left-0 right-0 mt-1 max-h-64 overflow-y-auto bg-surface border border-line rounded-md shadow-lg z-10">
                        <AppListItemButton
                            v-for="result in pickerResults"
                            :key="result.id"
                            v-on:click="pickResult(result)"
                        >
                            <p class="text-primary truncate">{{ result.label }}</p>
                            <p v-if="result.hint" class="text-xs text-muted truncate">{{ result.hint }}</p>
                        </AppListItemButton>
                    </div>
                </div>
                <p v-if="errors.target" class="text-xs text-rose-400">{{ errors.target }}</p>
            </div>

            <div v-else-if="form.targetType === 'post_type_archive'">
                <AppMultiselect
                    v-model="form.targetId"
                    :options="archiveOptions"
                    :label="t('backend.menus.target')"
                    :error="errors.target"
                    :allow-empty="false"
                    track-by="value"
                />
            </div>

            <AppInput
                v-else-if="form.targetType === 'custom_url'"
                v-model="form.customUrl"
                :label="t('backend.menus.customUrl')"
                placeholder="https://… or /path"
                :error="errors.customUrl"
                required
            />

            <div class="space-y-2">
                <label class="block text-xs font-semibold text-secondary uppercase tracking-wide">
                    {{ t("backend.menus.translations") }}
                </label>
                <p class="text-xs text-muted">{{ t("backend.menus.translationsHint") }}</p>
                <div class="flex gap-1 flex-wrap">
                    <AppTab
                        v-for="locale in locales"
                        :key="locale"
                        variant="pill"
                        size="sm"
                        :active="activeLocale === locale"
                        :active-class="'bg-accent-600 text-white'"
                        v-on:click="activeLocale = locale"
                    >
                        {{ locale.toUpperCase() }}
                        <span v-if="form.translations[locale]" class="ml-1">●</span>
                    </AppTab>
                </div>
                <AppInput
                    :model-value="form.translations[activeLocale] ?? ''"
                    :placeholder="t('backend.menus.translationPlaceholder')"
                    :error="errors.translations"
                    v-on:update:model-value="setTranslation(activeLocale, $event)"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-line">
                <AppMultiselect
                    v-model="form.visibility"
                    :options="visibilityOptions"
                    :label="t('backend.menus.visibility')"
                    :allow-empty="false"
                    :searchable="false"
                />
                <AppInput
                    v-model="form.cssClass"
                    :label="t('backend.menus.cssClass')"
                    placeholder="font-bold text-accent-500"
                />
            </div>

            <AppCheckbox v-model="form.openInNewTab" :label="t('backend.menus.openInNewTab')" />
        </form>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="close"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                <AppButton type="submit" variant="primary" size="md" :loading="saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
