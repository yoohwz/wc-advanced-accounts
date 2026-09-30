# Membership rendering contract

`yoaa_render_membership_content( $raw_content )` is the shared Core/Premium PHP entry point for rendering raw shortcode content containing `yoaa_membership` wrappers. It pre-authorizes Membership regions without executing their bodies, then calls the native WordPress shortcode renderer. It does not render blocks. Themes/plugins needing the no-execution guarantee for direct shortcode rendering must use this API instead of calling `do_shortcode( $raw_content )` directly.

```php
echo yoaa_render_membership_content( $raw_content );
```

Automatic pre-authorization runs on `the_content`, `widget_text_content` and `widget_block_content` at priority `-9998`, before native block hooks, blocks and shortcodes. Public REST rendered content passing through `the_content` receives this protection. An arbitrary native `do_shortcode()` call bypassing these entry points is outside the guarantee: WordPress processes shortcodes in HTML attributes before the enclosing shortcode callback.

The guard checks wrapper structure and the currently registered Core/Premium Membership shortcode owner's policy. It removes denied bodies before native rendering and leaves authorized wrappers unchanged for normal exactly-once rendering. Valid denied wrappers retain the owner's notice, `hide=yes` and Premium custom-message behavior. Non-empty explicit levels fail closed if any level is unknown, deselected or no longer a registered role; valid lists use OR semantics. Omitted/empty levels retain guest/logged-in behavior. Premium uses effective Membership entitlement; Core keeps its supported role policy.

Fully escaped `[[yoaa_membership ...]]` syntax retains native literal semantics. Recursive same-tag nesting is not a supported new language: ambiguous nesting or malformed attributes fail closed. A malformed paired region is replaced with a denial; separable surrounding content is retained. An unclosed wrapper consumes the remaining uncertain region. Ordinary nested other shortcodes and blocks inside a denied wrapper never execute through the supported pipelines/API.

The shared guard is idempotent and resolves the effective shortcode owner at render time, including after Free/Premium ownership coordination. It does not change role storage, entitlement lifecycle, native REST editor capabilities or global shortcode/block registration.
