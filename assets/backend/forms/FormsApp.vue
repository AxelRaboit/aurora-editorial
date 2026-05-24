<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { VueDraggable } from "vue-draggable-plus";
import { FormFieldType } from "@editorial/shared/enums/formFieldType.js";
import { useFormsList } from "@editorial/backend/forms/composables/useFormsList.js";
import { useFormEditor } from "@editorial/backend/forms/composables/useFormEditor.js";
import { useFormFields } from "@editorial/backend/forms/composables/useFormFields.js";
import { useFormSubmissions } from "@editorial/backend/forms/composables/useFormSubmissions.js";
import { useFormSteps } from "@editorial/backend/forms/composables/useFormSteps.js";
import {
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Plus,
    Trash2,
    GripVertical,
    Pencil,
    Download,
    Eye,
    Settings,
    Layers,
    Inbox,
    Save,
    Webhook,
    GitBranch,
    Users,
    X,
} from "lucide-vue-next";
import FormPreviewModal from "@editorial/backend/forms/FormPreviewModal.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppCheckbox from "@/shared/components/form/toggle/AppCheckbox.vue";
import AppSelect from "@/shared/components/form/select/AppSelect.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";
import AppToggle from "@/shared/components/form/toggle/AppToggle.vue";
import { slugify } from "@/shared/utils/format/slugify.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    locales: { type: Array, default: () => ["fr"] },
    listPath: { type: String, required: true },
    getPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    fieldCreatePath: { type: String, required: true },
    fieldUpdatePath: { type: String, required: true },
    fieldDeletePath: { type: String, required: true },
    fieldReorderPath: { type: String, required: true },
    submissionsPath: { type: String, required: true },
    exportPath: { type: String, required: true },
});

const { forms, loading, page, totalPages, total, fetchForms, goToPage } = useFormsList(props.listPath);

const { selectedForm, editingForm, formErrors, saving, activeTab, activeLocale, showDeleteConfirm, deleting, slugLocked, sharedSlug, isCreating, emptyForm, defaultLocale, formTitle, isLocaleFilled, localeFieldError, jsonRequest, startCreate, selectForm, onTitleInput, onSlugInput, onSharedSlugToggle, saveForm, confirmDelete } =
    useFormEditor(props, fetchForms);

const tabs = computed(() => {
    if (isCreating.value) return [{ key: "settings", label: t("backend.forms.tabs.settings"), icon: Settings }];
    return [
        { key: "settings", label: t("backend.forms.tabs.settings"), icon: Settings },
        { key: "fields",   label: t("backend.forms.tabs.fields"),   icon: Layers },
        { key: "submissions", label: t("backend.forms.tabs.submissions"), icon: Inbox },
    ];
});

const { showFieldModal, editingField, editingFieldId, fieldOptionsText, fieldErrors, fieldSaving, fieldActiveLocale, FIELD_TYPES, OPERATORS, fieldHasOptions, fieldTypeLabel, fieldLabel, openAddField, openEditField, submitField, pendingDeleteField, deleteFieldLoading, confirmDeleteField, doDeleteField, onFieldsReordered } =
    useFormFields(props, selectedForm, editingForm, jsonRequest);

const { addStep, removeStep, otherFields, addCondition, removeCondition } = useFormSteps({
    editingForm,
    editingField,
    editingFieldId,
    locales: props.locales,
});

const { submissionFields, viewingSubmission, submissions, submissionsLoading, submissionsPage, submissionsTotalPages, submissionsTotal, fetchSubmissions, goToSubmissionsPage, resetSubmissions, exportCsv, submissionValue, onTabChange: onTabChangeBase } =
    useFormSubmissions(props.submissionsPath, props.exportPath, selectedForm, activeLocale);

function onTabChange(tab) { onTabChangeBase(tab, activeTab); }

const showPreview = ref(false);
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]">
        <div class="lg:w-72 shrink-0 flex flex-col gap-3">
            <AppButton
                v-if="can('editorial.forms.create')"
                variant="primary"
                size="md"
                class="w-full justify-center"
                v-on:click="startCreate"
            >
                <Plus class="w-4 h-4 shrink-0" :stroke-width="2" />
                {{ t("backend.forms.add") }}
            </AppButton>

            <div class="relative space-y-3">
                <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                    <AppNoData v-if="!loading && !forms.length" :message="t('backend.forms.empty')" />
                    <ul v-else class="divide-y divide-line/60">
                        <li
                            v-for="form in forms"
                            :key="form.id"
                            class="px-4 py-3.5 cursor-pointer hover:bg-surface-2/50 active:bg-accent-600/5 transition-colors flex items-start gap-3"
                            :class="selectedForm?.id === form.id ? 'bg-accent-600/10' : ''"
                            v-on:click="selectForm(form)"
                        >
                            <ClipboardList class="w-4 h-4 shrink-0 mt-0.5" :class="selectedForm?.id === form.id ? 'text-accent-400' : 'text-muted'" :stroke-width="2" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium truncate" :class="selectedForm?.id === form.id ? 'text-accent-400' : 'text-primary'">{{ formTitle(form) || "—" }}</p>
                                <p class="text-xs text-muted mt-0.5">{{ form.submissionCount }} {{ t("backend.forms.submissions_count") }}</p>
                            </div>
                            <AppBadge v-if="!form.active" color="gray" class="shrink-0">{{ t("backend.forms.inactive") }}</AppBadge>
                        </li>
                    </ul>
                </div>

                <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />
                <AppLoader :active="loading" />
            </div>
        </div>

        <div v-if="selectedForm || isCreating" class="flex-1 min-w-0 min-h-0 bg-surface border border-line/60 rounded-xl overflow-hidden flex flex-col">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-5 py-4 border-b border-line/60">
                <div class="flex items-center gap-2 min-w-0">
                    <ClipboardList class="w-5 h-5 shrink-0 text-accent-400" :stroke-width="2" />
                    <h2 class="text-base font-semibold text-primary truncate">
                        {{ isCreating ? t("backend.forms.new_form") : (formTitle(selectedForm) || "—") }}
                    </h2>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <AppButton
                        v-if="!isCreating && can('editorial.forms.edit')"
                        variant="danger"
                        size="md"
                        v-on:click="showDeleteConfirm = true"
                    >
                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                    <AppButton
                        variant="secondary"
                        size="md"
                        v-on:click="showPreview = true"
                    >
                        <Eye class="w-4 h-4" :stroke-width="2" />
                        {{ t("backend.forms.preview") }}
                    </AppButton>
                    <AppButton
                        v-if="activeTab === 'settings'"
                        variant="primary"
                        size="md"
                        :disabled="saving"
                        v-on:click="saveForm"
                    >
                        <Save v-if="!saving" class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ saving ? t("shared.common.loading") : t("shared.common.save") }}
                    </AppButton>
                </div>
            </div>

            <div class="flex gap-1 px-5 pt-3 border-b border-line/60">
                <AppTab
                    v-for="tab in tabs"
                    :key="tab.key"
                    variant="underline"
                    size="sm"
                    :active="activeTab === tab.key"
                    v-on:click="onTabChange(tab.key)"
                >
                    <component :is="tab.icon" class="w-4 h-4" :stroke-width="2" />
                    {{ tab.label }}
                </AppTab>
            </div>

            <div v-if="activeTab === 'settings'" class="p-5 space-y-4 overflow-y-auto flex-1">
                <div v-if="locales.length > 1" class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
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
                            <span
                                class="inline-block w-1.5 h-1.5 rounded-full"
                                :class="isLocaleFilled(locale) ? 'bg-emerald-400' : 'bg-muted/40'"
                                :title="isLocaleFilled(locale) ? t('backend.forms.locale_filled') : t('backend.forms.locale_empty')"
                            />
                        </AppTab>
                    </div>
                    <p class="text-xs text-muted">{{ t("backend.forms.locales_optional") }}</p>
                </div>

                <div :class="sharedSlug ? '' : 'grid grid-cols-1 sm:grid-cols-2 gap-4'">
                    <AppInput
                        v-model="editingForm.translations[activeLocale].title"
                        :label="t('backend.forms.title')"
                        :placeholder="t('backend.forms.title_placeholder')"
                        :error="localeFieldError(formErrors, activeLocale, 'title') ?? ''"
                        v-on:update:model-value="onTitleInput"
                    />
                    <AppInput
                        v-if="!sharedSlug"
                        v-model="editingForm.translations[activeLocale].slug"
                        :label="t('backend.forms.slug')"
                        :placeholder="t('backend.forms.slug_placeholder')"
                        :error="localeFieldError(formErrors, activeLocale, 'slug') ?? ''"
                        v-on:update:model-value="onSlugInput"
                    />
                </div>

                <AppTextarea
                    v-model="editingForm.translations[activeLocale].description"
                    :label="t('backend.forms.description')"
                    :placeholder="t('backend.forms.description_placeholder')"
                    :rows="3"
                />

                <hr class="border-line/40">

                <div class="space-y-2">
                    <AppCheckbox
                        v-if="locales.length > 1"
                        v-model="sharedSlug"
                        :label="t('backend.forms.shared_slug')"
                        v-on:update:model-value="onSharedSlugToggle"
                    />
                    <AppInput
                        v-if="sharedSlug"
                        v-model="editingForm.translations[activeLocale].slug"
                        :label="t('backend.forms.slug')"
                        :placeholder="t('backend.forms.slug_placeholder')"
                        :error="localeFieldError(formErrors, activeLocale, 'slug') ?? ''"
                        v-on:update:model-value="onSlugInput"
                    />
                </div>

                <hr class="border-line/40">

                <div class="flex flex-col gap-1">
                    <AppInput
                        v-model="editingForm.notifyEmail"
                        type="email"
                        :label="t('backend.forms.notify_email')"
                        :placeholder="t('backend.forms.notify_email_placeholder')"
                    />
                    <p class="text-xs text-muted">{{ t("backend.forms.notify_email_hint") }}</p>
                </div>

                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-1.5 mb-1">
                        <Webhook class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.webhook_url") }}</span>
                    </div>
                    <AppInput
                        v-model="editingForm.webhookUrl"
                        type="url"
                        :placeholder="t('backend.forms.webhook_url_placeholder')"
                    />
                    <p class="text-xs text-muted">{{ t("backend.forms.webhook_url_hint") }}</p>
                </div>

                <div class="flex items-start gap-3">
                    <AppToggle v-model="editingForm.crmSync" class="mt-0.5" />
                    <div>
                        <p class="text-sm text-primary flex items-center gap-1.5">
                            <Users class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                            {{ t("backend.forms.crm_sync") }}
                        </p>
                        <p class="text-xs text-muted">{{ t("backend.forms.crm_sync_hint") }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <GitBranch class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                            <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.steps") }}</label>
                        </div>
                        <AppButton variant="link-accent" size="none" class="text-xs flex items-center gap-1 self-start sm:self-auto" v-on:click="addStep">
                            <Plus class="w-3 h-3" :stroke-width="2" />
                            {{ t("backend.forms.add_step") }}
                        </AppButton>
                    </div>
                    <p v-if="!editingForm.steps?.length" class="text-xs text-muted">{{ t("backend.forms.steps_empty") }}</p>
                    <div v-for="(step, i) in editingForm.steps" :key="i" class="flex items-center gap-2">
                        <span class="text-xs text-muted w-5 shrink-0 text-center">{{ i + 1 }}</span>
                        <AppInput
                            v-model="editingForm.steps[i][activeLocale]"
                            :placeholder="`${t('backend.forms.step_label')} ${i + 1}`"
                            class="flex-1"
                        />
                        <AppButton variant="ghost" size="none" class="text-muted hover:text-rose-400 shrink-0" v-on:click="removeStep(i)">
                            <X class="w-4 h-4" :stroke-width="2" />
                        </AppButton>
                    </div>
                    <p v-if="editingForm.steps?.length" class="text-xs text-muted">{{ t("backend.forms.steps_hint") }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <AppToggle v-model="editingForm.active" />
                    <span class="text-sm text-primary">{{ t("backend.forms.active") }}</span>
                </div>
            </div>

            <div v-if="activeTab === 'fields'" class="p-5 flex flex-col gap-4 overflow-y-auto flex-1">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-secondary">{{ t("backend.forms.fields_hint") }}</p>
                    <AppButton v-if="can('editorial.forms.edit')" variant="secondary" size="md" v-on:click="openAddField">
                        <Plus class="w-4 h-4" :stroke-width="2" />
                        {{ t("backend.forms.add_field") }}
                    </AppButton>
                </div>

                <div v-if="editingForm.fields.length === 0" class="py-10 text-center text-sm text-muted">
                    {{ t("backend.forms.fields_empty") }}
                </div>

                <VueDraggable
                    v-else
                    v-model="editingForm.fields"
                    handle=".drag-handle"
                    :animation="150"
                    class="space-y-2"
                    v-on:end="onFieldsReordered"
                >
                    <div
                        v-for="field in editingForm.fields"
                        :key="field.id"
                        class="flex items-center gap-3 px-4 py-3 bg-surface hover:bg-surface-2/50 border border-line/60 rounded-lg transition-colors"
                    >
                        <GripVertical class="drag-handle w-4 h-4 text-muted cursor-grab active:cursor-grabbing shrink-0" :stroke-width="2" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium text-primary">{{ fieldLabel(field) || "—" }}</span>
                                <AppBadge v-if="field.required" color="amber">{{ t("backend.forms.required") }}</AppBadge>
                                <AppBadge color="accent">{{ fieldTypeLabel(field.type) }}</AppBadge>
                            </div>
                        </div>
                        <AppIconButton v-if="can('editorial.forms.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEditField(field)">
                            <Pencil class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton v-if="can('editorial.forms.edit')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDeleteField(field)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </VueDraggable>
            </div>

            <div v-if="activeTab === 'submissions'" class="flex flex-col gap-4 overflow-y-auto flex-1">
                <div class="flex items-center justify-between px-5 pt-5">
                    <p class="text-sm text-secondary">{{ submissionsTotal }} {{ t("backend.forms.submissions_count") }}</p>
                    <AppButton variant="secondary" size="md" :disabled="!submissions.length" v-on:click="exportCsv">
                        <Download class="w-4 h-4" :stroke-width="2" />
                        {{ t("backend.forms.export_csv") }}
                    </AppButton>
                </div>

                <div class="bg-surface border-t border-line/60 overflow-hidden">
                    <AppNoData v-if="!submissionsLoading && !submissions.length" :message="t('backend.forms.submissions_empty')" />
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-surface-2/50 border-b border-line/40">
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted whitespace-nowrap">{{ t("backend.forms.submitted_at") }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted whitespace-nowrap">{{ t("backend.forms.locale") }}</th>
                                    <th v-for="field in submissionFields" :key="field.id" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted whitespace-nowrap max-w-xs">{{ fieldLabel(field) }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.edit") }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/40">
                                <tr v-for="submission in submissions" :key="submission.id" class="group hover:bg-surface-2/40 transition-colors">
                                    <td class="px-4 py-3 text-xs text-muted whitespace-nowrap">{{ formatDateTime(submission.submittedAt) }}</td>
                                    <td class="px-4 py-3 text-xs text-muted whitespace-nowrap uppercase">{{ submission.locale }}</td>
                                    <td v-for="field in submissionFields" :key="field.id" class="px-4 py-3 text-sm text-secondary max-w-xs truncate">
                                        {{ submissionValue(submission, field) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <AppIconButton color="accent" :title="t('backend.forms.view_submission')" v-on:click="viewingSubmission = submission">
                                            <Pencil class="w-4 h-4" :stroke-width="2" />
                                        </AppIconButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-5 pb-5">
                    <AppPagination :page="submissionsPage" :total-pages="submissionsTotalPages" v-on:change="goToSubmissionsPage" />
                </div>
            </div>
        </div>

        <div v-else class="flex-1 hidden lg:flex items-center justify-center text-sm text-muted bg-surface border border-line/60 rounded-xl min-h-48">
            {{ t("backend.forms.select_or_create") }}
        </div>
    </div>

    <AppModal
        :show="showFieldModal"
        max-width="sm"
        :title="editingFieldId !== null ? t('backend.forms.edit_field') : t('backend.forms.add_field_title')"
        :icon="editingFieldId !== null ? Pencil : ClipboardList"
        :closeable="false"
        v-on:close="showFieldModal = false"
    >
        <div class="space-y-4">
            <AppSelect v-model="editingField.type" :label="t('backend.forms.field_type')">
                <option v-for="ft in FIELD_TYPES" :key="ft.value" :value="ft.value">{{ ft.label }}</option>
            </AppSelect>

            <AppCheckbox v-model="editingField.required" :label="t('backend.forms.field_required')" />

            <AppSelect
                v-if="editingForm.steps?.length"
                v-model="editingField.step"
                :label="t('backend.forms.field_step')"
            >
                <option :value="null">{{ t('backend.forms.field_step_none') }}</option>
                <option v-for="(step, i) in editingForm.steps" :key="i" :value="i">
                    {{ step[activeLocale] || step[props.locales[0]] || `${t('backend.forms.step_label')} ${i + 1}` }}
                </option>
            </AppSelect>

            <div v-if="otherFields.length" class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t("backend.forms.conditions") }}</label>
                    <AppButton variant="link-accent" size="none" class="text-xs flex items-center gap-1" v-on:click="addCondition">
                        <Plus class="w-3 h-3" :stroke-width="2" /> {{ t("backend.forms.add_condition") }}
                    </AppButton>
                </div>
                <div v-if="editingField.conditions?.length > 1" class="flex items-center gap-2">
                    <span class="text-xs text-muted">{{ t("backend.forms.conditions_logic_label") }}</span>
                    <AppButton
                        v-for="logic in ['and', 'or']"
                        :key="logic"
                        variant="ghost"
                        size="none"
                        class="px-2 py-0.5 text-xs rounded-md border transition-colors"
                        :class="editingField.conditionsLogic === logic ? 'bg-accent-600 border-accent-600 text-white' : 'border-line text-secondary hover:border-accent-400'"
                        v-on:click="editingField.conditionsLogic = logic"
                    >
                        {{ t(`backend.forms.conditionsLogic.${logic}`) }}
                    </AppButton>
                </div>
                <div v-for="(condition, i) in editingField.conditions" :key="i" class="flex items-end gap-1.5 flex-wrap">
                    <AppSelect v-model="condition.fieldId" class="flex-1 min-w-28">
                        <option v-for="f in otherFields" :key="f.id" :value="f.id">{{ fieldLabel(f) || f.id }}</option>
                    </AppSelect>
                    <AppSelect v-model="condition.operator" class="w-32">
                        <option v-for="op in OPERATORS" :key="op.value" :value="op.value">{{ op.label }}</option>
                    </AppSelect>
                    <AppInput
                        v-if="!['empty', 'not_empty'].includes(condition.operator)"
                        v-model="condition.value"
                        :placeholder="t('backend.forms.condition_value')"
                        class="flex-1 min-w-20"
                    />
                    <AppButton variant="ghost" size="none" class="text-muted hover:text-rose-400 mb-1.5" v-on:click="removeCondition(i)">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                    </AppButton>
                </div>
                <p v-if="!editingField.conditions?.length" class="text-xs text-muted">{{ t("backend.forms.conditions_empty") }}</p>
            </div>

            <hr class="border-line/40">

            <div v-if="locales.length > 1" class="flex gap-1">
                <AppTab
                    v-for="locale in locales"
                    :key="locale"
                    size="xs"
                    :active="fieldActiveLocale === locale"
                    active-class="bg-accent-600 text-white"
                    inactive-class="bg-surface-2 text-secondary hover:bg-surface-3"
                    v-on:click="fieldActiveLocale = locale"
                >
                    {{ locale.toUpperCase() }}
                    <span
                        class="inline-block w-1.5 h-1.5 rounded-full"
                        :class="editingField.translations[locale]?.label?.trim() ? 'bg-emerald-400' : 'bg-muted/40'"
                    />
                </AppTab>
            </div>

            <AppInput
                v-model="editingField.translations[fieldActiveLocale].label"
                :label="t('backend.forms.field_label')"
                :placeholder="t('backend.forms.field_label_placeholder')"
                :error="localeFieldError(fieldErrors, fieldActiveLocale, 'label') ?? ''"
            />

            <AppInput
                v-if="!fieldHasOptions"
                v-model="editingField.translations[fieldActiveLocale].placeholder"
                :label="t('backend.forms.field_placeholder')"
                :placeholder="t('backend.forms.field_placeholder_placeholder')"
            />

            <div v-if="fieldHasOptions" class="flex flex-col gap-1">
                <AppTextarea
                    v-model="fieldOptionsText[fieldActiveLocale]"
                    :label="t('backend.forms.field_options')"
                    :placeholder="t('backend.forms.field_options_placeholder')"
                    :rows="4"
                    :mono="true"
                />
                <p class="text-xs text-muted">{{ t("backend.forms.field_options_hint") }}</p>
            </div>
        </div>

        <template #footer>
            <AppModalFooter bordered>
                <AppButton variant="ghost" size="md" v-on:click="showFieldModal = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="primary" size="md" :disabled="fieldSaving" v-on:click="submitField"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
            </AppModalFooter>
        </template>
    </AppModal>

    <AppModal
        :show="showDeleteConfirm"
        max-width="sm"
        :title="t('backend.forms.delete_confirm_title')"
        :icon="Trash2"
        :closeable="false"
        v-on:close="showDeleteConfirm = false"
    >
        <p class="text-sm text-secondary">{{ t("backend.forms.delete_confirm_body", { title: formTitle(selectedForm) }) }}</p>
        <template #footer>
            <AppModalFooter bordered>
                <AppButton variant="ghost" size="md" v-on:click="showDeleteConfirm = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" :disabled="deleting" v-on:click="confirmDelete">
                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>

    <AppModal
        :show="!!viewingSubmission"
        max-width="md"
        :title="t('backend.forms.view_submission')"
        :closeable="false"
        v-on:close="viewingSubmission = null"
    >
        <div class="space-y-3">
            <div class="flex flex-col gap-1">
                <label class="text-xs text-secondary uppercase tracking-wide">{{ t("backend.forms.submitted_at") }}</label>
                <p class="text-sm text-muted">{{ viewingSubmission ? formatDateTime(viewingSubmission.submittedAt) : "" }}</p>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs text-secondary uppercase tracking-wide">{{ t("backend.forms.locale") }}</label>
                <p class="text-sm text-muted uppercase">{{ viewingSubmission?.locale }}</p>
            </div>
            <div v-for="field in submissionFields" :key="field.id" class="flex flex-col gap-1">
                <label class="text-xs text-secondary uppercase tracking-wide">{{ fieldLabel(field) }}</label>
                <p class="text-sm text-primary whitespace-pre-wrap bg-surface-2 rounded px-3 py-2">
                    {{ submissionValue(viewingSubmission, field) }}
                </p>
            </div>
        </div>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="viewingSubmission = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
            </AppModalFooter>
        </template>
    </AppModal>

    <FormPreviewModal
        :show="showPreview"
        :fields="editingForm.fields"
        :steps="editingForm.steps ?? []"
        :form-title="formTitle(editingForm)"
        :form-description="editingForm.translations?.[activeLocale]?.description ?? ''"
        :active-locale="activeLocale"
        :default-locale="defaultLocale()"
        v-on:close="showPreview = false"
    />

    <AppModal
        :show="!!pendingDeleteField"
        max-width="sm"
        :closeable="false"
        :title="t('shared.common.delete')"
        :icon="Trash2"
        v-on:close="pendingDeleteField = null"
    >
        <p class="text-sm text-primary">{{ t('backend.forms.delete_field_confirm', { label: pendingDeleteField ? fieldLabel(pendingDeleteField) : '' }) }}</p>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDeleteField = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteFieldLoading" v-on:click="doDeleteField"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
