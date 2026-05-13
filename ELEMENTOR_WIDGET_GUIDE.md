# Elementor Widget Development — Best Practices Guide

Everything learned building the Availability Calendar for Beds24 widget.
Use this as a checklist and reference when starting a new Elementor widget project.

---

## Table of Contents

1. [License & Plugin Header](#1-license--plugin-header)
2. [Project Structure](#2-project-structure)
3. [Asset Registration & Enqueuing](#3-asset-registration--enqueuing)
4. [Widget Class Structure](#4-widget-class-structure)
5. [Controls: Defaults & Responsive Values](#5-controls-defaults--responsive-values)
6. [PHP Rendering: `render()`](#6-php-rendering-render)
7. [Editor Preview: `content_template()`](#7-editor-preview-content_template)
8. [Live Preview JS Handler](#8-live-preview-js-handler)
9. [Dynamic Tags (ACF, Post Meta, etc.)](#9-dynamic-tags-acf-post-meta-etc)
10. [`is_dynamic_content()`](#10-is_dynamic_content)
11. [Plugin Update Checker (PUC)](#11-plugin-update-checker-puc)
12. [Development Branch Workflow](#12-development-branch-workflow)
13. [GitHub Actions CI/CD](#13-github-actions-cicd)
14. [Code Quality: PHPCS & PHPStan](#14-code-quality-phpcs--phpstan)

---

## 1. License & Plugin Header

WordPress.org requires GPL-2.0-or-later. Always include the full header block in the main plugin file.

```php
<?php
/**
 * Plugin Name:       My Widget
 * Plugin URI:        https://github.com/you/my-widget
 * Description:       Short description.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://github.com/you
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-widget
 * Primary Branch:    main
 *
 * @package My_Widget
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
```

**Notes:**
- Remove `GitHub Branch: development` before merging to main — it tells Git Updater to track the dev branch, which is wrong for stable releases.
- `Primary Branch: main` is used by Plugin Update Checker and Git Updater.
- The `Version:` field in the header is what WordPress displays. The release CI workflow can inject it automatically from the git tag — keep a `BAC_VERSION` constant in sync with it.

---

## 2. Project Structure

```
my-widget/
├── my-widget.php                  ← Main plugin file (shortcode + init)
├── assets/
│   ├── my-widget.css              ← Frontend CSS
│   ├── my-widget.js               ← Frontend JS class
│   └── elementor-preview.js       ← Editor-only preview handler
├── widgets/
│   └── class-my-widget.php        ← Elementor widget class
├── vendor/                        ← Composer dependencies (runtime only)
│   └── yahnis-elsts/              ← Plugin Update Checker
├── readme.txt                     ← WordPress.org readme
├── composer.json
├── phpcs.xml
├── phpstan.neon
└── .github/
    └── workflows/
        ├── ci.yml                 ← Lint on PRs to main
        ├── dev-release.yml        ← Build zip on push to development
        └── release.yml            ← Build zip on version tag
```

### Critical: asset paths

WordPress and Elementor reference assets by **URL**, not by filesystem path.
Assets must live under the plugin folder; the PHP file registers them with `plugin_dir_url(__FILE__)`.

```php
// In main plugin file:
define( 'MY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

wp_register_style( 'my-widget', MY_PLUGIN_URL . 'assets/my-widget.css', array(), MY_VERSION );
wp_register_script( 'my-widget', MY_PLUGIN_URL . 'assets/my-widget.js',  array(), MY_VERSION, true );
```

**If assets are in the wrong folder** (e.g. root instead of `assets/`), the CSS and JS will 404 silently — the widget renders but with no styling or interactivity.

### Separating frontend CSS from preview

The `elementor/preview/enqueue_scripts` hook loads assets into the **Elementor preview iframe** — you must enqueue **both** the CSS and JS there, or the grid layout will break (only user-agent styles apply).

```php
add_action(
    'elementor/preview/enqueue_scripts',
    function () {
        wp_enqueue_style( 'my-widget' );          // ← easy to forget
        wp_enqueue_script( 'my-widget' );
        wp_enqueue_script(
            'my-widget-elementor-preview',
            MY_PLUGIN_URL . 'assets/elementor-preview.js',
            array( 'elementor-frontend', 'my-widget' ),
            MY_VERSION,
            true
        );
    }
);
```

---

## 3. Asset Registration & Enqueuing

Register assets on `init` so handles exist in all contexts (shortcode, widget, REST, AJAX).
Enqueue lazily — only when an instance is actually on the page.

```php
add_action( 'init', function () {
    wp_register_style( 'my-widget', MY_PLUGIN_URL . 'assets/my-widget.css', array(), MY_VERSION );
    wp_register_script( 'my-widget', MY_PLUGIN_URL . 'assets/my-widget.js',  array(), MY_VERSION, true );
} );

function my_register_instance( array $config ): string {
    wp_enqueue_style( 'my-widget' );   // idempotent — safe to call multiple times
    wp_enqueue_script( 'my-widget' );
    // ... return container HTML
}
```

---

## 4. Widget Class Structure

```php
namespace MyPlugin;

class My_Widget extends \Elementor\Widget_Base {

    public function get_name(): string        { return 'my_widget'; }
    public function get_title(): string       { return esc_html__( 'My Widget', 'my-widget' ); }
    public function get_icon(): string        { return 'eicon-code'; }
    public function get_categories(): array   { return array( 'general' ); }

    protected function register_controls(): void { /* ... */ }
    protected function render(): void            { /* ... */ }
    protected function content_template(): void  { /* ... */ }
    public    function is_dynamic_content(): bool { return true; }
}
```

Register the widget on `elementor/widgets/register`:

```php
add_action(
    'elementor/widgets/register',
    function ( \Elementor\Widgets_Manager $manager ) {
        require_once MY_PLUGIN_DIR . 'widgets/class-my-widget.php';
        $manager->register( new \MyPlugin\My_Widget() );
    }
);
```

---

## 5. Controls: Defaults & Responsive Values

### The default value problem

**Elementor only stores control values in the database when the user explicitly changes them.**
If a user never touches the "Months to Display" slider, nothing is stored — the control's `default` exists only in PHP as a hint for the editor UI.

This means:
- `get_settings()` may return an empty array `[]` for a slider that was never touched
- `get_settings_for_display()` applies defaults and the responsive cascade — prefer this in `render()`

### Correct pattern for responsive sliders

```php
$this->add_responsive_control(
    'nummonths',
    array(
        'label'              => esc_html__( 'Months to Display', 'my-widget' ),
        'type'               => \Elementor\Controls_Manager::SLIDER,
        'size_units'         => array(),
        'range'              => array(
            'px' => array( 'min' => 1, 'max' => 12, 'step' => 1 ),
        ),
        'default'            => array( 'unit' => 'px', 'size' => 3 ),
        'tablet_default'     => array( 'unit' => 'px', 'size' => 2 ),
        'mobile_default'     => array( 'unit' => 'px', 'size' => 1 ),
        'frontend_available' => true,   // see section 8
    )
);
```

Always write a helper to safely extract the integer from a slider value —
Elementor may return `{ size: 3, unit: 'px' }` **or** a plain integer depending on context:

```php
private function slider_size( $val, int $fallback ): int {
    if ( is_array( $val ) && isset( $val['size'] ) && '' !== $val['size'] ) {
        return (int) $val['size'];
    }
    if ( is_numeric( $val ) ) {
        return (int) $val;
    }
    return $fallback;
}
```

Use it like this in `render()`:

```php
$s = $this->get_settings_for_display();
$months = $this->slider_size( $s['nummonths'] ?? array(), 3 );
```

### `frontend_available` on controls

Add `'frontend_available' => true'` to every control whose value needs to be read by a **JavaScript handler** on the published frontend.

Without it, the control value is absent from `elementorFrontend.config.elements.data` and `getElementSettings()` returns nothing for that key.

**However:** for the editor preview, the more reliable approach is to embed settings in a `data-*` attribute via `content_template()` — see Section 7. The `frontend_available` flag still matters for the published page's JS.

---

## 6. PHP Rendering: `render()`

Always use `get_settings_for_display()`, not `get_settings()`:

| Method | Use case |
|--------|----------|
| `get_settings_for_display()` | `render()` — resolves dynamic tags, applies responsive cascade |
| `get_settings()` | Reading raw stored values for logic decisions only (e.g. checking `__dynamic__`) |

```php
protected function render(): void {
    $s = $this->get_settings_for_display();

    // $s['roomid'] is now the resolved value — even if it came from an ACF field.
    $config = $this->build_config( $s );

    // In edit mode, attach config as a data attribute for the JS preview handler.
    $extra_attrs = array();
    if ( \Elementor\Plugin::$instance && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        $preview_cfg = $config;
        unset( $preview_cfg['containerId'] ); // JS sets this from the DOM id
        $extra_attrs['data-my-cfg'] = (string) wp_json_encode( $preview_cfg );
    }

    // Output the widget HTML (bac_register_instance equivalent)
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $this->render_html( $config, $extra_attrs );
}
```

**Why is edit mode special?** When `is_dynamic_content()` returns `true`, Elementor renders the widget via an AJAX call — `wp_footer` is never fired, so any initialization scripts hooked there won't run. Embedding the config in a `data-*` attribute lets the JS handler initialize the widget without the footer script.

---

## 7. Editor Preview: `content_template()`

`content_template()` is an Underscore.js template that Elementor uses to render the widget in the editor canvas **without a PHP round-trip**.

### Embed settings in data attributes — do not rely on `getElementSettings()`

`getElementSettings()` in the preview JS handler reads from `elementorFrontend.config.elements.data`, which is generated by PHP when the editor page loads. It only contains controls that have `frontend_available: true` **and** whose values were serialized during the last Elementor page save. This makes it unreliable for initial render.

**Better approach:** embed the settings directly in a `data-*` attribute inside `content_template()`. The template has unconditional access to the full `settings` object.

```php
protected function content_template(): void {
    ?>
    <#
    var containerId = 'my-widget-' + view.model.id;

    // Safely read a slider value regardless of whether Elementor returns
    // { size: n, unit: 'px' } or a plain number.
    function _sz( v, fb ) {
        if ( ! v ) { return fb; }
        var n = ( 'object' === typeof v ) ? parseInt( v.size, 10 ) : parseInt( v, 10 );
        return ( isNaN( n ) || n < 1 ) ? fb : n;
    }

    // For static controls, settings.key is the raw literal value.
    // For dynamic tags, is_dynamic_content() forces server-side rendering,
    // so content_template() only runs for static values.
    function _plain( key ) {
        return String( settings[ key ] || '' ).trim();
    }

    var myCfg = JSON.stringify( {
        roomid:   _plain( 'roomid' ),
        numMonths: _sz( settings.nummonths, 3 ),
        lang:     _plain( 'lang' ) || 'en',
    } );
    #>
    <div id="{{ containerId }}" class="my-widget-wrapper" data-my-cfg='{{{ myCfg }}}'></div>
    <?php
}
```

**Notes on the template syntax:**
- `{{ value }}` — HTML-escaped output (safe for attributes using double quotes as delimiter)
- `{{{ value }}}` — Unescaped output (use single quotes as attribute delimiter when embedding JSON)
- `<# code #>` — Arbitrary JS logic

### Color controls — no re-render needed

Color controls with `selectors` are handled by Elementor via CSS custom property injection. They update **instantly** in the editor without triggering a template re-render and without any JS re-initialization.

```php
$this->add_control(
    'color_available',
    array(
        'label'     => esc_html__( 'Available', 'my-widget' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => array(
            '{{WRAPPER}} .my-widget-wrapper' => '--my-avail-bg: {{VALUE}};',
        ),
    )
);
```

Define those variables in your CSS and use them throughout — Elementor sets them inline on the widget container element.

---

## 8. Live Preview JS Handler

The JS handler runs inside the Elementor preview iframe and re-initializes the widget when settings change.

```javascript
// assets/elementor-preview.js
( function () {
    'use strict';

    var MyWidgetHandler = elementorModules.frontend.handlers.Base.extend( {

        onInit: function () {
            elementorModules.frontend.handlers.Base.prototype.onInit.apply( this, arguments );
            this._initWidget();
        },

        onElementChange: function () {
            this._initWidget();
        },

        _initWidget: function () {
            var $wrapper = this.$element.find( '.my-widget-wrapper' );
            if ( ! $wrapper.length ) { return; }

            // Clear any existing timers / subscriptions on re-init.
            if ( this._instance && this._instance.destroy ) {
                this._instance.destroy();
            }
            $wrapper.empty();

            // Read config from the data attribute set by content_template() or render().
            // This is more reliable than getElementSettings() — see notes below.
            var cfg;
            try {
                cfg = JSON.parse( $wrapper.attr( 'data-my-cfg' ) || '{}' );
            } catch ( e ) {
                cfg = {};
            }
            cfg.containerId = $wrapper.attr( 'id' );

            this._instance = new MyWidget( cfg );
        },
    } );

    jQuery( window ).on( 'elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/my_widget.default',   // must match get_name() + '.default'
            function ( $scope ) {
                new MyWidgetHandler( { $element: $scope } );
            }
        );
    } );
} )();
```

### Why data attribute instead of `getElementSettings()`

`getElementSettings()` is unreliable for the initial render because:
1. On initial page load in the editor, it reads from `elementorFrontend.config.elements.data`
2. That config only includes controls with `frontend_available: true`
3. It requires the page to have been saved in Elementor after the controls were first registered
4. For dynamic tags, it returns an empty string (the tag is not resolved client-side)

Reading from the `data-*` attribute avoids all of these issues. The attribute is set by either:
- `content_template()` for static values (instant, no AJAX)
- PHP `render()` (via AJAX) when `is_dynamic_content()` returns true — includes resolved dynamic tag values

---

## 9. Dynamic Tags (ACF, Post Meta, etc.)

### The problem

Dynamic tags (Elementor's `{{acf_field}}`, post meta, author name, etc.) are **resolved server-side only**.

In the Elementor editor preview, `content_template()` (JavaScript) cannot resolve dynamic tags:
- `settings.roomid` will be an **empty string** when a dynamic tag is assigned to it
- The tag configuration lives in `settings.__dynamic__.roomid` — not the resolved value

This is why a heading element that uses an ACF field shows the real value in the editor (Elementor patches its DOM directly for display-only output), but a custom widget that needs the value as an *input* (e.g. to make an API call) sees nothing.

### The fix: `is_dynamic_content()` + server-side render

See Section 10 for `is_dynamic_content()`. Once it returns `true`, Elementor switches the editor from the JS template to server-side AJAX rendering. The PHP `render()` method is called, `get_settings_for_display()` resolves the dynamic tag, and you get the actual value.

### Passing the resolved value to JS

Embed the resolved config in a `data-*` attribute in `render()` during edit mode (Section 6). The JS handler reads from it after Elementor injects the server-rendered HTML.

### Do not use `__dynamic__` as a guard in `content_template()`

It is tempting to check `settings.__dynamic__.roomid` in the Underscore template to detect dynamic tags and show a placeholder. Avoid this — even after server-side resolution the `__dynamic__` key is still present, so the guard permanently blocks the resolved value.

---

## 10. `is_dynamic_content()`

```php
public function is_dynamic_content(): bool {
    // Return true unconditionally for widgets with server-rendered content.
    // When true, Elementor uses AJAX to call render() in the editor preview
    // instead of the JS content_template(), allowing dynamic tags to be
    // resolved via get_settings_for_display().
    //
    // CRITICAL: Never call $this->get_settings() here.
    // Elementor calls is_dynamic_content() on widget *types* during editor
    // script loading — before any widget instance is bound to a post.
    // At that point get_settings() returns null and causes a fatal TypeError
    // in Elementor\Controls_Stack::sanitize_settings().
    return true;
}
```

**What returning `true` does:**
- Editor preview switches from JS `content_template()` to server-side AJAX rendering
- `render()` is called on every non-CSS setting change, `get_settings_for_display()` resolves dynamic tags
- Color controls still update via CSS injection (instant, no AJAX)
- Slight extra latency on non-CSS setting changes (one AJAX round-trip) — acceptable

**What it does NOT affect:**
- Published frontend rendering — always PHP, always `render()`, unaffected
- `wp_footer` behavior on the published page — unaffected

---

## 11. Plugin Update Checker (PUC)

[YahnisElsts/plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) lets WordPress detect updates from GitHub without being on wordpress.org.

### Setup

```php
$puc_path = plugin_dir_path( __FILE__ ) . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $puc_path ) ) {
    require_once $puc_path;
    $checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/you/my-widget/',
        __FILE__,
        'my-widget'
    );

    if ( defined( 'MY_WIDGET_UPDATE_BRANCH' ) && MY_WIDGET_UPDATE_BRANCH ) {
        // Dev mode: track a development branch and override the download URL
        // to use a correctly-named zip (see Section 12).
        $checker->setBranch( MY_WIDGET_UPDATE_BRANCH );
    } else {
        // Stable mode: use tagged GitHub Releases with attached zip assets.
        $checker->getVcsApi()->enableReleaseAssets();
    }
    unset( $checker );
}
unset( $puc_path );
```

### Override the download URL for dev builds

GitHub's branch zipball is named `{repo}-{branch}/` internally. When WordPress installs it, it sees a **different folder name** than the existing plugin and treats it as a brand new plugin — you end up with two plugins instead of an update.

Fix: build a correctly-named zip in CI and override the PUC download URL:

```php
add_filter(
    'puc_request_info_result-my-widget',   // suffix must match plugin slug
    function ( $info ) {
        if ( null === $info ) {
            return $info;
        }
        if ( defined( 'MY_WIDGET_UPDATE_BRANCH' ) && MY_WIDGET_UPDATE_BRANCH ) {
            $info->download_url = 'https://github.com/you/my-widget/releases/download/dev-latest/my-widget.zip';
        }
        return $info;
    }
);
```

### Version numbers on the dev branch

Append `-dev` to the version string on the development branch (e.g. `1.5.0-dev`). PUC uses the `Version:` header to detect whether an update is available. With `-dev` versions, PUC may not show an update prompt — use `?force-check=1` appended to the Plugins admin URL to force a check during development.

### Opt-in per site via wp-config.php

```php
// In wp-config.php on your dev/staging site only:
define( 'MY_WIDGET_UPDATE_BRANCH', 'development' );
```

Production sites never define this constant, so they track tagged stable releases.

---

## 12. Development Branch Workflow

### The problem with developing on main

Once a plugin is published, users may be running it. Pushing breaking changes to main means those changes ship to production sites immediately when they trigger a PUC check.

### Recommended workflow

```
development branch  →  push  →  CI lints  →  builds zip  →  uploads to dev-latest pre-release
                                                                          ↓
                                                               WP admin detects update
                                                               (if BAC_UPDATE_BRANCH defined)
                                                                          ↓
                                                               Test on staging
                                                                          ↓
main branch  ←  merge  ←  tag v1.x.x  →  release.yml  →  zip attached to GitHub Release
```

### Version naming

| Branch | Version example | Meaning |
|--------|----------------|---------|
| development | `1.5.0-dev` | Work in progress |
| main (tagged) | `1.5.0` | Stable release |

Increment dev versions per commit or logical chunk (e.g. `1.5.1-dev`, `1.5.2-dev`) so you can tell from the Plugins admin which build is installed on staging.

### Before merging to main

- Remove `GitHub Branch: development` from the plugin header
- Strip the `-dev` suffix from the version
- Update `Stable tag:` in `readme.txt`
- Add a changelog entry in `readme.txt`
- Run PHPCS and PHPStan
- Merge with `--no-ff` to preserve branch history

---

## 13. GitHub Actions CI/CD

### Three workflows

**`ci.yml`** — Runs PHPCS + PHPStan on pull requests targeting `main`. Does NOT run on pushes (to avoid duplicate runs with dev-release.yml).

```yaml
on:
  pull_request:
    branches: [main]
```

**`dev-release.yml`** — Runs on every push to `development`. Lints first, then builds and uploads the zip to the rolling `dev-latest` pre-release.

```yaml
on:
  push:
    branches: [development]

jobs:
  lint:
    # ... PHPCS + PHPStan ...

  release:
    needs: lint
    steps:
      - name: Build zip
        run: |
          mkdir -p /tmp/build/my-widget
          rsync -av --exclude='.git' --exclude='.github' --exclude='vendor' \
            --exclude='phpcs.xml' --exclude='phpstan.neon' --exclude='*.md' \
            . /tmp/build/my-widget/
          composer install --no-dev --working-dir=/tmp/build/my-widget
          cd /tmp/build && zip -r my-widget.zip my-widget/

      - name: Upload to dev-latest pre-release
        uses: softprops/action-gh-release@v2
        with:
          tag_name: dev-latest
          prerelease: true
          make_latest: false
          files: /tmp/build/my-widget.zip
```

**Critical:** the zip must be named `my-widget.zip` and must contain a single folder `my-widget/` matching the plugin slug. GitHub's own branch zipball uses `{repo}-{branch}/` — that's the wrong folder name and will be treated as a new plugin.

**`release.yml`** — Triggered by a version tag push (`git tag v1.5.0 && git push origin v1.5.0`). Injects the version from the tag into the PHP file and readme, builds the zip, creates the GitHub Release.

```yaml
on:
  push:
    tags: ['v*']
```

The version injection means you do not need a separate commit to bump the version before tagging — just tag and push.

---

## 14. Code Quality: PHPCS & PHPStan

### PHPCS (WordPress Coding Standards)

```xml
<!-- phpcs.xml -->
<?xml version="1.0"?>
<ruleset name="My Widget">
    <file>.</file>
    <exclude-pattern>vendor/</exclude-pattern>
    <rule ref="WordPress"/>
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array" value="my-widget"/>
        </properties>
    </rule>
</ruleset>
```

**Common gotchas:**
- WordPress uses **Yoda conditions**: `null === $var` not `$var === null`
- Inline arrays in function calls must be on their own lines when there are 2+ entries
- Arrow alignment is expected on multi-line arrays
- Function docblock must document every parameter with `@param`
- `error_log()` calls are flagged — use `phpcs:ignore` if you need them temporarily (see below)

**Never silently remove debug code flagged by PHPCS.** Use `phpcs:ignore` instead and ask first:

```php
error_log( 'debug: ' . $value ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
```

### PHPStan

```neon
# phpstan.neon
parameters:
    level: 6
    paths:
        - .
    excludePaths:
        - vendor/
```

**Common gotchas with Elementor:**
- PUC's info object has dynamic properties — use `@phpstan-ignore property.notFound`
- `\Elementor\Plugin::$instance` may be typed as nullable — always null-check before dereferencing

```php
if ( \Elementor\Plugin::$instance && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
    // safe
}
```

### Run both locally before every commit

```bash
./vendor/bin/phpcs
./vendor/bin/phpstan analyse --memory-limit=512M
./vendor/bin/phpcbf   # auto-fix most PHPCS issues
```

---

## Quick Reference Checklist

**New widget setup:**
- [ ] GPL-2.0-or-later license in header
- [ ] Assets in `assets/` subfolder, registered with `plugin_dir_url()`
- [ ] CSS enqueued in `elementor/preview/enqueue_scripts` (not just JS)
- [ ] Widget class in `widgets/` subfolder
- [ ] `is_dynamic_content()` returns `true` — never calls `get_settings()` inside it

**Controls:**
- [ ] `get_settings_for_display()` in `render()`, not `get_settings()`
- [ ] `slider_size()` helper for responsive sliders (handles both array and scalar forms)
- [ ] `frontend_available: true` on controls needed by frontend JS
- [ ] Color controls use `selectors` → CSS custom properties (instant, no re-render)

**Editor preview:**
- [ ] `content_template()` embeds settings in `data-*` attribute as JSON
- [ ] JS handler reads from `data-*` attribute, not `getElementSettings()`
- [ ] `elementor/preview/enqueue_scripts` loads both widget CSS and JS

**Dynamic tags:**
- [ ] `is_dynamic_content()` returns `true`
- [ ] `render()` attaches `data-*` attribute in edit mode with resolved config
- [ ] No `__dynamic__` guards in `content_template()` — they block resolved values

**Release workflow:**
- [ ] Dev branch named `development`
- [ ] Dev builds use `-dev` version suffix
- [ ] PUC override filter points to correctly-named `dev-latest` zip
- [ ] `define( 'MY_PLUGIN_UPDATE_BRANCH', 'development' )` in staging `wp-config.php` only
- [ ] Merge to `main` → remove `GitHub Branch: development` header → tag → push tag → done
