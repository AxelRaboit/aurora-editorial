import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";

export function useFormSteps(steps, visibleFields, formData, errors) {
    const { t } = useI18n();

    const isMultiStep = computed(() => steps?.length > 0);
    const currentStep = ref(0);
    const totalSteps = computed(() => steps?.length ?? 1);

    function fieldsForStep(stepIndex) {
        return visibleFields.value.filter(
            (f) => (f.step ?? null) === stepIndex,
        );
    }

    const currentStepFields = computed(() =>
        isMultiStep.value
            ? fieldsForStep(currentStep.value)
            : visibleFields.value,
    );

    function isLastStep() {
        return !isMultiStep.value || currentStep.value === totalSteps.value - 1;
    }

    function validateStep(fields) {
        let valid = true;
        for (const field of fields) {
            const value = formData[field.id];
            const isEmpty =
                value === false
                    ? true
                    : Array.isArray(value)
                      ? value.length === 0
                      : String(value ?? "").trim() === "";
            if (field.required && isEmpty) {
                errors[field.id] = t("shared.form.fieldRequired");
                valid = false;
            }
        }
        return valid;
    }

    function nextStep() {
        Object.keys(errors).forEach((k) => delete errors[k]);
        if (!validateStep(currentStepFields.value)) return;
        if (currentStep.value < totalSteps.value - 1) currentStep.value++;
    }

    function prevStep() {
        if (currentStep.value > 0) currentStep.value--;
    }

    return {
        isMultiStep,
        currentStep,
        totalSteps,
        fieldsForStep,
        currentStepFields,
        isLastStep,
        validateStep,
        nextStep,
        prevStep,
    };
}
