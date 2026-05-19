import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";

const SUPPORTS = ["blocks", "thumbnail", "excerpt"];

export function usePostTypeModal(
    props,
    postTypes,
    selectedId,
    replacePostType,
) {
    const {
        modal,
        form,
        errors: postTypeErrors,
        loading: postTypeLoading,
        openCreate,
        openEdit,
        submit,
    } = useFormModal({
        empty: () => ({
            slug: "",
            label: "",
            icon: "",
            hasArchive: false,
            supports: [...SUPPORTS],
            taxonomyIds: [],
        }),
        fromEntity: (pt) => ({
            slug: pt.slug,
            label: pt.label,
            icon: pt.icon ?? "",
            hasArchive: pt.hasArchive,
            supports: [...(pt.supports ?? [])],
            taxonomyIds: [...(pt.taxonomyIds ?? [])],
        }),
        createUrl: () => props.createPath,
        editUrl: (pt) => buildPath(props.updatePath, { id: pt.id }),
        onSuccess: ({ data }) => {
            replacePostType(data.postType);
            selectedId.value = data.postType.id;
        },
    });

    function toggleIn(list, value) {
        return list.includes(value)
            ? list.filter((item) => item !== value)
            : [...list, value];
    }

    return {
        postTypeModal: modal,
        form,
        postTypeErrors,
        postTypeLoading,
        openCreatePostType: openCreate,
        openEditPostType: openEdit,
        submitPostType: submit,
        toggleIn,
    };
}
