<template>
  <div class="rabbits-builder" :class="{ 'is-loading': loading }">
    <!-- Toolbar -->
    <div class="rabbits-toolbar">
      <div class="rabbits-toolbar__left">
        <button
          v-for="bp in breakpoints"
          :key="bp.key"
          class="rabbits-toolbar__btn"
          :class="{ active: activeBreakpoint === bp.key }"
          @click="activeBreakpoint = bp.key"
          :title="bp.label"
        >
          {{ bp.icon }}
        </button>
      </div>
      <div class="rabbits-toolbar__center">
        <span class="rabbits-toolbar__title">{{ componentHandle }}</span>
      </div>
      <div class="rabbits-toolbar__right">
        <button class="rabbits-toolbar__btn" @click="save" :disabled="saving">
          {{ saving ? 'Saving...' : 'Save' }}
        </button>
        <button class="rabbits-toolbar__btn" @click="refreshPreview">
          Refresh
        </button>
      </div>
    </div>

    <!-- Main layout: sidebar + canvas + properties -->
    <div class="rabbits-layout">
      <!-- Left sidebar: atom palette + layer tree -->
      <div class="rabbits-sidebar rabbits-sidebar--left">
        <div class="rabbits-panel">
          <div class="rabbits-panel__header">Add Element</div>
          <div class="rabbits-palette">
            <button
              v-for="atom in atomPalette"
              :key="atom.type"
              class="rabbits-palette__item"
              draggable="true"
              @dragstart="onDragStartAtom($event, atom.type)"
              @click="addAtomToRoot(atom.type)"
            >
              <span class="rabbits-palette__label">{{ atom.label }}</span>
            </button>
          </div>
        </div>

        <div class="rabbits-panel">
          <div class="rabbits-panel__header">Layer Tree</div>
          <div class="rabbits-layers">
            <LayerNode
              v-if="tree && tree.id"
              :node="tree"
              :selected-id="selectedNodeId"
              :depth="0"
              @select="selectNode"
              @drop="onDropNode"
            />
          </div>
        </div>
      </div>

      <!-- Canvas (iframe preview) -->
      <div class="rabbits-canvas">
        <iframe
          ref="previewIframe"
          :src="previewUrl"
          class="rabbits-canvas__iframe"
          :style="canvasStyle"
        ></iframe>
      </div>

      <!-- Right sidebar: properties panel -->
      <div class="rabbits-sidebar rabbits-sidebar--right">
        <div class="rabbits-panel" v-if="selectedNode">
          <div class="rabbits-panel__header">Properties</div>
          <PropertyPanel
            :node="selectedNode"
            :breakpoint="activeBreakpoint"
            @update="onUpdateNode"
          />
        </div>
        <div class="rabbits-panel" v-else>
          <div class="rabbits-panel__header">Properties</div>
          <p class="rabbits-panel__empty">Select an element to edit its properties.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import LayerNode from './components/LayerNode.vue';
import PropertyPanel from './components/PropertyPanel.vue';

export default {
  name: 'BuilderApp',

  components: { LayerNode, PropertyPanel },

  props: {
    componentId: { type: Number, required: true },
    componentHandle: { type: String, required: true },
    previewUrl: { type: String, required: true },
    apiUrl: { type: String, required: true },
  },

  data() {
    return {
      loading: true,
      saving: false,
      tree: {},
      selectedNodeId: null,
      activeBreakpoint: 'desktop',
      atomPalette: [],
      breakpoints: [
        { key: 'desktop', label: 'Desktop', icon: '🖥', width: '100%' },
        { key: 'tablet', label: 'Tablet', icon: '📱', width: '768px' },
        { key: 'mobile', label: 'Mobile', icon: '📲', width: '375px' },
      ],
    };
  },

  computed: {
    selectedNode() {
      if (!this.selectedNodeId || !this.tree?.id) return null;
      return this.findNode(this.tree, this.selectedNodeId);
    },

    canvasStyle() {
      const bp = this.breakpoints.find(b => b.key === this.activeBreakpoint);
      return {
        maxWidth: bp?.width || '100%',
        margin: '0 auto',
      };
    },
  },

  async mounted() {
    await this.loadData();
    this.loading = false;
  },

  methods: {
    async loadData() {
      try {
        // Load tree
        const treeRes = await this.api('get-tree', { componentId: this.componentId }, 'GET');
        this.tree = treeRes.tree || {};

        // Load palette
        const paletteRes = await this.api('get-palette', {}, 'GET');
        this.atomPalette = paletteRes.atoms || [];
      } catch (err) {
        console.error('Failed to load builder data:', err);
      }
    },

    selectNode(nodeId) {
      this.selectedNodeId = nodeId;
    },

    async addAtomToRoot(type) {
      const parentId = this.selectedNodeId || (this.tree?.id || 'root');
      await this.addNode(type, parentId);
    },

    async addNode(type, parentId, position) {
      try {
        const res = await this.api('add-node', {
          componentId: this.componentId,
          parentId,
          nodeType: type,
          position: position ?? null,
        });

        if (res.success) {
          this.tree = res.tree;
          this.selectedNodeId = res.newNode?.id;
          this.refreshPreview();
        }
      } catch (err) {
        console.error('Failed to add node:', err);
      }
    },

    async removeNode(nodeId) {
      try {
        const res = await this.api('remove-node', {
          componentId: this.componentId,
          nodeId,
        });

        if (res.success) {
          this.tree = res.tree;
          if (this.selectedNodeId === nodeId) {
            this.selectedNodeId = null;
          }
          this.refreshPreview();
        }
      } catch (err) {
        console.error('Failed to remove node:', err);
      }
    },

    async onUpdateNode(nodeId, updates) {
      try {
        const res = await this.api('update-node', {
          componentId: this.componentId,
          nodeId,
          updates,
        });

        if (res.success) {
          this.tree = res.tree;
          this.refreshPreview();
        }
      } catch (err) {
        console.error('Failed to update node:', err);
      }
    },

    async onDropNode({ nodeId, newParentId, position }) {
      try {
        const res = await this.api('move-node', {
          componentId: this.componentId,
          nodeId,
          newParentId,
          position,
        });

        if (res.success) {
          this.tree = res.tree;
          this.refreshPreview();
        }
      } catch (err) {
        console.error('Failed to move node:', err);
      }
    },

    async save() {
      this.saving = true;
      try {
        const res = await this.api('save-tree', {
          componentId: this.componentId,
          tree: this.tree,
        });

        if (res.success) {
          Craft.cp.displayNotice('Component saved.');
        } else {
          Craft.cp.displayError('Failed to save component.');
        }
      } catch (err) {
        Craft.cp.displayError('Failed to save component.');
      } finally {
        this.saving = false;
      }
    },

    refreshPreview() {
      const iframe = this.$refs.previewIframe;
      if (iframe) {
        iframe.contentWindow?.postMessage({ type: 'rabbits:refresh' }, '*');
        // Fallback: reload iframe
        setTimeout(() => { iframe.src = iframe.src; }, 100);
      }
    },

    onDragStartAtom(event, type) {
      event.dataTransfer.setData('rabbits/atom-type', type);
      event.dataTransfer.effectAllowed = 'copy';
    },

    findNode(tree, id) {
      if (tree.id === id) return tree;
      if (tree.children) {
        for (const child of tree.children) {
          const found = this.findNode(child, id);
          if (found) return found;
        }
      }
      return null;
    },

    async api(action, data, method = 'POST') {
      const csrfToken = Craft.csrfTokenValue;
      const url = `${this.apiUrl}/${action}`;

      if (method === 'GET') {
        const params = new URLSearchParams(data);
        const res = await fetch(`${url}?${params}`, {
          headers: { 'X-CSRF-Token': csrfToken, 'Accept': 'application/json' },
        });
        return res.json();
      }

      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken,
          'Accept': 'application/json',
        },
        body: JSON.stringify({ ...data, [Craft.csrfTokenName]: csrfToken }),
      });
      return res.json();
    },
  },
};
</script>
