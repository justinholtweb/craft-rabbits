# craft-rabbits

Webflow-style visual component builder for Craft CMS 5.

## Plugin Details
- **Namespace:** `justinholtweb\rabbits`
- **Handle:** `rabbits`
- **Requires:** PHP 8.2+, Craft CMS 5.3+

## Architecture
- Component element type with JSON tree structure + compiled Twig
- CP Vue 3 builder with iframe preview
- CSS animations + WAAPI (no GSAP dependency)
- Optional Sourdough theme bridge for design tokens
- Twig functions: `rabbits_component()`, `rabbits_styles()`, `rabbits_animations()`

## Key Patterns
- Element type: `src/elements/Component.php` (follows craft-leads Popup pattern)
- Tree manipulation: `src/services/Builder.php`
- JSON → Twig: `src/services/TwigCompiler.php`
- Vue builder: `src/web/assets/builder/src/`

## Build
```bash
cd src/web/assets/builder && npm install && npm run build
```

## Related Plugins
- craft-sourdough: Theme/token integration via ThemeBridge
- craft-smoke: Frontend editing via data-smoke-* attributes
- craft-leads: Reference for element type patterns
- craft-freenav: Reference for hierarchical element patterns
