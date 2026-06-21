import { createApp } from 'vue';
import BuilderApp from './BuilderApp.vue';
import './builder.css';

const mountEl = document.getElementById('rabbits-builder');

if (mountEl) {
  const app = createApp(BuilderApp, {
    componentId: parseInt(mountEl.dataset.componentId),
    componentHandle: mountEl.dataset.componentHandle,
    previewUrl: mountEl.dataset.previewUrl,
  });

  app.mount(mountEl);
}
