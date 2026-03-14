<template>
  <div class="rabbits-layer" :class="{ 'is-selected': node.id === selectedId }">
    <div
      class="rabbits-layer__row"
      :style="{ paddingLeft: depth * 16 + 'px' }"
      @click="$emit('select', node.id)"
      draggable="true"
      @dragstart.stop="onDragStart"
      @dragover.prevent="onDragOver"
      @drop.stop="onDrop"
    >
      <span
        v-if="hasChildren"
        class="rabbits-layer__toggle"
        @click.stop="expanded = !expanded"
      >
        {{ expanded ? '▾' : '▸' }}
      </span>
      <span v-else class="rabbits-layer__toggle rabbits-layer__toggle--empty"></span>

      <span class="rabbits-layer__type">{{ node.type }}</span>
      <span class="rabbits-layer__tag">{{ node.tag }}</span>
    </div>

    <div v-if="expanded && hasChildren" class="rabbits-layer__children">
      <LayerNode
        v-for="(child, index) in node.children"
        :key="child.id"
        :node="child"
        :selected-id="selectedId"
        :depth="depth + 1"
        @select="$emit('select', $event)"
        @drop="$emit('drop', $event)"
      />
    </div>
  </div>
</template>

<script>
export default {
  name: 'LayerNode',

  props: {
    node: { type: Object, required: true },
    selectedId: { type: String, default: null },
    depth: { type: Number, default: 0 },
  },

  emits: ['select', 'drop'],

  data() {
    return {
      expanded: this.depth < 2,
    };
  },

  computed: {
    hasChildren() {
      return this.node.children && this.node.children.length > 0;
    },
  },

  methods: {
    onDragStart(event) {
      event.dataTransfer.setData('rabbits/node-id', this.node.id);
      event.dataTransfer.effectAllowed = 'move';
    },

    onDragOver(event) {
      event.dataTransfer.dropEffect = 'move';
    },

    onDrop(event) {
      const nodeId = event.dataTransfer.getData('rabbits/node-id');
      const atomType = event.dataTransfer.getData('rabbits/atom-type');

      if (nodeId && nodeId !== this.node.id) {
        this.$emit('drop', {
          nodeId,
          newParentId: this.node.id,
          position: 0,
        });
      }
    },
  },
};
</script>
