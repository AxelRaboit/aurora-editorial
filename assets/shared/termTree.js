export function buildTermTree(terms) {
    const byId = new Map(
        terms.map((term) => [term.id, { ...term, children: [] }]),
    );
    const roots = [];
    for (const node of byId.values()) {
        if (node.parentId && byId.has(node.parentId)) {
            byId.get(node.parentId).children.push(node);
        } else {
            roots.push(node);
        }
    }
    const sortRecursive = (nodes) => {
        nodes.sort((a, b) => a.position - b.position || a.id - b.id);
        nodes.forEach((n) => sortRecursive(n.children));
    };
    sortRecursive(roots);
    return roots;
}

export function flattenTreeWithDepth(nodes, depth = 0) {
    const result = [];
    for (const node of nodes) {
        result.push({ ...node, depth });
        if (node.children?.length) {
            result.push(...flattenTreeWithDepth(node.children, depth + 1));
        }
    }
    return result;
}
