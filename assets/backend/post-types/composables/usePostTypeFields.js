import { ref, watch } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";
import { PostFieldType } from "@editorial/shared/enums/postFieldType.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

const EMPTY_FIELD = {
    name: "",
    label: "",
    type: "text",
    required: false,
    translatable: false,
    choicesText: "",
    referencePostTypeId: null,
    referenceMultiple: false,
};

function fieldFromEntity(f) {
    const options = f.options ?? {};
    return {
        name: f.name,
        label: f.label,
        type: f.type,
        required: f.required,
        translatable: f.translatable,
        choicesText: (options.choices ?? [])
            .map((c) => `${c.value}|${c.label}`)
            .join("\n"),
        referencePostTypeId: options.postTypeId ?? null,
        referenceMultiple: options.multiple ?? false,
    };
}

function buildFieldOptions(form) {
    if (form.type === PostFieldType.Select) {
        return {
            choices: form.choicesText
                .split("\n")
                .map((l) => l.trim())
                .filter(Boolean)
                .map((l) => {
                    const [value, ...rest] = l.split("|");
                    return {
                        value: value.trim(),
                        label: rest.join("|").trim() || value.trim(),
                    };
                }),
        };
    }
    if (form.type === PostFieldType.Reference) {
        const options = { multiple: form.referenceMultiple };
        if (form.referencePostTypeId)
            options.postTypeId = Number(form.referencePostTypeId);
        return options;
    }
    return {};
}

export function usePostTypeFields(props, selected, replacePostType) {
    const { t } = useI18n();
    const { request: deleteFieldRequest } = useRequest();
    const { request: reorderRequest } = useRequest();

    const {
        modal: fieldModal,
        form: fieldForm,
        openCreate,
        openEdit,
        submit: submitField,
    } = useFormModal({
        empty: () => ({ ...EMPTY_FIELD }),
        fromEntity: fieldFromEntity,
        createUrl: () =>
            buildPath(props.fieldCreatePath, { id: selected.value.id }),
        editUrl: (field) =>
            buildPath(props.fieldEditPath, {
                id: selected.value.id,
                fieldId: field.id,
            }),
        buildBody: (form) => ({
            name: form.name,
            label: form.label,
            type: form.type,
            required: form.required,
            translatable: form.translatable,
            options: buildFieldOptions(form),
        }),
        onSuccess: ({ data }) => replacePostType(data.postType),
    });

    function openCreateField() {
        if (selected.value) openCreate();
    }
    function openEditField(field) {
        openEdit(field);
    }

    const deletingField = ref(null);
    async function confirmDeleteField() {
        const field = deletingField.value;
        if (!field || !selected.value) return;
        try {
            const url = buildPath(props.fieldDeletePath, {
                id: selected.value.id,
                fieldId: field.id,
            });
            const data = await deleteFieldRequest(url);
            if (!data?.success) {
                toast.error(t("shared.common.error"));
                return;
            }
            replacePostType(data.postType);
            toast.success(t("shared.common.deleted"));
        } finally {
            deletingField.value = null;
        }
    }

    const orderedFields = ref([]);
    watch(
        () => selected.value?.fields,
        (fields) => {
            orderedFields.value = [...(fields ?? [])].sort(
                (a, b) => a.position - b.position,
            );
        },
        { immediate: true, deep: true },
    );

    async function persistFieldOrder() {
        if (!selected.value) return;
        const data = await reorderRequest(
            buildPath(props.fieldReorderPath, { id: selected.value.id }),
            { orderedIds: orderedFields.value.map((f) => f.id) },
        );
        if (!data) return;
        if (!data.success) toast.error(t("shared.common.error"));
        else replacePostType(data.postType);
    }

    return {
        fieldModal,
        fieldForm,
        openCreateField,
        openEditField,
        submitField,
        deletingField,
        confirmDeleteField,
        orderedFields,
        persistFieldOrder,
    };
}
