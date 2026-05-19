import { describe, expect, it } from "vitest";
import {
    buildTermTree,
    flattenTreeWithDepth,
} from "@editorial/shared/termTree.js";

const terms = [
    { id: 3, parentId: 1, position: 1 },
    { id: 1, parentId: null, position: 2 },
    { id: 2, parentId: null, position: 1 },
    { id: 4, parentId: 1, position: 0 },
];

describe("buildTermTree", () => {
    it("returns roots sorted by (position, id)", () => {
        const tree = buildTermTree(terms);
        expect(tree.map((n) => n.id)).toEqual([2, 1]);
    });

    it("nests children under their parent and sorts them", () => {
        const tree = buildTermTree(terms);
        const node1 = tree.find((n) => n.id === 1);
        expect(node1.children.map((c) => c.id)).toEqual([4, 3]);
    });

    it("treats unknown parents as roots", () => {
        const orphan = [{ id: 9, parentId: 99, position: 0 }];
        const tree = buildTermTree(orphan);
        expect(tree).toHaveLength(1);
        expect(tree[0].id).toBe(9);
    });

    it("returns an empty array on empty input", () => {
        expect(buildTermTree([])).toEqual([]);
    });
});

describe("flattenTreeWithDepth", () => {
    it("flattens depth-first with depth markers", () => {
        const tree = buildTermTree(terms);
        const flat = flattenTreeWithDepth(tree);
        expect(flat.map((n) => [n.id, n.depth])).toEqual([
            [2, 0],
            [1, 0],
            [4, 1],
            [3, 1],
        ]);
    });

    it("returns empty array for empty tree", () => {
        expect(flattenTreeWithDepth([])).toEqual([]);
    });
});
