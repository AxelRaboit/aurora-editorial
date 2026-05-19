<script setup>
import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";
import AppSelect from "@/shared/components/form/select/AppSelect.vue";
import AppDatePicker from "@/shared/components/form/picker/AppDatePicker.vue";
import AppCheckbox from "@/shared/components/form/toggle/AppCheckbox.vue";
import AppFieldLabel from "@/shared/components/form/AppFieldLabel.vue";
import { useFormData } from "@editorial/frontend/composables/useFormData.js";
import { useFormConditions } from "@editorial/frontend/composables/useFormConditions.js";
import { useFormSteps } from "@editorial/frontend/composables/useFormSteps.js";
import { useFormSubmit } from "@editorial/frontend/composables/useFormSubmit.js";

const { t } = useI18n();

const props = defineProps({
    submitPath: { type: String, required: true },
    formTitle: { type: String, default: "" },
    formDescription: { type: String, default: null },
    fields: { type: Array, default: () => [] },
    steps: { type: Array, default: () => [] },
});

// errors is shared — useFormSteps writes validation errors, useFormSubmit writes server errors
const errors = reactive({});

const { formData, isChecked, toggleCheckbox } = useFormData(props.fields);
const { visibleFields } = useFormConditions(props.fields, formData);
const { isMultiStep, currentStep, totalSteps, fieldsForStep, currentStepFields, isLastStep, validateStep, nextStep, prevStep } =
    useFormSteps(props.steps, visibleFields, formData, errors);
const { submitting, submitted, handleSubmit } =
    useFormSubmit(props.submitPath, visibleFields, currentStepFields, formData, errors, isMultiStep, fieldsForStep, totalSteps, currentStep, validateStep);
</script>

<template>
    <div class="max-w-2xl mx-auto space-y-6">
        <div v-if="submitted" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-emerald-400 text-sm font-medium">
            {{ t("shared.form.success") }}
        </div>

        <template v-else>
            <div v-if="formTitle || formDescription" class="space-y-1">
                <h1 v-if="formTitle" class="text-2xl font-bold text-primary">{{ formTitle }}</h1>
                <p v-if="formDescription" class="text-secondary text-sm">{{ formDescription }}</p>
            </div>

            <div v-if="isMultiStep" class="space-y-2">
                <div class="flex items-center justify-between text-xs text-muted">
                    <span>{{ steps[currentStep] }}</span>
                    <span>{{ currentStep + 1 }} / {{ totalSteps }}</span>
                </div>
                <div class="h-1.5 bg-surface-2 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        style="background-color: var(--th-accent);"
                        :style="{ width: `${((currentStep + 1) / totalSteps) * 100}%` }"
                    />
                </div>
            </div>

            <p v-if="errors['_global']" class="text-sm text-rose-400">{{ errors["_global"] }}</p>

            <form class="space-y-5" v-on:submit.prevent="isLastStep() ? handleSubmit() : nextStep()">
                <template v-for="field in currentStepFields" :key="field.id">
                    <AppTextarea
                        v-if="field.type === 'textarea'"
                        v-model="formData[field.id]"
                        :label="field.label"
                        :placeholder="field.placeholder ?? ''"
                        :required="field.required"
                        :error="errors[field.id] ?? ''"
                        :rows="4"
                    />

                    <AppSelect
                        v-else-if="field.type === 'select'"
                        v-model="formData[field.id]"
                        :label="field.label"
                        :placeholder="t('shared.form.selectPlaceholder')"
                        :required="field.required"
                        :error="errors[field.id] ?? ''"
                    >
                        <option v-for="option in field.options" :key="option" :value="option">{{ option }}</option>
                    </AppSelect>

                    <div v-else-if="field.type === 'radio'">
                        <AppFieldLabel :label="field.label" :required="field.required" />
                        <div class="mt-1.5 space-y-1.5">
                            <label v-for="option in field.options" :key="option" class="flex items-center gap-2 text-sm text-primary cursor-pointer">
                                <input
                                    v-model="formData[field.id]"
                                    type="radio"
                                    :name="`field-${field.id}`"
                                    :value="option"
                                    class="text-[--th-accent] focus:ring-[--th-accent]"
                                >
                                {{ option }}
                            </label>
                        </div>
                        <p v-if="errors[field.id]" class="mt-1 text-xs text-rose-400">{{ errors[field.id] }}</p>
                    </div>

                    <div v-else-if="field.type === 'checkbox'">
                        <AppCheckbox
                            v-if="!field.options?.length"
                            v-model="formData[field.id]"
                        >
                            {{ field.label }}<span v-if="field.required" class="ml-0.5 text-rose-400">*</span>
                        </AppCheckbox>
                        <template v-else>
                            <AppFieldLabel :label="field.label" :required="field.required" />
                            <div class="mt-1.5 space-y-1.5">
                                <AppCheckbox
                                    v-for="option in field.options"
                                    :key="option"
                                    :label="option"
                                    :model-value="isChecked(field.id, option)"
                                    v-on:update:model-value="toggleCheckbox(field.id, option)"
                                />
                            </div>
                        </template>
                        <p v-if="errors[field.id]" class="mt-1 text-xs text-rose-400">{{ errors[field.id] }}</p>
                    </div>

                    <AppDatePicker
                        v-else-if="field.type === 'date'"
                        v-model="formData[field.id]"
                        :label="field.label"
                        :required="field.required"
                        :error="errors[field.id] ?? ''"
                    />

                    <AppInput
                        v-else
                        v-model="formData[field.id]"
                        :type="field.type"
                        :label="field.label"
                        :placeholder="field.placeholder ?? ''"
                        :required="field.required"
                        :error="errors[field.id] ?? ''"
                    />
                </template>

                <div class="flex items-center gap-3" :class="isMultiStep && currentStep > 0 ? 'justify-between' : 'justify-end'">
                    <AppButton
                        v-if="isMultiStep && currentStep > 0"
                        type="button"
                        variant="secondary"
                        size="md"
                        v-on:click="prevStep"
                    >
                        <ChevronLeft class="w-4 h-4" :stroke-width="2" />
                        {{ t("shared.form.prev") }}
                    </AppButton>
                    <AppButton
                        type="submit"
                        variant="primary"
                        size="md"
                        :style="{ backgroundColor: 'var(--th-accent)' }"
                        :disabled="submitting"
                    >
                        <template v-if="submitting">{{ t("shared.form.submitting") }}</template>
                        <template v-else-if="!isLastStep()">
                            {{ t("shared.form.next") }}
                            <ChevronRight class="w-4 h-4" :stroke-width="2" />
                        </template>
                        <template v-else>{{ t("shared.form.submit") }}</template>
                    </AppButton>
                </div>
            </form>
        </template>
    </div>
</template>
