import { usePaginatedSearch } from "@/shared/composables/http/frontend/usePaginatedSearch.js";

export function usePostSearch(props) {
    const { items: posts, ...rest } = usePaginatedSearch({
        initialItems: props.initialPosts,
        initialPage: props.initialPage,
        initialTotalPages: props.initialTotalPages,
        initialTotal: props.initialTotal,
        searchPath: props.searchPath,
        itemsKey: "posts",
    });

    return { posts, ...rest };
}
