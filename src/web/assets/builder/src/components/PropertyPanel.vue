<template>
  <div class="rabbits-properties">
    <!-- Tag -->
    <div v-if="showTagSelector" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Tag</label>
      <select class="rabbits-prop-input" :value="node.tag" @change="update('tag', $event.target.value)">
        <option v-for="tag in availableTags" :key="tag" :value="tag">{{ tag }}</option>
      </select>
    </div>

    <!-- Content (text-bearing nodes) -->
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

    <!-- Href (link / button) -->
    <div v-if="hasHref" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Link URL</label>
      <input class="rabbits-prop-input rabbits-prop-input--code" :value="node.href" @change="update('href', $event.target.value)" placeholder="#" />
    </div>

    <!-- Image / Video source -->
    <div v-if="isImage || isVideo" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Source</label>
      <div class="rabbits-prop-group__sub">
        <select class="rabbits-prop-input" :value="srcType" @change="updateSrcType($event.target.value)">
          <option value="static">Static URL</option>
          <option value="field">Field Binding</option>
        </select>
        <input
          v-if="srcType === 'static'"
          class="rabbits-prop-input"
          :value="srcValue"
          @input="updateSrcValue($event.target.value)"
          placeholder="https://…"
        />
        <input
          v-else
          class="rabbits-prop-input rabbits-prop-input--code"
          :value="srcBinding"
          @input="updateSrcBinding($event.target.value)"
          placeholder="entry.image.one().url"
        />
      </div>
    </div>

    <!-- Image alt -->
    <div v-if="isImage" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Alt Text</label>
      <input class="rabbits-prop-input" :value="node.alt" @change="update('alt', $event.target.value)" />
    </div>

    <!-- Video options -->
    <div v-if="isVideo" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Poster</label>
      <input class="rabbits-prop-input" :value="node.poster" @change="update('poster', $event.target.value)" placeholder="poster image URL" />
      <div class="rabbits-prop-flags">
        <label v-for="flag in ['controls','autoplay','loop','muted']" :key="flag" class="rabbits-prop-flag">
          <input type="checkbox" :checked="!!node[flag]" @change="update(flag, $event.target.checked)" /> {{ flag }}
        </label>
      </div>
    </div>

    <!-- Icon SVG -->
    <div v-if="isIcon" class="rabbits-prop-group">
      <label class="rabbits-prop-label">SVG Markup</label>
      <textarea class="rabbits-prop-input rabbits-prop-input--code" rows="4" :value="node.svg" @change="update('svg', $event.target.value)" placeholder="<svg>…</svg>"></textarea>
    </div>

    <!-- Embed -->
    <div v-if="isEmbed" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Embed URL</label>
      <input class="rabbits-prop-input rabbits-prop-input--code" :value="node.src" @change="update('src', $event.target.value)" placeholder="https://www.youtube.com/embed/…" />
      <label class="rabbits-prop-label">Title</label>
      <input class="rabbits-prop-input" :value="node.title" @change="update('title', $event.target.value)" />
    </div>

    <!-- Raw HTML -->
    <div v-if="isHtml" class="rabbits-prop-group">
      <label class="rabbits-prop-label">HTML</label>
      <textarea class="rabbits-prop-input rabbits-prop-input--code" rows="6" :value="node.html" @change="update('html', $event.target.value)"></textarea>
    </div>

    <!-- Form -->
    <div v-if="isForm" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Action</label>
      <input class="rabbits-prop-input rabbits-prop-input--code" :value="node.action" @change="update('action', $event.target.value)" placeholder="/submit" />
      <label class="rabbits-prop-label">Method</label>
      <select class="rabbits-prop-input" :value="node.method" @change="update('method', $event.target.value)">
        <option value="post">POST</option>
        <option value="get">GET</option>
      </select>
    </div>

    <!-- Input -->
    <div v-if="isInput" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Input Type</label>
      <select class="rabbits-prop-input" :value="node.inputType" @change="update('inputType', $event.target.value)">
        <option v-for="t in inputTypes" :key="t" :value="t">{{ t }}</option>
      </select>
    </div>

    <!-- Input / Textarea / Select shared fields -->
    <div v-if="isInput || isTextarea || isSelect" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Name</label>
      <input class="rabbits-prop-input rabbits-prop-input--code" :value="node.name" @change="update('name', $event.target.value)" placeholder="field_name" />
      <template v-if="isInput || isTextarea">
        <label class="rabbits-prop-label">Placeholder</label>
        <input class="rabbits-prop-input" :value="node.placeholder" @change="update('placeholder', $event.target.value)" />
      </template>
      <label v-if="isTextarea" class="rabbits-prop-label">Rows</label>
      <input v-if="isTextarea" type="number" class="rabbits-prop-input" :value="node.rows" @change="update('rows', parseInt($event.target.value) || 1)" />
      <label class="rabbits-prop-flag">
        <input type="checkbox" :checked="!!node.required" @change="update('required', $event.target.checked)" /> required
      </label>
    </div>

    <!-- Select options -->
    <div v-if="isSelect" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Options</label>
      <div class="rabbits-styles">
        <div v-for="(opt, i) in (node.options || [])" :key="i" class="rabbits-style-row">
          <input class="rabbits-prop-input rabbits-prop-input--small" :value="opt.label" @change="updateOption(i, 'label', $event.target.value)" placeholder="Label" />
          <input class="rabbits-prop-input rabbits-prop-input--small rabbits-prop-input--code" :value="opt.value" @change="updateOption(i, 'value', $event.target.value)" placeholder="value" />
          <button class="rabbits-btn-icon" @click="removeOption(i)">×</button>
        </div>
        <div class="rabbits-style-row rabbits-style-row--add">
          <button class="rabbits-btn-icon" @click="addOption">+</button>
        </div>
      </div>
    </div>

    <!-- Label for -->
    <div v-if="isLabel" class="rabbits-prop-group">
      <label class="rabbits-prop-label">For (field name)</label>
      <input class="rabbits-prop-input rabbits-prop-input--code" :value="node.for" @change="update('for', $event.target.value)" />
    </div>

    <!-- Columns -->
    <div v-if="isColumns" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Columns</label>
      <input type="number" min="1" max="6" class="rabbits-prop-input" :value="node.columnCount || 2" @change="updateColumnCount($event.target.value)" />
    </div>

    <!-- Slideshow / Carousel -->
    <div v-if="isSlideshow || isCarousel" class="rabbits-prop-group">
      <template v-if="isCarousel">
        <label class="rabbits-prop-label">Items per view</label>
        <input type="number" min="1" max="6" class="rabbits-prop-input" :value="node.itemsPerView || 3" @change="update('itemsPerView', Math.max(1, parseInt($event.target.value) || 1))" />
      </template>
      <label class="rabbits-prop-label">Interval (ms)</label>
      <input type="number" class="rabbits-prop-input" :value="node.interval || 5000" @change="update('interval', parseInt($event.target.value) || 0)" />
      <div class="rabbits-prop-flags">
        <label class="rabbits-prop-flag"><input type="checkbox" :checked="!!node.autoplay" @change="update('autoplay', $event.target.checked)" /> autoplay</label>
        <label class="rabbits-prop-flag"><input type="checkbox" :checked="!!node.loop" @change="update('loop', $event.target.checked)" /> loop</label>
        <label class="rabbits-prop-flag"><input type="checkbox" :checked="!!node.showArrows" @change="update('showArrows', $event.target.checked)" /> arrows</label>
        <label class="rabbits-prop-flag"><input type="checkbox" :checked="!!node.showDots" @change="update('showDots', $event.target.checked)" /> dots</label>
      </div>
      <button class="btn small" style="margin-top:0.5rem" @click="$emit('add-child', node.id, 'slide')">Add Slide</button>
    </div>

    <!-- Popup -->
    <div v-if="isPopup" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Trigger</label>
      <select class="rabbits-prop-input" :value="node.trigger || 'click'" @change="update('trigger', $event.target.value)">
        <option value="click">On click</option>
        <option value="page-load">On page load</option>
        <option value="exit-intent">On exit intent</option>
      </select>
      <template v-if="(node.trigger || 'click') === 'click'">
        <label class="rabbits-prop-label">Trigger button label</label>
        <input class="rabbits-prop-input" :value="node.triggerLabel" @change="update('triggerLabel', $event.target.value)" />
      </template>
      <template v-if="node.trigger === 'page-load'">
        <label class="rabbits-prop-label">Delay (ms)</label>
        <input type="number" class="rabbits-prop-input" :value="node.delay || 0" @change="update('delay', parseInt($event.target.value) || 0)" />
      </template>
    </div>

    <!-- Accordion -->
    <div v-if="isAccordion" class="rabbits-prop-group">
      <label class="rabbits-prop-flag"><input type="checkbox" :checked="!!node.allowMultiple" @change="update('allowMultiple', $event.target.checked)" /> allow multiple open</label>
      <button class="btn small" style="margin-top:0.5rem" @click="$emit('add-child', node.id, 'accordion-item')">Add Item</button>
    </div>

    <!-- Accordion item title -->
    <div v-if="isAccordionItem" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Title</label>
      <input class="rabbits-prop-input" :value="node.title && node.title.value" @input="updateTitleValue($event.target.value)" />
    </div>

    <!-- Tabs -->
    <div v-if="isTabs" class="rabbits-prop-group">
      <button class="btn small" @click="$emit('add-child', node.id, 'tab')">Add Tab</button>
    </div>

    <!-- Tab label -->
    <div v-if="isTab" class="rabbits-prop-group">
      <label class="rabbits-prop-label">Tab label</label>
      <input class="rabbits-prop-input" :value="node.label" @change="update('label', $event.target.value)" />
    </div>

    <!-- Freeform / Formie form handle -->
    <div v-if="isFreeform || isFormie" class="rabbits-prop-group">
      <label class="rabbits-prop-label">{{ isFreeform ? 'Freeform' : 'Formie' }} form handle</label>
      <input class="rabbits-prop-input rabbits-prop-input--code" :value="node.handle" @change="update('handle', $event.target.value)" placeholder="contactForm" />
      <p class="rabbits-prop-hint">Renders the {{ isFreeform ? 'Freeform' : 'Formie' }} form with this handle on the front end.</p>
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
          <input class="rabbits-prop-input rabbits-prop-input--small rabbits-prop-input--code" :value="prop" readonly />
          <input class="rabbits-prop-input rabbits-prop-input--small" :value="value" @change="updateStyle(prop, $event.target.value)" />
          <button class="rabbits-btn-icon" @click="removeStyle(prop)">×</button>
        </div>
        <div class="rabbits-style-row rabbits-style-row--add">
          <input class="rabbits-prop-input rabbits-prop-input--small rabbits-prop-input--code" v-model="newStyleProp" placeholder="property" />
          <input class="rabbits-prop-input rabbits-prop-input--small" v-model="newStyleValue" placeholder="value" @keyup.enter="addStyle" />
          <button class="rabbits-btn-icon" @click="addStyle">+</button>
        </div>
      </div>
    </div>

    <!-- Animation -->
    <div class="rabbits-prop-group">
      <label class="rabbits-prop-label">Animation</label>
      <div class="rabbits-prop-group__sub">
        <select class="rabbits-prop-input" :value="animPreset" @change="setAnimationPreset($event.target.value)">
          <option value="">None</option>
          <option v-for="(preset, key) in presets" :key="key" :value="key">{{ preset.label }}</option>
        </select>
        <template v-if="hasAnimation">
          <select class="rabbits-prop-input" :value="animTrigger" @change="setAnimationTrigger($event.target.value)">
            <option v-for="(label, value) in triggers" :key="value" :value="value">{{ label }}</option>
          </select>
          <div class="rabbits-style-row">
            <input type="number" class="rabbits-prop-input rabbits-prop-input--small" :value="animDuration" @change="setAnimationOption('duration', $event.target.value)" placeholder="duration ms" />
            <input type="number" class="rabbits-prop-input rabbits-prop-input--small" :value="animDelay" @change="setAnimationOption('delay', $event.target.value)" placeholder="delay ms" />
          </div>
          <input class="rabbits-prop-input rabbits-prop-input--code" :value="animEasing" @change="setAnimationOption('easing', $event.target.value)" placeholder="easing (ease-out)" />
        </template>
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
const FIXED_TAG_TYPES = ['image', 'video', 'icon', 'embed', 'html', 'input', 'textarea', 'select', 'divider', 'spacer',
  'slideshow', 'carousel', 'slide', 'popup', 'accordion', 'accordion-item', 'tabs', 'tab', 'freeform', 'formie'];

export default {
  name: 'PropertyPanel',

  props: {
    node: { type: Object, required: true },
    breakpoint: { type: String, default: 'default' },
    presets: { type: Object, default: () => ({}) },
    triggers: { type: Object, default: () => ({}) },
  },

  emits: ['update', 'add-child'],

  data() {
    return {
      newStyleProp: '',
      newStyleValue: '',
      inputTypes: ['text', 'email', 'tel', 'number', 'password', 'url', 'date', 'checkbox', 'radio', 'hidden'],
    };
  },

  computed: {
    availableTags() {
      return ['div', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav',
              'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'a', 'ul', 'ol', 'li',
              'figure', 'figcaption', 'blockquote'];
    },

    showTagSelector() { return !FIXED_TAG_TYPES.includes(this.node.type); },
    hasContent() { return ['heading', 'text', 'button', 'link', 'listitem', 'label', 'submit'].includes(this.node.type); },
    hasHref() { return ['link', 'button'].includes(this.node.type); },
    isImage() { return this.node.type === 'image'; },
    isVideo() { return this.node.type === 'video'; },
    isIcon() { return this.node.type === 'icon'; },
    isEmbed() { return this.node.type === 'embed'; },
    isHtml() { return this.node.type === 'html'; },
    isForm() { return this.node.type === 'form'; },
    isInput() { return this.node.type === 'input'; },
    isTextarea() { return this.node.type === 'textarea'; },
    isSelect() { return this.node.type === 'select'; },
    isLabel() { return this.node.type === 'label'; },
    isColumns() { return this.node.type === 'columns'; },
    isSlideshow() { return this.node.type === 'slideshow'; },
    isCarousel() { return this.node.type === 'carousel'; },
    isPopup() { return this.node.type === 'popup'; },
    isAccordion() { return this.node.type === 'accordion'; },
    isAccordionItem() { return this.node.type === 'accordion-item'; },
    isTabs() { return this.node.type === 'tabs'; },
    isTab() { return this.node.type === 'tab'; },
    isFreeform() { return this.node.type === 'freeform'; },
    isFormie() { return this.node.type === 'formie'; },

    contentType() { return this.node.content?.type || 'static'; },
    contentValue() { return this.node.content?.value || ''; },
    contentBinding() { return this.node.content?.binding || ''; },

    srcType() { return this.node.src?.type || 'static'; },
    srcValue() { return this.node.src?.value || ''; },
    srcBinding() { return this.node.src?.binding || ''; },

    currentStyles() {
      const bpKey = this.breakpoint === 'desktop' ? 'default' : this.breakpoint;
      return this.node.styles?.[bpKey] || {};
    },

    hasAnimation() { return !!this.node.animations; },
    animPreset() { return this.node.animations?.preset || ''; },
    animTrigger() { return this.node.animations?.trigger || 'scroll-into-view'; },
    animDuration() { return this.node.animations?.options?.duration ?? ''; },
    animDelay() { return this.node.animations?.options?.delay ?? ''; },
    animEasing() { return this.node.animations?.options?.easing ?? ''; },
  },

  methods: {
    update(key, value) {
      this.$emit('update', this.node.id, { [key]: value });
    },

    updateContentType(type) {
      const content = { ...this.node.content, type };
      if (type === 'field') content.binding = content.binding || 'entry.title';
      this.$emit('update', this.node.id, { content });
    },
    updateContentValue(value) {
      this.$emit('update', this.node.id, { content: { ...this.node.content, value } });
    },
    updateContentBinding(value) {
      const key = this.contentType === 'field' ? 'binding' : 'value';
      this.$emit('update', this.node.id, { content: { ...this.node.content, [key]: value } });
    },

    updateSrcType(type) {
      const src = { ...this.node.src, type };
      if (type === 'field') src.binding = src.binding || 'entry.image.one().url';
      this.$emit('update', this.node.id, { src });
    },
    updateSrcValue(value) {
      this.$emit('update', this.node.id, { src: { ...this.node.src, value } });
    },
    updateSrcBinding(value) {
      this.$emit('update', this.node.id, { src: { ...this.node.src, binding: value } });
    },

    updateOption(i, key, value) {
      const options = (this.node.options || []).map((o, idx) => idx === i ? { ...o, [key]: value } : o);
      this.$emit('update', this.node.id, { options });
    },
    addOption() {
      this.$emit('update', this.node.id, { options: [...(this.node.options || []), { value: '', label: 'Option' }] });
    },
    removeOption(i) {
      this.$emit('update', this.node.id, { options: (this.node.options || []).filter((_, idx) => idx !== i) });
    },

    updateColumnCount(n) {
      const count = Math.max(1, Math.min(6, parseInt(n) || 1));
      const bpKey = this.breakpoint === 'desktop' ? 'default' : this.breakpoint;
      const styles = { ...(this.node.styles || {}) };
      styles[bpKey] = { ...(styles[bpKey] || {}), gridTemplateColumns: `repeat(${count}, 1fr)` };
      this.$emit('update', this.node.id, { columnCount: count, styles });
    },

    updateTitleValue(value) {
      this.$emit('update', this.node.id, { title: { type: 'static', value } });
    },

    updateClasses(value) {
      this.$emit('update', this.node.id, { classes: value.split(/\s+/).filter(Boolean) });
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

    setAnimationPreset(presetKey) {
      if (!presetKey) {
        this.$emit('update', this.node.id, { animations: null });
        return;
      }
      const preset = this.presets[presetKey] || {};
      const current = this.node.animations || {};
      this.$emit('update', this.node.id, {
        animations: {
          preset: presetKey,
          trigger: current.trigger || 'scroll-into-view',
          keyframes: preset.keyframes || [],
          options: { ...(preset.options || {}), ...(current.options || {}) },
        },
      });
    },
    setAnimationTrigger(trigger) {
      if (!this.node.animations) return;
      this.$emit('update', this.node.id, { animations: { ...this.node.animations, trigger } });
    },
    setAnimationOption(key, value) {
      if (!this.node.animations) return;
      const options = { ...(this.node.animations.options || {}) };
      if (value === '' || value == null) {
        delete options[key];
      } else {
        options[key] = key === 'easing' ? value : (parseInt(value) || 0);
      }
      this.$emit('update', this.node.id, { animations: { ...this.node.animations, options } });
    },
  },
};
</script>
