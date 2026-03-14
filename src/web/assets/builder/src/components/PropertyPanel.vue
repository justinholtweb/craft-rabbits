<template>
  <div class="rabbits-properties">
    <!-- Node info -->
    <div class="rabbits-prop-group">
      <label class="rabbits-prop-label">Tag</label>
      <select class="rabbits-prop-input" :value="node.tag" @change="update('tag', $event.target.value)">
        <option v-for="tag in availableTags" :key="tag" :value="tag">{{ tag }}</option>
      </select>
    </div>

    <!-- Content (for text nodes) -->
    <div v-if="hasContent" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Content</label>
      <div class="rabbits-prop-group__sub">
        <select class="rabbits-prop-input" :value="contentType" @change="updateContentType($event.target.value)">
          <option value="static">Static Text</option>
          <option value="field">Field Binding</option>
          <option value="twig">Twig Expression</option>
        </select>
        <input
          v-if="contentType === 'static'"
          type="text"
          class="rabbits-prop-input"
          :value="contentValue"
          @input="updateContentValue($event.target.value)"
        />
        <input
          v-else
          type="text"
          class="rabbits-prop-input rabbits-prop-input--code"
          :value="contentType === 'field' ? contentBinding : contentValue"
          @input="updateContentBinding($event.target.value)"
          :placeholder="contentType === 'field' ? 'entry.title' : '{{ entry.title }}'"
        />
      </div>
    </div>

    <!-- CSS Classes -->
    <div class="rabbits-prop-group">
      <label class="rabbits-prop-label">Classes</label>
      <input
        type="text"
        class="rabbits-prop-input rabbits-prop-input--code"
        :value="(node.classes || []).join(' ')"
        @change="updateClasses($event.target.value)"
        placeholder="class1 class2"
      />
    </div>

    <!-- Styles -->
    <div class="rabbits-prop-group">
      <label class="rabbits-prop-label">Styles ({{ breakpoint }})</label>
      <div class="rabbits-styles">
        <div v-for="(value, prop) in currentStyles" :key="prop" class="rabbits-style-row">
          <input
            class="rabbits-prop-input rabbits-prop-input--small rabbits-prop-input--code"
            :value="prop"
            readonly
          />
          <input
            class="rabbits-prop-input rabbits-prop-input--small"
            :value="value"
            @change="updateStyle(prop, $event.target.value)"
          />
          <button class="rabbits-btn-icon" @click="removeStyle(prop)">×</button>
        </div>
        <div class="rabbits-style-row rabbits-style-row--add">
          <input
            class="rabbits-prop-input rabbits-prop-input--small rabbits-prop-input--code"
            v-model="newStyleProp"
            placeholder="property"
          />
          <input
            class="rabbits-prop-input rabbits-prop-input--small"
            v-model="newStyleValue"
            placeholder="value"
            @keyup.enter="addStyle"
          />
          <button class="rabbits-btn-icon" @click="addStyle">+</button>
        </div>
      </div>
    </div>

    <!-- Delete -->
    <div class="rabbits-prop-group" v-if="node.id !== 'root'">
      <button class="btn small" @click="$emit('update', node.id, { _delete: true })">
        Delete Element
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PropertyPanel',

  props: {
    node: { type: Object, required: true },
    breakpoint: { type: String, default: 'default' },
  },

  emits: ['update'],

  data() {
    return {
      newStyleProp: '',
      newStyleValue: '',
    };
  },

  computed: {
    availableTags() {
      return ['div', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav',
              'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'a', 'img', 'hr',
              'ul', 'ol', 'li', 'figure', 'figcaption', 'blockquote'];
    },

    hasContent() {
      return ['heading', 'text', 'button', 'link'].includes(this.node.type);
    },

    contentType() {
      return this.node.content?.type || 'static';
    },

    contentValue() {
      return this.node.content?.value || '';
    },

    contentBinding() {
      return this.node.content?.binding || '';
    },

    currentStyles() {
      const bpKey = this.breakpoint === 'desktop' ? 'default' : this.breakpoint;
      return this.node.styles?.[bpKey] || {};
    },
  },

  methods: {
    update(key, value) {
      this.$emit('update', this.node.id, { [key]: value });
    },

    updateContentType(type) {
      const content = { ...this.node.content, type };
      if (type === 'field') {
        content.binding = content.binding || 'entry.title';
      }
      this.$emit('update', this.node.id, { content });
    },

    updateContentValue(value) {
      this.$emit('update', this.node.id, {
        content: { ...this.node.content, value },
      });
    },

    updateContentBinding(value) {
      const key = this.contentType === 'field' ? 'binding' : 'value';
      this.$emit('update', this.node.id, {
        content: { ...this.node.content, [key]: value },
      });
    },

    updateClasses(value) {
      const classes = value.split(/\s+/).filter(Boolean);
      this.$emit('update', this.node.id, { classes });
    },

    updateStyle(prop, value) {
      const bpKey = this.breakpoint === 'desktop' ? 'default' : this.breakpoint;
      const styles = { ...(this.node.styles || {}) };
      styles[bpKey] = { ...(styles[bpKey] || {}), [prop]: value };
      this.$emit('update', this.node.id, { styles });
    },

    removeStyle(prop) {
      const bpKey = this.breakpoint === 'desktop' ? 'default' : this.breakpoint;
      const styles = { ...(this.node.styles || {}) };
      const bpStyles = { ...(styles[bpKey] || {}) };
      delete bpStyles[prop];
      styles[bpKey] = bpStyles;
      this.$emit('update', this.node.id, { styles });
    },

    addStyle() {
      if (this.newStyleProp && this.newStyleValue) {
        this.updateStyle(this.newStyleProp, this.newStyleValue);
        this.newStyleProp = '';
        this.newStyleValue = '';
      }
    },
  },
};
</script>
