import { createApp } from 'vue';
import BuilderApp from './BuilderApp.vue';

const mountEl = document.getElementById('rabbits-builder');

if (mountEl) {
  const app = createApp(BuilderApp, {
    componentId: parseInt(mountEl.dataset.componentId),
    componentHandle: mountEl.dataset.componentHandle,
    previewUrl: mountEl.dataset.previewUrl,
    apiUrl: mountEl.dataset.apiUrl,
  });

  app.mount(mountEl);
}
