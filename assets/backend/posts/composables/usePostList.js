import { ref } from "vue";
import { usePaginatedFetch } from "@/shared/composables/http/backend/usePaginatedFetch.js";

export function usePostList(
    postsPath,
    initialPosts,
    initialSearch,
    getExtraParams = () => ({}),
) {
    const search = ref(initialSearch ?? "");

    const {
        items: posts,
        page,
        totalPages,
        total,
        loading,
        load,
        goToPage,
        reset,
    } = usePaginatedFetch(
        postsPath,
        () => ({
            ...(search.value && { search: search.value }),
            ...getExtraParams(),
        }),
        null,
        initialPosts,
    );

    function performSearch() {
        reset();
    }

    function addPost(post) {
        posts.value.unshift(post);
    }

    function updatePost(updated) {
        const index = posts.value.findIndex((post) => post.id === updated.id);
        if (index !== -1) posts.value[index] = updated;
    }

    function removePost(id) {
        posts.value = posts.value.filter((post) => post.id !== id);
    }

    return {
        posts,
        page,
        totalPages,
        total,
        loading,
        search,
        performSearch,
        goToPage,
        addPost,
        updatePost,
        removePost,
        load,
    };
}
