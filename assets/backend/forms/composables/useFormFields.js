import { ref, computed } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { FormFieldType } from "@editorial/shared/enums/formFieldType.js";

export function useFormFields(props, selectedForm, editingForm, jsonRequest) {
    const { t } = useI18n();

    const showFieldModal = ref(false);
    const editingFieldId = ref(null);
    const fieldOptionsText = ref({});
    const fieldErrors = ref({});
    const fieldSaving = ref(false);
    const fieldActiveLocale = ref(props.locales[0] ?? "fr");

    const FIELD_TYPES = computed(() => [
        { value: "text", label: t("backend.forms.field_types.text") },
        { value: "email", label: t("backend.forms.field_types.email") },
        { value: "textarea", label: t("backend.forms.field_types.textarea") },
        { value: "number", label: t("backend.forms.field_types.number") },
        { value: "tel", label: t("backend.forms.field_types.tel") },
        { value: "date", label: t("backend.forms.field_types.date") },
        { value: "select", label: t("backend.forms.field_types.select") },
        { value: "radio", label: t("backend.forms.field_types.radio") },
        { value: "checkbox", label: t("backend.forms.field_types.checkbox") },
    ]);

    function emptyFieldTranslations() {
        return Object.fromEntries(
            props.locales.map((l) => [
                l,
                { label: "", placeholder: "", options: [] },
            ]),
        );
    }

    const OPERATORS = computed(() => [
        { value: "eq", label: t("backend.forms.operators.eq") },
        { value: "neq", label: t("backend.forms.operators.neq") },
        { value: "contains", label: t("backend.forms.operators.contains") },
        { value: "not_empty", label: t("backend.forms.operators.not_empty") },
        { value: "empty", label: t("backend.forms.operators.empty") },
    ]);

    const editingField = ref({
        type: "text",
        required: false,
        step: null,
        conditions: [],
        conditionsLogic: "and",
        translations: emptyFieldTranslations(),
    });

    const fieldHasOptions = computed(() =>
        [
            FormFieldType.Select,
            FormFieldType.Radio,
            FormFieldType.Checkbox,
        ].includes(editingField.value.type),
    );

    function fieldTypeLabel(type) {
        return FIELD_TYPES.value.find((ft) => ft.value === type)?.label ?? type;
    }

    function fieldLabel(field, locale) {
        return (
            field?.translations?.[locale]?.label ??
            field?.translations?.[props.locales[0]]?.label ??
            ""
        );
    }

    function openAddField() {
        editingFieldId.value = null;
        editingField.value = {
            type: "text",
            required: false,
            step: null,
            conditions: [],
            conditionsLogic: "and",
            translations: emptyFieldTranslations(),
        };
        fieldOptionsText.value = Object.fromEntries(
            props.locales.map((l) => [l, ""]),
        );
        fieldErrors.value = {};
        fieldActiveLocale.value = props.locales[0] ?? "fr";
        showFieldModal.value = true;
    }

    function openEditField(field) {
        editingFieldId.value = field.id;
        const translations = emptyFieldTranslations();
        for (const [locale, data] of Object.entries(field.translations ?? {})) {
            translations[locale] = {
                label: data.label ?? "",
                placeholder: data.placeholder ?? "",
                options: [...(data.options ?? [])],
            };
        }
        editingField.value = {
            type: field.type,
            required: field.required,
            step: field.step ?? null,
            conditions: field.conditions
                ? field.conditions.map((c) => ({ ...c }))
                : [],
            conditionsLogic: field.conditionsLogic ?? "and",
            translations,
        };
        fieldOptionsText.value = Object.fromEntries(
            props.locales.map((l) => [
                l,
                (translations[l]?.options ?? []).join("\n"),
            ]),
        );
        fieldErrors.value = {};
        fieldActiveLocale.value = props.locales[0] ?? "fr";
        showFieldModal.value = true;
    }

    async function submitField() {
        if (!selectedForm.value || fieldSaving.value) return;
        fieldErrors.value = {};
        fieldSaving.value = true;
        const translations = {};
        for (const locale of props.locales) {
            const trans = editingField.value.translations[locale] ?? {
                label: "",
                placeholder: "",
                options: [],
            };
            const options = fieldHasOptions.value
                ? (fieldOptionsText.value[locale] ?? "")
                      .split("\n")
                      .map((s) => s.trim())
                      .filter(Boolean)
                : [];
            translations[locale] = {
                label: trans.label,
                placeholder: trans.placeholder || null,
                options,
            };
        }
        const payload = {
            type: editingField.value.type,
            required: editingField.value.required,
            step: editingField.value.step,
            conditions: editingField.value.conditions?.length
                ? editingField.value.conditions
                : null,
            conditionsLogic: editingField.value.conditionsLogic,
            translations,
        };
        const isUpdate = editingFieldId.value !== null;
        const url = isUpdate
            ? buildPath(props.fieldUpdatePath, {
                  id: selectedForm.value.id,
                  fieldId: editingFieldId.value,
              })
            : buildPath(props.fieldCreatePath, { id: selectedForm.value.id });
        try {
            const data = await jsonRequest(url, {
                method: HttpMethod.Post,
                body: JSON.stringify(payload),
            });
            if (data.success) {
                toast.success(t("shared.common.saved"));
                if (isUpdate) {
                    const index = editingForm.value.fields.findIndex(
                        (f) => f.id === editingFieldId.value,
                    );
                    if (index !== -1)
                        editingForm.value.fields[index] = data.field;
                } else editingForm.value.fields.push(data.field);
                showFieldModal.value = false;
            } else if (data.errors) {
                fieldErrors.value = data.errors;
                const firstErrorLocale = Object.keys(data.errors)
                    .find((k) => k.startsWith("translations."))
                    ?.split(".")[1];
                if (firstErrorLocale)
                    fieldActiveLocale.value = firstErrorLocale;
            } else toast.error(t("shared.common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            fieldSaving.value = false;
        }
    }

    const pendingDeleteField = ref(null);
    const deleteFieldLoading = ref(false);

    function confirmDeleteField(field) {
        pendingDeleteField.value = field;
    }

    async function doDeleteField() {
        if (
            !selectedForm.value ||
            !pendingDeleteField.value ||
            deleteFieldLoading.value
        )
            return;
        deleteFieldLoading.value = true;
        const field = pendingDeleteField.value;
        const url = buildPath(props.fieldDeletePath, {
            id: selectedForm.value.id,
            fieldId: field.id,
        });
        try {
            const data = await jsonRequest(url, { method: HttpMethod.Post });
            if (data.success) {
                toast.success(t("shared.common.deleted"));
                editingForm.value.fields = editingForm.value.fields.filter(
                    (f) => f.id !== field.id,
                );
                pendingDeleteField.value = null;
            } else toast.error(t("shared.common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            deleteFieldLoading.value = false;
        }
    }

    async function onFieldsReordered() {
        if (!selectedForm.value) return;
        const orderedIds = editingForm.value.fields.map((f) => f.id);
        const url = buildPath(props.fieldReorderPath, {
            id: selectedForm.value.id,
        });
        try {
            const data = await jsonRequest(url, {
                method: HttpMethod.Post,
                body: JSON.stringify({ orderedIds }),
            });
            if (!data.success) toast.error(t("shared.common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    return {
        showFieldModal,
        editingField,
        editingFieldId,
        fieldOptionsText,
        fieldErrors,
        fieldSaving,
        fieldActiveLocale,
        FIELD_TYPES,
        OPERATORS,
        fieldHasOptions,
        fieldTypeLabel,
        fieldLabel,
        openAddField,
        openEditField,
        submitField,
        pendingDeleteField,
        deleteFieldLoading,
        confirmDeleteField,
        doDeleteField,
        onFieldsReordered,
    };
}
