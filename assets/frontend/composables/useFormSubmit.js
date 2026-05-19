import { ref } from "vue";
import { useRequest } from "@/shared/composables/http/frontend/useRequest.js";

export function useFormSubmit(
    submitPath,
    visibleFields,
    currentStepFields,
    formData,
    errors,
    isMultiStep,
    fieldsForStep,
    totalSteps,
    currentStep,
    validateStep,
) {
    const { loading: submitting, request } = useRequest();
    const submitted = ref(false);

    async function handleSubmit() {
        Object.keys(errors).forEach((k) => delete errors[k]);
        if (!validateStep(currentStepFields.value)) return;

        const payload = {};
        for (const field of visibleFields.value) {
            payload[field.id] = formData[field.id];
        }

        const data = await request(submitPath, payload);
        if (!data) return;

        if (data.success) {
            submitted.value = true;
        } else if (data.errors) {
            Object.assign(errors, data.errors);
            if (isMultiStep.value) {
                for (let s = 0; s < totalSteps.value; s++) {
                    if (fieldsForStep(s).some((f) => errors[f.id])) {
                        currentStep.value = s;
                        break;
                    }
                }
            }
        }
    }

    return { submitting, submitted, handleSubmit };
}
