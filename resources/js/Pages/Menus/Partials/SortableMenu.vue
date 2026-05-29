<script setup>
import Draggable from 'vuedraggable';
import { cloneMenuItem, normalizeMenuItems } from '../menuItemNormalizer';

defineProps({
    items: {
        type: Array,
        required: true
    },
    depth: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['update:items', 'edit', 'remove', 'changed']);

const typeLabels = {
    custom: 'لینک سفارشی',
    page: 'صفحه',
    category: 'دسته‌بندی',
    product: 'محصول',
    brand: 'برند',
    post: 'مقاله',
    external: 'لینک خارجی'
};

const updateItems = (items) => {
    emit('update:items', normalizeMenuItems(items));
    emit('changed');
};

const cloneItem = (item) => cloneMenuItem(item);
</script>

<template>
    <Draggable
        :model-value="items"
        item-key="id"
        group="menu-items"
        handle=".menu-drag-handle"
        ghost-class="menu-ghost"
        chosen-class="menu-chosen"
        class="menu-sortable"
        :animation="180"
        :clone="cloneItem"
        @update:model-value="updateItems"
        @change="$emit('changed')"
    >
        <template #item="{ element }">
            <div class="menu-node" :style="{ marginInlineStart: depth ? '1rem' : '0' }">
                <div class="menu-node__bar">
                    <button type="button" class="menu-drag-handle" title="جابجایی">☰</button>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="truncate font-bold text-surface-900">{{ element.title }}</span>
                            <Tag :value="typeLabels[element.type] || element.type" severity="secondary" />
                            <Tag v-if="!element.is_active" value="غیرفعال" severity="danger" />
                        </div>
                        <div class="mt-1 truncate text-xs text-surface-500">
                            {{ element.url || element.route_name || 'لینک داخلی' }}
                        </div>
                    </div>
                    <Button icon="pi pi-pencil" text rounded severity="secondary" aria-label="ویرایش" @click="$emit('edit', element)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" aria-label="حذف" @click="$emit('remove', element)" />
                </div>

                <SortableMenu
                    v-model:items="element.children"
                    :depth="depth + 1"
                    @edit="$emit('edit', $event)"
                    @remove="$emit('remove', $event)"
                    @changed="$emit('changed')"
                />
            </div>
        </template>
    </Draggable>
</template>

<style scoped>
.menu-sortable {
    min-height: 1rem;
}

.menu-node {
    margin-bottom: 0.55rem;
}

.menu-node__bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border: 1px solid var(--surface-border);
    border-radius: 6px;
    background: var(--surface-card);
    padding: 0.75rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

.menu-drag-handle {
    cursor: grab;
    border: 1px solid var(--surface-border);
    border-radius: 4px;
    background: var(--surface-ground);
    color: var(--text-color-secondary);
    width: 2rem;
    height: 2rem;
}

.menu-ghost {
    opacity: 0.45;
}

.menu-chosen .menu-node__bar {
    border-color: var(--primary-color);
}
</style>
