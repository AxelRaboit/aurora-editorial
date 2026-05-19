import { computed } from "vue";

/**
 * Steps + Conditions helpers for the form editor.
 *
 * - `addStep()` / `removeStep(index)` mutate `editingForm.value.steps`.
 *   Each new step is initialized with an empty string per locale.
 * - `otherFields` is the list of fields except the currently edited
 *   one (a field can't reference itself in a condition).
 * - `addCondition()` / `removeCondition(index)` mutate
 *   `editingField.value.conditions`. New conditions default to the
 *   first available `otherFields` entry, operator "eq", value "".
 *
 * Keeps `FormsApp.vue` free of the array-mutation plumbing while
 * leaving the form refs as the single source of truth.
 *
 * @param {object} options
 * @param {import('vue').Ref} options.editingForm  - the form being edited
 * @param {import('vue').Ref} options.editingField - the field whose conditions are edited
 * @param {import('vue').Ref<number>} options.editingFieldId - id of the field above (for self-exclusion)
 * @param {string[]} options.locales
 */
export function useFormSteps({
    editingForm,
    editingField,
    editingFieldId,
    locales,
}) {
    function addStep() {
        const step = Object.fromEntries(locales.map((l) => [l, ""]));
        editingForm.value.steps = [...(editingForm.value.steps ?? []), step];
    }

    function removeStep(index) {
        editingForm.value.steps = editingForm.value.steps.filter(
            (_, i) => i !== index,
        );
    }

    const otherFields = computed(() =>
        (editingForm.value.fields ?? []).filter(
            (f) => f.id !== editingFieldId.value,
        ),
    );

    function addCondition() {
        const firstField = otherFields.value[0];
        editingField.value.conditions = [
            ...(editingField.value.conditions ?? []),
            { fieldId: firstField?.id ?? null, operator: "eq", value: "" },
        ];
    }

    function removeCondition(index) {
        editingField.value.conditions = editingField.value.conditions.filter(
            (_, i) => i !== index,
        );
    }

    return { addStep, removeStep, otherFields, addCondition, removeCondition };
}
