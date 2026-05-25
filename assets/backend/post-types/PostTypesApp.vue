<script setup>
import { useI18n } from "vue-i18n";
import { VueDraggable } from "vue-draggable-plus";
import { usePostTypeSelect } from "@editorial/backend/post-types/composables/usePostTypeSelect.js";
import { usePostTypeModal } from "@editorial/backend/post-types/composables/usePostTypeModal.js";
import { usePostTypeDelete } from "@editorial/backend/post-types/composables/usePostTypeDelete.js";
import { usePostTypeFields } from "@editorial/backend/post-types/composables/usePostTypeFields.js";
import { Plus, Pencil, Trash2, Layers, Lock, GripVertical, Save, X, ClipboardList } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppSelect from "@/shared/components/form/select/AppSelect.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppCheckbox from "@/shared/components/form/toggle/AppCheckbox.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import { PostFieldType } from "@editorial/shared/enums/postFieldType.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    postTypes: { type: Array, default: () => [] },
    taxonomies: { type: Array, default: () => [] },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    fieldCreatePath: { type: String, required: true },
    fieldEditPath: { type: String, required: true },
    fieldDeletePath: { type: String, required: true },
    fieldReorderPath: { type: String, required: true },
});

const FIELD_TYPES = Object.values(PostFieldType);

const { postTypes, selectedId, selected, replacePostType } = usePostTypeSelect(props.postTypes);
const { postTypeModal, form: postTypeForm, postTypeErrors, postTypeLoading, openCreatePostType, openEditPostType, submitPostType, toggleIn } = usePostTypeModal(props, postTypes, selectedId, replacePostType);
const { deletingPostType, confirmDeletePostType } = usePostTypeDelete(props.deletePath, postTypes, selectedId);
const { fieldModal, fieldForm, openCreateField, openEditField, submitField, deletingField, confirmDeleteField, orderedFields, persistFieldOrder } =
    usePostTypeFields(props, selected, replacePostType);


</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <aside class="lg:w-72 shrink-0 space-y-2">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("backend.post_types.title") }}</h2>
                <AppButton v-if="can('editorial.post_types.create')" variant="primary" size="md" v-on:click="openCreatePostType">
                    <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("shared.common.add") }}
                </AppButton>
            </div>
            <div class="space-y-1">
                <AppButton
                    v-for="postType in postTypes"
                    :key="postType.id"
                    variant="nav"
                    size="nav"
                    :active="selectedId === postType.id"
                    v-on:click="selectedId = postType.id"
                >
                    <Layers class="w-4 h-4 shrink-0" :stroke-width="2" />
                    <span class="flex-1 font-medium truncate">{{ postType.label }}</span>
                    <Lock v-if="postType.isBuiltIn" class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" :title="t('backend.post_types.built_in')" />
                </AppButton>
            </div>
        </aside>

        <main class="flex-1 min-w-0 space-y-4">
            <AppNoData v-if="!selected" :message="t('backend.post_types.empty')" />
            <div v-else class="space-y-4">
                <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-primary">{{ selected.label }}</h3>
                            <p class="text-xs text-muted font-mono mt-0.5">{{ selected.slug }}</p>
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <AppBadge v-if="selected.isBuiltIn" color="amber">
                                    <Lock class="w-3 h-3" :stroke-width="2" />
                                    {{ t("backend.post_types.built_in") }}
                                </AppBadge>
                                <AppBadge v-if="selected.hasArchive" color="sky">{{ t("backend.post_types.has_archive") }}</AppBadge>
                                <AppBadge v-for="support in selected.supports" :key="support" color="gray">{{ support }}</AppBadge>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <AppButton v-if="can('editorial.post_types.edit')" variant="secondary" size="md" v-on:click="openEditPostType(selected)">
                                <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("shared.common.edit") }}
                            </AppButton>
                            <AppButton
                                v-if="!selected.isBuiltIn && can('editorial.post_types.delete')"
                                variant="danger"
                                size="md"
                                v-on:click="deletingPostType = selected"
                            >
                                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                                {{ t("shared.common.delete") }}
                            </AppButton>
                        </div>
                    </div>
                </div>

                <div class="bg-surface border border-line/60 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <h4 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t("backend.post_types.fields.title") }}</h4>
                        <AppButton v-if="can('editorial.post_types.edit')" variant="primary" size="md" v-on:click="openCreateField">
                            <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("shared.common.add") }}
                        </AppButton>
                    </div>

                    <AppNoData v-if="!orderedFields.length" :message="t('backend.post_types.fields.empty')" />
                    <AppMessage v-else variant="info">
                        {{ t("backend.post_types.fields.dnd_hint") }}
                    </AppMessage>

                    <VueDraggable
                        v-if="orderedFields.length"
                        v-model="orderedFields"
                        handle=".drag-handle"
                        :animation="150"
                        ghost-class="opacity-50"
                        class="space-y-1"
                        v-on:end="persistFieldOrder"
                    >
                        <div
                            v-for="field in orderedFields"
                            :key="field.id"
                            class="flex items-center gap-2 px-3 py-2 rounded-md border border-line bg-surface-2"
                        >
                            <AppIconButton class="drag-handle cursor-grab active:cursor-grabbing p-1" :title="''">
                                <GripVertical class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-primary truncate">{{ field.label }}</div>
                                <div class="text-xs text-muted font-mono truncate">{{ field.name }}</div>
                            </div>
                            <AppBadge color="gray">{{ field.type }}</AppBadge>
                            <AppBadge v-if="field.required" color="rose">{{ t("backend.post_types.fields.required") }}</AppBadge>
                            <AppBadge v-if="field.translatable" color="sky">{{ t("backend.post_types.fields.translatable") }}</AppBadge>
                            <div class="flex items-center gap-0.5">
                                <AppIconButton v-if="can('editorial.post_types.edit')" color="accent" v-on:click="openEditField(field)">
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="can('editorial.post_types.edit')" color="rose" v-on:click="deletingField = field">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </div>
                    </VueDraggable>
                </div>
            </div>
        </main>

        <AppModal
            :show="postTypeModal.open"
            max-width="lg"
            :title="postTypeModal.entity ? t('backend.post_types.edit_post_type') : t('backend.post_types.create')"
            :icon="postTypeModal.entity ? Pencil : Layers"
            :closeable="false"
            v-on:close="postTypeModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitPostType">
                <AppInput
                    v-model="postTypeForm.slug"
                    :label="t('backend.post_types.slug')"
                    :error="postTypeErrors.slug ?? ''"
                    :disabled="postTypeModal.entity?.isBuiltIn ?? false"
                    :placeholder="t('backend.post_types.slug_placeholder')"
                />
                <AppInput
                    v-model="postTypeForm.label"
                    :label="t('backend.post_types.label')"
                    :error="postTypeErrors.label ?? ''"
                    :placeholder="t('backend.post_types.label_placeholder')"
                />
                <AppInput
                    v-model="postTypeForm.icon"
                    :label="t('backend.post_types.icon')"
                    :placeholder="t('backend.post_types.icon_placeholder')"
                />
                <AppCheckbox v-model="postTypeForm.hasArchive" :label="t('backend.post_types.has_archive')" />

                <div class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t("backend.post_types.supports") }}</label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="support in SUPPORTS"
                            :key="support"
                            class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium cursor-pointer border transition-colors"
                            :class="postTypeForm.supports.includes(support)
                                ? 'bg-accent-600 border-accent-600 text-white'
                                : 'bg-surface-2 border-line text-secondary hover:border-accent-400'"
                        >
                            <input
                                type="checkbox"
                                class="sr-only"
                                :checked="postTypeForm.supports.includes(support)"
                                v-on:change="postTypeForm.supports = toggleIn(postTypeForm.supports, support)"
                            >
                            {{ support }}
                        </label>
                    </div>
                </div>

                <div v-if="taxonomies.length" class="space-y-2">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t("backend.taxonomies.title") }}</label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="taxonomy in taxonomies"
                            :key="taxonomy.id"
                            class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium cursor-pointer border transition-colors"
                            :class="postTypeForm.taxonomyIds.includes(taxonomy.id)
                                ? 'bg-accent-600 border-accent-600 text-white'
                                : 'bg-surface-2 border-line text-secondary hover:border-accent-400'"
                        >
                            <input
                                type="checkbox"
                                class="sr-only"
                                :checked="postTypeForm.taxonomyIds.includes(taxonomy.id)"
                                v-on:change="postTypeForm.taxonomyIds = toggleIn(postTypeForm.taxonomyIds, taxonomy.id)"
                            >
                            {{ taxonomy.slug }}
                        </label>
                    </div>
                </div>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="postTypeModal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="postTypeLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="fieldModal.open"
            max-width="lg"
            :title="fieldModal.editing ? t('backend.post_types.fields.edit') : t('backend.post_types.fields.add')"
            :icon="fieldModal.editing ? Pencil : ClipboardList"
            :closeable="false"
            v-on:close="fieldModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitField">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppInput
                        v-model="fieldForm.name"
                        :label="t('backend.post_types.fields.name')"
                        :error="fieldModal.errors.name ?? ''"
                        :placeholder="t('backend.post_types.fields.name_placeholder')"
                    />
                    <AppInput
                        v-model="fieldForm.label"
                        :label="t('backend.post_types.fields.label')"
                        :error="fieldModal.errors.label ?? ''"
                        :placeholder="t('backend.post_types.fields.label_placeholder')"
                    />
                </div>

                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t("backend.post_types.fields.type") }}</label>
                    <AppSelect v-model="fieldForm.type">
                        <option v-for="fieldType in FIELD_TYPES" :key="fieldType" :value="fieldType">{{ fieldType }}</option>
                    </AppSelect>
                </div>

                <div v-if="fieldForm.type === 'select'">
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t("backend.post_types.fields.select_choices") }}</label>
                    <textarea
                        v-model="fieldForm.choicesText"
                        rows="5"
                        class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary placeholder-muted font-mono focus:border-accent-500 focus:ring-1 focus:ring-accent-500 transition resize-none"
                        :placeholder="t('backend.post_types.fields.select_choices_placeholder')"
                    />
                    <p class="text-xs text-muted mt-1">{{ t("backend.post_types.fields.select_choices_hint") }}</p>
                </div>

                <div v-if="fieldForm.type === 'reference'" class="space-y-2">
                    <AppMultiselect
                        v-model="fieldForm.referencePostTypeId"
                        :options="postTypes"
                        :label="t('backend.post_types.fields.reference_target_type')"
                        :placeholder="t('backend.post_types.fields.reference_any_type')"
                        :allow-empty="true"
                        track-by="id"
                        option-label="label"
                    />
                    <AppCheckbox v-model="fieldForm.referenceMultiple" :label="t('backend.post_types.fields.reference_multiple')" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppCheckbox v-model="fieldForm.required" :label="t('backend.post_types.fields.required')" />
                    <AppCheckbox v-model="fieldForm.translatable" :label="t('backend.post_types.fields.translatable')" />
                </div>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="fieldModal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="fieldModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!deletingPostType"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingPostType = null"
        >
            <p class="text-sm text-primary">{{ t("backend.post_types.delete_confirm", { label: deletingPostType?.label }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingPostType = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeletePostType"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!deletingField"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingField = null"
        >
            <p class="text-sm text-primary">{{ t("backend.post_types.fields.delete_confirm", { label: deletingField?.label }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingField = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeleteField"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
