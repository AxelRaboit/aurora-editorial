import { reactive } from "vue";
import { FormFieldType } from "@editorial/shared/enums/formFieldType.js";

export function useFormData(fields) {
    const formData = reactive({});

    for (const field of fields) {
        if (field.type === FormFieldType.Checkbox) {
            formData[field.id] = field.options?.length ? [] : false;
        } else {
            formData[field.id] = "";
        }
    }

    function isChecked(fieldId, option) {
        return (
            Array.isArray(formData[fieldId]) &&
            formData[fieldId].includes(option)
        );
    }

    function toggleCheckbox(fieldId, option) {
        if (!Array.isArray(formData[fieldId])) formData[fieldId] = [];
        const index = formData[fieldId].indexOf(option);
        if (index === -1) {
            formData[fieldId].push(option);
        } else {
            formData[fieldId].splice(index, 1);
        }
    }

    return { formData, isChecked, toggleCheckbox };
}
