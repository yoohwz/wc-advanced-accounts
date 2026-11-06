=== Advanced Accounts for WooCommerce ===
Contributors: yoohw
Tags: woocommerce, my-account, otp-login, account-verification, membership
Requires at least: 6.3
Tested up to: 7.0
WC tested up to: 10.8
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize customer profiles and account menus, protect sign-in, manage verification, and control member-only content.

== Description ==

Advanced Accounts for WooCommerce helps store owners improve the WooCommerce My Account experience with email verification, email OTP login and password reset, phone-based accounts, customer profiles, CAPTCHA, account management, and membership content access.

Use it to make customer accounts easier to manage, verify email ownership, and show selected content only to the right customers.

[Premium version](https://yoohw.com/product/woocommerce-advanced-accounts-premium/) | [Documentation](https://docs.yoohw.com/category/woocommerce-advanced-accounts-premium/) | [Support](https://yoohw.com/support/) | [Demo](https://sandbox.yoohw.com/demo/wcaa_demo.html)

= WooCommerce My Account Customization =

Improve the customer account area without editing theme templates. You can manage WooCommerce account behavior from plugin settings and keep the experience aligned with your store.

Free account features include:

* Redirect the default WordPress login page to WooCommerce My Account.
* Support phone number based account login.
* Disable email requirement when using phone-first registration.
* Keep checkout and account login flows connected to WooCommerce.
* Change default account menu labels, slugs, Font Awesome icons, order, and visibility.
* Add custom navigation links with a label, URL, icon, position, visibility, and optional new tab.
* Require first and last names on My Account registration.
* Let customers upload, replace, and remove avatars, and choose the name displayed below them.
* Block or unblock customer accounts individually or in bulk, revoking existing sessions when blocked.
* Let customers request account/personal data erasure from Account details through the WordPress privacy process. Confirmation by email is required; an administrator processes the request. Users and orders are not automatically deleted. Phone-first accounts must first save a real email address.
* Migrate existing customer usernames to phone-number based usernames with dry-run and batch controls.

Premium account customization features include:

* Add custom content pages in My Account, with endpoint content and individual CSS.
* Show account tabs only to selected roles or membership levels.
* Build custom profile fields, conditional fields, and upload fields.
* Add birth date fields and age requirements.

= Account Verification =

Require new WooCommerce customers to confirm their email address before their account is fully activated.

Free email verification and OTP features include:

* Email verification during account registration.
* Email activation links and verified email status for compatible checkout protection workflows.
* WooCommerce OTP login by email.
* Password reset using an OTP sent by email.
* Server-side OTP expiration, failed attempt limits, resend cooldown, and resend limits. Email login OTP permits the initial send plus the configured resends in a 15-minute destination window; changing browser sessions does not reset it.
* View email verification status and mark or unmark it individually or in bulk as an administrator override.
* Preview and apply email verification batches in Tools with dry-run, bounded batches, and per-user results.

Phone verification and phone OTP workflows are available in the Premium version using third-party providers such as Twilio or Textmagic. You can also request integration with another messaging provider; typical completion time is 1–2 business days.

= Membership Content Access =

Create membership levels with WordPress roles and use them to control who can see selected content.

Free membership features include:

* Create and manage membership roles.
* Use the `[yoaa_membership]` shortcode to show content to selected roles.
* Show or hide shortcode content for logged-in users or guests.
* Build simple member-only pages without a separate membership plugin.
* Set a custom denial message with `[yoaa_membership level="gold_member" message="Please join to view this content."]...[/yoaa_membership]`. Omit `message` for the default notice; `hide="yes"` hides the notice as well as the protected content.

Premium membership features include:

* Restrict posts, pages, and WooCommerce products by membership level.
* Create a membership products page.
* Allow customers to hold multiple membership roles.
* Configure access notices for product and membership commerce workflows.
* Offer membership-based product discounts.
* Support WooCommerce Subscriptions for recurring membership plans.

= Integrations =

Enable Cloudflare Turnstile, reCAPTCHA v2/v3, or hCaptcha in WooCommerce > Settings > Integration to protect the WordPress and WooCommerce login/registration forms. CAPTCHA is off by default and requires provider keys. Enabled protection fails when the provider rejects a token or cannot verify it; incomplete settings do not produce successful verification. This covers those forms, not every API or OTP request. Overlapping Blacklist Manager CAPTCHA is coordinated for the protected forms.

Advanced Accounts for WooCommerce works with these related tools:

* [Blacklist Manager](https://wordpress.org/plugins/wc-blacklist-manager/) - Recognizes verified email addresses during checkout checks; Premium phone verification can provide verified phone status.
* [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) - Premium membership plans can work with subscription products.
* [WooCommerce Loyalty - Points and Rewards](https://yoohw.com/product/woocommerce-loyalty/) - Premium membership roles can be used with loyalty rewards.

== Installation ==

1. Upload the `wc-advanced-accounts` folder to `/wp-content/plugins/`.
2. Activate **Advanced Accounts for WooCommerce** from **Plugins**.
3. Make sure WooCommerce is installed and active.
4. Go to **WooCommerce > Settings > Accounts & Membership** and open the Advanced, Profile, Membership, Endpoints, or Tools sections. CAPTCHA settings are in the existing **Integration** tab.
5. Enable only the account, email verification/OTP, or membership features your store needs.

== Frequently Asked Questions ==

= What does Advanced Accounts for WooCommerce do? =

It customizes WooCommerce customer accounts, adds email verification and OTP, supports phone-based accounts, and provides role-based membership content controls.

= Can I add OTP login to WooCommerce My Account? =

Yes. The free plugin supports OTP login and OTP password reset through email. Phone OTP is available in Premium through third-party providers such as Twilio or Textmagic.

= Can customers register with a phone number instead of an email address? =

Yes. You can use a phone number as the account username and keep the standard password-based login and password reset flow.

= Can I verify email addresses during WooCommerce registration? =

Yes. You can require email verification before a new customer account is fully activated.

= Can I create member-only content in WooCommerce? =

Yes. The free plugin includes membership roles and the `[yoaa_membership]` shortcode for showing or hiding content by role, logged-in status, or guest status.

= Can I restrict WooCommerce products by membership level? =

Product restriction is available in the Premium version. The free version focuses on role creation and shortcode-based content visibility.

= Does this plugin replace a full membership plugin? =

It covers lightweight membership access using WordPress roles and shortcodes. Stores that need product restrictions, discounts, multiple memberships, and membership plan pages can use the Premium version.

= Which messaging services are supported for phone verification and OTP? =

Twilio and Textmagic are available in the Premium version. If you need another provider, submit a request through https://yoohw.com/contact-us/; a typical provider integration takes 1–2 business days.

= Does the plugin send visitor IP geolocation or product feed requests? =

No. The free plugin does not send visitor IP geolocation, product-news subscription, or catalog feed requests.

= Is Advanced Accounts for WooCommerce compatible with HPOS? =

Yes. The plugin declares compatibility with WooCommerce High-Performance Order Storage.

== External Services ==

The free plugin sends email verification, OTP, and privacy confirmation through the site's WordPress mail system. CAPTCHA providers below are contacted only when configured and enabled for login or registration. Their scripts run in the visitor's browser and may collect device/browser and network information under the provider's policies; verification tokens and the configured secret key are sent to the provider's verification endpoint. Keys are configured by the store administrator. The free plugin does not connect to an external SMS service.

= Cloudflare Turnstile =

Browser script: https://challenges.cloudflare.com/turnstile/v0/api.js
Verification: https://challenges.cloudflare.com/turnstile/v0/siteverify
Terms: https://www.cloudflare.com/website-terms/
Privacy: https://www.cloudflare.com/privacypolicy/

= Google reCAPTCHA v2 and v3 =

Browser script: https://www.google.com/recaptcha/api.js
Verification: https://www.google.com/recaptcha/api/siteverify
Terms: https://policies.google.com/terms
Privacy: https://policies.google.com/privacy

= hCaptcha =

Browser script: https://js.hcaptcha.com/1/api.js
Verification: https://hcaptcha.com/siteverify
Terms: https://www.hcaptcha.com/terms
Privacy: https://www.hcaptcha.com/privacy

The following SMS providers are available only in the Premium version when configured by the store administrator.

= Twilio =

Service: https://www.twilio.com/

Twilio is an SMS provider available in the Premium version when explicitly enabled by the store administrator.

= Textmagic =

Service: https://www.textmagic.com/

Textmagic is an SMS provider available in the Premium version when explicitly enabled by the store administrator.

Other messaging providers can be requested through https://yoohw.com/contact-us/. Typical integration time is 1–2 business days.

No visitor IP geolocation, product-news subscription, or catalog feed request is sent by the free plugin.

== Bundled Libraries ==

* Font Awesome Free 6.7.2: bundled in `font/fontawesome/`. License details and source are available at https://fontawesome.com/license/free and https://github.com/FortAwesome/Font-Awesome.
* International Telephone Input 29.0.5: bundled in `js/intl-tel-input/`, including UI locale data. License and source are available at https://github.com/jackocnr/intl-tel-input.

== Changelog ==

= 1.5.0 (Nov 6, 2026) =
* New: Added first and last name registration fields, customer avatar upload/replacement/removal, and profile display-name formats to Free.
* New: Added Cloudflare Turnstile, reCAPTCHA v2/v3, and hCaptcha protection for supported WordPress and WooCommerce login and registration forms.
* New: Added administrator single and bulk account blocking/unblocking, including active-session revocation.
* New: Added email verification status controls and an email-only batch tool with dry-run and per-user results.
* New: Added customer personal-data erasure requests through the WordPress confirmation and administrator-processing workflow; accounts and orders are not automatically deleted.
* New: Added custom My Account menu links with URL, icon, order, visibility, and new-tab controls.
* New: Added the membership shortcode message attribute for custom denied-access notices.
* Security: Enforced login OTP resend limits on the server and checked account restrictions before OTP/reset sign-in continuation.
* Fix: Protected custom and companion-owned membership roles with safer role removal and deletion checks.
* Improve: Coordinated overlapping Free/Premium account controls and CAPTCHA integrations to avoid duplicate handling.
* Improve: Refined account settings, provider fields, batch results, custom-link dialogs, and desktop/mobile layouts; added contextual Premium comparisons and upgrade links that follow the administrator color scheme.
* Docs: Updated Free/Premium feature descriptions and external-service disclosures. New optional features remain disabled until configured by the store administrator.

For previous releases, see `changelog.txt`.
