<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";
import { useTaxonomySelect } from "@editorial/backend/taxonomies/composables/useTaxonomySelect.js";
import { useTaxonomyTree } from "@editorial/backend/taxonomies/composables/useTaxonomyTree.js";
import { useTaxonomyDelete } from "@editorial/backend/taxonomies/composables/useTaxonomyDelete.js";
import { useTermDelete } from "@editorial/backend/taxonomies/composables/useTermDelete.js";
import { Plus, Pencil, Trash2, FolderTree, Folder, ChevronDown, ChevronRight, GripVertical, Lock, Save, X, Tag } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppCheckbox from "@/shared/components/form/toggle/AppCheckbox.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNavListItem from "@/shared/components/nav/AppNavListItem.vue";
import TermNode from "@editorial/backend/taxonomies/TermNode.vue";
import { useTermFormHelpers } from "@editorial/backend/taxonomies/composables/useTermFormHelpers.js";
import { required } from "@/shared/utils/validation/validators.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    taxonomies: { type: Array, default: () => [] },
    postTypes: { type: Array, default: () => [] },
    locales: { type: Array, default: () => ["fr"] },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    termCreatePath: { type: String, required: true },
    termEditPath: { type: String, required: true },
    termDeletePath: { type: String, required: true },
    termReorderPath: { type: String, required: true },
});

const { taxonomies, selectedId, activeLocale, selected, replaceTaxonomy, translationLabel, termName } =
    useTaxonomySelect(props.taxonomies, props.locales);

const flatTerms = computed(() => selected.value?.terms ?? []);

const {
    tree, collapsed, toggleCollapsed, parentOptionsForTerm,
    draggingId, dragOverId, rootDragOver,
    onTermDragStart, onTermDragEnd, onTermDragOver, onTermDragLeave,
    onRootDragOver, onRootDragLeave, onDropOnTerm, onDropOnRoot,
} = useTaxonomyTree(selected, flatTerms, props.termReorderPath, props.locales, activeLocale, replaceTaxonomy, termName);

const { autoSlugTerm: autoSlugTermHelper, normalizeAllLocaleSlugs } = useTermFormHelpers();

const { deletingTaxonomy, confirmDeleteTaxonomy } = useTaxonomyDelete(props.deletePath, taxonomies, selectedId);
const { deletingTerm, confirmDeleteTerm } = useTermDelete(props.termDeletePath, selected, replaceTaxonomy);

// ── Taxonomy form (modal) ────────────────────────────────────────────────────
const {
    modal: taxonomyModal, form: taxonomyForm,
    errors: taxonomyErrors, loading: taxonomyLoading,
    openCreate: openCreateTaxonomy, openEdit: openEditTaxonomy, submit: submitTaxonomy,
} = useFormModal({
    empty: () => ({
        slug: "", hierarchical: false,
        postTypeIds: props.postTypes.map((pt) => pt.id),
        translations: Object.fromEntries(props.locales.map((l) => [l, { label: "", description: "" }])),
    }),
    fromEntity: (tx) => ({
        slug: tx.slug, hierarchical: tx.hierarchical,
        postTypeIds: [...(tx.postTypeIds ?? [])],
        translations: Object.fromEntries(props.locales.map((l) => [l, {
            label: tx.translations?.[l]?.label ?? "",
            description: tx.translations?.[l]?.description ?? "",
        }])),
    }),
    createUrl: () => props.createPath,
    editUrl:   (tx) => buildPath(props.updatePath, { id: tx.id }),
    rules: () => ({
        slug: () => required(t("backend.taxonomies.errors.slug_required"))(taxonomyForm.slug),
        [`translations[${activeLocale.value}].label`]: () =>
            required(t("backend.taxonomies.errors.label_required"))(taxonomyForm.translations[activeLocale.value]?.label),
    }),
    onSuccess: ({ data }) => { replaceTaxonomy(data.taxonomy); selectedId.value = data.taxonomy.id; },
});

// ── Term form (modal) ────────────────────────────────────────────────────────
const pendingParentId = { value: null };

const {
    modal: termModal, form: termForm,
    errors: termErrors, loading: termLoading,
    openCreate: openTermCreate, openEdit: openEditTerm, submit: rawSubmitTerm,
} = useFormModal({
    empty: () => ({
        parentId: pendingParentId.value,
        translations: Object.fromEntries(props.locales.map((l) => [l, { name: "", slug: "", description: "" }])),
    }),
    fromEntity: (tr) => ({
        parentId: tr.parentId,
        translations: Object.fromEntries(props.locales.map((l) => [l, {
            name: tr.translations?.[l]?.name ?? "",
            slug: tr.translations?.[l]?.slug ?? "",
            description: tr.translations?.[l]?.description ?? "",
        }])),
    }),
    createUrl: () => buildPath(props.termCreatePath, { id: selected.value.id }),
    editUrl:   (tr) => buildPath(props.termEditPath, { id: selected.value.id, termId: tr.id }),
    rules: () => ({
        [`translations[${activeLocale.value}].name`]: () =>
            required(t("backend.taxonomies.terms.errors.name_required"))(termForm.translations[activeLocale.value]?.name),
    }),
    onSuccess: ({ data }) => replaceTaxonomy(data.taxonomy),
});

function openCreateTerm(parentId = null) {
    pendingParentId.value = parentId;
    openTermCreate();
}

const autoSlugTerm = (locale) => autoSlugTermHelper(termForm, locale);

function submitTerm() {
    if (!selected.value) return;
    normalizeAllLocaleSlugs(termForm, props.locales);
    rawSubmitTerm();
}

const parentOptions = computed(() => parentOptionsForTerm(termModal.entity));
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <aside class="lg:w-72 shrink-0 space-y-2">
            <div class="flex items-center gap-1.5">
                <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide flex-1">{{ t("backend.taxonomies.title") }}</h2>
                <AppIconButton
                    v-if="can('editorial.taxonomies.create')"
                    :title="t('backend.taxonomies.addTaxonomy')"
                    v-on:click="openCreateTaxonomy"
                >
                    <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                </AppIconButton>
            </div>
            <!--
                Same visual language as the media folder sidebar: plain
                button rows, transparent border by default, accent fill +
                border on the selected entry, hover-only surface tint.
            -->
            <div class="space-y-0.5">
                <AppNavListItem
                    v-for="taxonomy in taxonomies"
                    :key="taxonomy.id"
                    :active="selectedId === taxonomy.id"
                    v-on:click="selectedId = taxonomy.id"
                >
                    <template #icon>
                        <FolderTree v-if="taxonomy.hierarchical" class="w-4 h-4" :stroke-width="2" />
                        <Folder v-else class="w-4 h-4" :stroke-width="2" />
                    </template>
                    {{ translationLabel(taxonomy, activeLocale) }}
                    <template v-if="taxonomy.isBuiltIn" #trailing>
                        <Lock class="w-3.5 h-3.5 text-muted" :stroke-width="2" :title="t('backend.taxonomies.builtIn')" />
                    </template>
                </AppNavListItem>
            </div>
        </aside>

        <main class="flex-1 min-w-0 space-y-4">
            <AppNoData v-if="!selected" :message="t('backend.taxonomies.empty')" />
            <div v-else class="space-y-4">
                <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-primary">{{ translationLabel(selected, activeLocale) }}</h3>
                            <p class="text-xs text-muted font-mono mt-0.5">{{ selected.slug }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <AppBadge v-if="selected.hierarchical" color="sky">
                                    <FolderTree class="w-3 h-3" :stroke-width="2" />
                                    {{ t("backend.taxonomies.hierarchical") }}
                                </AppBadge>
                                <AppBadge v-if="selected.isBuiltIn" color="amber">
                                    <Lock class="w-3 h-3" :stroke-width="2" />
                                    {{ t("backend.taxonomies.builtIn") }}
                                </AppBadge>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <AppButton v-if="can('editorial.taxonomies.edit')" variant="secondary" size="md" v-on:click="openEditTaxonomy(selected)">
                                <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("shared.common.edit") }}
                            </AppButton>
                            <AppButton
                                v-if="!selected.isBuiltIn && can('editorial.taxonomies.delete')"
                                variant="danger"
                                size="md"
                                v-on:click="deletingTaxonomy = selected"
                            >
                                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("shared.common.delete") }}
                            </AppButton>
                        </div>
                    </div>
                </div>

                <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <h4 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("backend.taxonomies.terms.title") }}</h4>
                        <div class="flex items-center gap-2">
                            <div v-if="locales.length > 1" class="flex gap-1">
                                <AppTab
                                    v-for="locale in locales"
                                    :key="locale"
                                    size="xs"
                                    :active="activeLocale === locale"
                                    active-class="bg-accent-600 text-white"
                                    v-on:click="activeLocale = locale"
                                >
                                    {{ locale.toUpperCase() }}
                                </AppTab>
                            </div>
                            <AppButton v-if="can('editorial.taxonomies.edit')" variant="primary" size="md" v-on:click="openCreateTerm()">
                                <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("shared.common.add") }}
                            </AppButton>
                        </div>
                    </div>

                    <AppMessage v-if="selected.hierarchical" variant="info">
                        {{ t("backend.taxonomies.terms.dndHint") }}
                    </AppMessage>

                    <AppNoData v-if="!tree.length" :message="t('backend.taxonomies.terms.empty')" />

                    <div
                        v-else
                        class="space-y-1 rounded-md transition-colors"
                        :class="rootDragOver ? 'bg-accent-50 dark:bg-accent-900/10 ring-1 ring-accent-500/40' : ''"
                        v-on:dragover="onRootDragOver"
                        v-on:dragleave="onRootDragLeave"
                        v-on:drop="onDropOnRoot"
                    >
                        <TermNode
                            v-for="node in tree"
                            :key="node.id"
                            :node="node"
                            :hierarchical="selected.hierarchical"
                            :active-locale="activeLocale"
                            :collapsed="collapsed"
                            :dragging-id="draggingId"
                            :drag-over-id="dragOverId"
                            v-on:toggle-collapse="toggleCollapsed($event)"
                            v-on:edit="openEditTerm($event)"
                            v-on:delete="deletingTerm = $event"
                            v-on:add-child="openCreateTerm($event)"
                            v-on:term-drag-start="onTermDragStart"
                            v-on:term-drag-end="onTermDragEnd"
                            v-on:term-drag-over="onTermDragOver"
                            v-on:term-drag-leave="onTermDragLeave"
                            v-on:drop-on-term="onDropOnTerm"
                        />
                    </div>
                </div>
            </div>
        </main>

        <AppModal
            :show="taxonomyModal.open"
            max-width="lg"
            :title="taxonomyModal.entity ? t('backend.taxonomies.editTaxonomy') : t('backend.taxonomies.addTaxonomy')"
            :icon="taxonomyModal.entity ? Pencil : Tag"
            :closeable="false"
            v-on:close="taxonomyModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitTaxonomy">
                <AppInput
                    v-model="taxonomyForm.slug"
                    :label="t('backend.taxonomies.slug')"
                    :error="taxonomyErrors.slug ?? ''"
                    :placeholder="t('backend.taxonomies.slugPlaceholder')"
                    :disabled="taxonomyModal.entity?.isBuiltIn ?? false"
                />

                <AppCheckbox
                    v-model="taxonomyForm.hierarchical"
                    :label="t('backend.taxonomies.hierarchical')"
                    :disabled="taxonomyModal.entity?.isBuiltIn ?? false"
                />

                <div class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t("backend.taxonomies.translations") }}</label>
                    <div class="flex gap-1">
                        <AppTab
                            v-for="locale in locales"
                            :key="locale"
                            size="xs"
                            :active="activeLocale === locale"
                            active-class="bg-accent-600 text-white"
                            inactive-class="bg-surface-2 text-secondary hover:bg-surface-3"
                            v-on:click="activeLocale = locale"
                        >
                            {{ locale.toUpperCase() }}
                        </AppTab>
                    </div>
                    <AppInput
                        v-model="taxonomyForm.translations[activeLocale].label"
                        :label="t('backend.taxonomies.label')"
                        :placeholder="t('backend.taxonomies.labelPlaceholder')"
                        :error="taxonomyErrors[`translations[${activeLocale}].label`] ?? ''"
                    />
                    <AppTextarea
                        v-model="taxonomyForm.translations[activeLocale].description"
                        :label="t('backend.taxonomies.description')"
                        :placeholder="t('backend.taxonomies.descriptionPlaceholder')"
                        :rows="2"
                    />
                </div>

                <div class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t("backend.taxonomies.postTypes") }}</label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="pt in postTypes"
                            :key="pt.id"
                            class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium cursor-pointer border transition-colors"
                            :class="taxonomyForm.postTypeIds.includes(pt.id)
                                ? 'bg-accent-600 border-accent-600 text-white'
                                : 'bg-surface-2 border-line text-secondary hover:border-accent-400'"
                        >
                            <input
                                type="checkbox"
                                class="sr-only"
                                :checked="taxonomyForm.postTypeIds.includes(pt.id)"
                                v-on:change="taxonomyForm.postTypeIds.includes(pt.id)
                                    ? taxonomyForm.postTypeIds = taxonomyForm.postTypeIds.filter((id) => id !== pt.id)
                                    : taxonomyForm.postTypeIds.push(pt.id)"
                            >
                            {{ pt.label }}
                        </label>
                    </div>
                </div>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="taxonomyModal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" :loading="taxonomyLoading" v-on:click="submitTaxonomy"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="termModal.open"
            max-width="md"
            :title="termModal.entity ? t('backend.taxonomies.terms.editTerm') : t('backend.taxonomies.terms.addTerm')"
            :icon="termModal.entity ? Pencil : Tag"
            :closeable="false"
            v-on:close="termModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitTerm">
                <div v-if="selected?.hierarchical">
                    <AppMultiselect
                        v-model="termForm.parentId"
                        :options="parentOptions"
                        :label="t('backend.taxonomies.terms.parent')"
                        :placeholder="t('backend.taxonomies.terms.noParent')"
                        :allow-empty="true"
                        track-by="id"
                        option-label="label"
                    />
                </div>

                <div class="flex gap-1">
                    <AppTab
                        v-for="locale in locales"
                        :key="locale"
                        size="xs"
                        :active="activeLocale === locale"
                        active-class="bg-accent-600 text-white"
                        inactive-class="bg-surface-2 text-secondary hover:bg-surface-3"
                        v-on:click="activeLocale = locale"
                    >
                        {{ locale.toUpperCase() }}
                    </AppTab>
                </div>

                <AppInput
                    v-model="termForm.translations[activeLocale].name"
                    :label="t('backend.taxonomies.terms.name')"
                    :error="termErrors[`translations[${activeLocale}].name`] ?? ''"
                    :placeholder="t('backend.taxonomies.terms.namePlaceholder')"
                    v-on:blur="autoSlugTerm(activeLocale)"
                />
                <AppInput
                    v-model="termForm.translations[activeLocale].slug"
                    :label="t('backend.taxonomies.terms.slug')"
                    :error="termErrors[`translations[${activeLocale}].slug`] ?? ''"
                    :placeholder="t('backend.taxonomies.terms.slugPlaceholder')"
                />
                <AppTextarea
                    v-model="termForm.translations[activeLocale].description"
                    :label="t('backend.taxonomies.terms.description')"
                    :placeholder="t('backend.taxonomies.terms.descriptionPlaceholder')"
                    :rows="2"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="termModal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" :loading="termLoading" v-on:click="submitTerm"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!deletingTaxonomy"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingTaxonomy = null"
        >
            <p class="text-sm text-primary">{{ t("backend.taxonomies.deleteTaxonomyConfirm", { label: translationLabel(deletingTaxonomy, activeLocale) }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingTaxonomy = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeleteTaxonomy"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!deletingTerm"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingTerm = null"
        >
            <p class="text-sm text-primary">{{ t("backend.taxonomies.terms.deleteTermConfirm", { name: termName(deletingTerm, activeLocale) }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingTerm = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeleteTerm"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
