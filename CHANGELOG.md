# Release Notes for Rabbits

## 5.0.0 - 2026-06-21

> {tip} First public release. Versioned 5.x to track Craft CMS 5.

### Added
- Visual component builder with a drag-and-drop canvas, live iframe preview,
  layer tree, and per-breakpoint style controls.
- Component element type with a JSON tree compiled to clean, cache-friendly Twig.
- 34+ element types across Layout, Content, Media, Forms, Interactive, Data, and
  Embed categories.
- Live data binding for text, links, and images, plus a **Dynamic List** element
  that repeats a child template over a Craft query.
- Load-in animations (Fade, Slide, Scale, Blur) using the Web Animations API,
  triggered on page load, scroll into view, hover, or click.
- Interactive components — Slideshow, Carousel, Popup/Modal, Accordion, Tabs,
  Card, Alert, Counter, Marquee, and Tooltip — powered by a dependency-free
  vanilla-JS runtime.
- Freeform and Formie form embeds (guarded so they no-op when the plugin isn’t
  installed).
- Generic **Attributes** editor for arbitrary `data-*` / `aria-*` attributes and
  Alpine `x-*` / `@` / `:` bindings.
- Tailwind awareness and an Alpine.js loader (pinned build with Subresource
  Integrity), configurable in Settings.
- Twig functions: `rabbits_component()`, `rabbits_styles()`,
  `rabbits_animations()`, and `rabbits_alpine()`.
- Optional Sourdough theme bridge for design tokens, and `data-rabbits-node`
  hooks for Smoke front-end editing.
- Granular user permissions (Craft Pro).
