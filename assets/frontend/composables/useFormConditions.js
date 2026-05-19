import { computed } from "vue";

export function useFormConditions(fields, formData) {
    function evaluateCondition(condition) {
        const value = formData[condition.fieldId];
        const str = Array.isArray(value)
            ? value.join(",")
            : String(value ?? "");
        switch (condition.operator) {
            case "eq":
                return str === String(condition.value ?? "");
            case "neq":
                return str !== String(condition.value ?? "");
            case "contains":
                return str.includes(String(condition.value ?? ""));
            case "not_empty":
                return str.trim() !== "";
            case "empty":
                return str.trim() === "";
            default:
                return true;
        }
    }

    function isFieldVisible(field) {
        if (!field.conditions?.length) return true;
        const results = field.conditions.map(evaluateCondition);
        return field.conditionsLogic === "or"
            ? results.some(Boolean)
            : results.every(Boolean);
    }

    const visibleFields = computed(() => fields.filter(isFieldVisible));

    return { visibleFields };
}
