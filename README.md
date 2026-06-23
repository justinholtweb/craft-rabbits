# Rabbits

A Webflow-style visual component builder for [Craft CMS 5](https://craftcms.com).

Rabbits lets you build reusable, responsive components in a visual canvas — no
template wrangling required. Components are stored as a JSON tree and compiled
to clean, cache-friendly Twig. Drop them anywhere on the front end with a single
function call, bind them to live CMS content, and add motion with built-in
animations and interactive widgets.

## Requirements

- Craft CMS 5.3.0 or later
- PHP 8.2 or later

## Installation

From the **Plugin Store**: search for “Rabbits” in your Craft control panel
under **Plugin Store**, then click **Install**.

With **Composer**:

```bash
composer require justinholtweb/craft-rabbits
php craft plugin/install rabbits
```

## Features

- **Visual builder** — drag-and-drop canvas with a live iframe preview, layer
  tree, and per-breakpoint style controls.
- **34+ element types** across Layout, Content, Media, Forms, Interactive, Data,
  and Embed categories.
- **Live data binding** — bind any text, link, or image to Craft fields, or
  loop a template over a query with the **Dynamic List** element.
- **Load-in animations** — fade, slide, scale, and blur presets driven by the
  Web Animations API, triggered on page load, scroll, hover, or click.
- **Interactive components** — slideshow, carousel, popup/modal, accordion,
  tabs, alert, counter, marquee, and tooltip, powered by a dependency-free
  vanilla-JS runtime.
- **Form-plugin embeds** — drop in [Freeform](https://docs.solspace.com/craft/freeform/)
  or [Formie](https://verbb.io/craft-plugins/formie) forms by handle.
- **Tailwind & Alpine friendly** — apply utility classes and arbitrary
  attributes (including Alpine `x-*` / `@` / `:` bindings) to any element.
- **Compiles to Twig** — output is plain Twig/HTML, so there’s no runtime
  rendering overhead and your markup stays portable.

## Usage

### Create a component

Go to **Rabbits → Components → New Component**, give it a handle, then click
**Open Builder** to design it visually. Set the component’s status to **Active**
when it’s ready to render on the front end.

### Render a component

```twig
{# Render by handle #}
{{ rabbits_component('hero') }}

{# Pass variables for field bindings #}
{{ rabbits_component('hero', { entry: entry }) }}
```

Output the component styles once per page (in your `<head>`), and the animation
+ interactive runtime once (before `</body>`):

```twig
{# In <head> #}
{{ rabbits_styles() }}

{# Before </body> #}
{{ rabbits_animations() }}

{# Optional — include Alpine.js if you use x-* bindings #}
{{ rabbits_alpine() }}
```

### Bind to CMS content

In the builder’s property panel, set a text element’s **Content** to **Field
Binding** and enter an expression like `entry.title`. For images, bind the
**Source** to something like `entry.heroImage.one().url`. Then pass the relevant
variables when you render:

```twig
{{ rabbits_component('articleCard', { entry: entry }) }}
```

### Loop over a query (Dynamic List)

Add a **Dynamic List**, set its **Query** to any Twig expression, and add a child
template (e.g. a Card). Reference each item with the configured item variable:

```
Query:          craft.entries.section('blog').limit(6)
Item variable:  item
Card heading binding:   item.title
```

## Element types

| Category | Elements |
| --- | --- |
| **Layout** | Container, Section, Columns, Divider, Spacer |
| **Content** | Heading, Text, List, Link, Button, Icon |
| **Media** | Image, Video |
| **Forms** | Form, Input, Textarea, Select, Label, Submit, Freeform, Formie |
| **Interactive** | Slideshow, Carousel, Popup, Accordion, Tabs, Card, Alert, Counter, Marquee, Tooltip |
| **Data** | Dynamic List |
| **Embed** | Embed (iframe), HTML |

## Animations

Any element can be given a load-in animation from the property panel:

- **Presets:** Fade In, Slide Up / Down / Left / Right, Scale In, Blur In
- **Triggers:** Page Load, Scroll Into View, Hover, Click
- **Controls:** duration, delay, and easing

## Tailwind & Alpine

- **Tailwind:** apply utility classes to any element via the **Classes** field.
  Set **Settings → CSS Framework** to *Tailwind* and make sure your compiled
  components are covered by your Tailwind `content` paths (or safelist) so
  utility classes aren’t purged.
- **Alpine:** add `x-data`, `x-show`, `@click`, `:class`, and other bindings via
  the **Attributes** editor. Toggle **Settings → Load Alpine.js** (or call
  `{{ rabbits_alpine() }}`) to load a pinned Alpine build with Subresource
  Integrity.

## Settings

Configure under **Rabbits → Settings** (or via `config/rabbits.php`): component
limits, Twig caching, custom CSS/JS, breakpoints, compiled output path, CSS
framework, and Alpine loading.

## Permissions

On Craft Pro, Rabbits registers granular permissions: **Access Rabbits**,
**Manage Components**, **Manage Classes**, **Manage Tokens**, and
**Manage Settings**. Because authors can write Twig bindings, grant
**Manage Components** only to trusted users.

## Extending Rabbits

Plugins and modules can register their own element types, add animation presets,
and rewrite compiled markup through Rabbits' events. See **[EXTENDING.md](EXTENDING.md)**
for the full developer guide, including a custom-element-type example.

## Building the builder assets

The control-panel builder is a Vue 3 app. To rebuild it after changing the
source:

```bash
cd src/web/assets/builder
npm install
npm run build
```

## License

This plugin is licensed under [The Craft License](LICENSE.md).
