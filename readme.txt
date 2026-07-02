=== Advanced Accounts for WooCommerce ===
Contributors: yoohw
Tags: woocommerce, my-account, otp-login, account-verification, membership
Requires at least: 6.3
Tested up to: 7.0
WC tested up to: 10.8
Requires PHP: 7.4
Stable tag: 1.4.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize WooCommerce My Account, add OTP login and verification, and control member-only content with roles.

== Description ==

Advanced Accounts for WooCommerce helps store owners improve the WooCommerce My Account experience with account verification, OTP login, password reset by OTP, and membership content access.

Use it to make customer accounts easier to manage, reduce fake registrations, verify phone or email ownership, and show selected content only to the right customers.

[Premium version](https://yoohw.com/product/woocommerce-advanced-accounts-premium/) | [Documentation](https://docs.yoohw.com/category/woocommerce-advanced-accounts-premium/) | [Support](https://yoohw.com/support/) | [Demo](https://sandbox.yoohw.com/demo/wcaa_demo.html)

= WooCommerce My Account Customization =

Improve the customer account area without editing theme templates. You can manage WooCommerce account behavior from plugin settings and keep the experience aligned with your store.

Free account features include:

* Redirect the default WordPress login page to WooCommerce My Account.
* Support phone number based account login.
* Disable email requirement when using phone-first registration.
* Keep checkout and account login flows connected to WooCommerce.
* Migrate existing customer usernames to phone-number based usernames with dry-run and batch controls.

Premium account customization features include:

* Add custom My Account endpoints.
* Customize endpoint labels, slugs, icons, order, and visibility.
* Show account tabs only to selected roles or membership levels.
* Add profile photos and extra registration fields.
* Block or unblock customer accounts without deleting user data.

= OTP Login and Account Verification =

Add secure one-time password flows for WooCommerce customers. The plugin supports OTP login, account registration verification, and password reset verification by phone or email.

Verification features include:

* WooCommerce OTP login by phone or email.
* Phone verification during account registration.
* Email verification during account registration.
* Password reset using OTP.
* Server-side OTP expiration, failed attempt limits, resend cooldown, and resend limits.
* Verified phone and email status for compatible checkout protection workflows.

SMS delivery is available through Yo Credits in the free plugin. Twilio and Textmagic are available in the Premium version when explicitly enabled.

= Membership Content Access =

Create membership levels with WordPress roles and use them to control who can see selected content.

Free membership features include:

* Create and manage membership roles.
* Use the `[yoaa_membership]` shortcode to show content to selected roles.
* Show or hide shortcode content for logged-in users or guests.
* Build simple member-only pages without a separate membership plugin.

Premium membership features include:

* Restrict posts, pages, and WooCommerce products by membership level.
* Create a membership products page.
* Allow customers to hold multiple membership roles.
* Show custom access notices for restricted content.
* Offer membership-based product discounts.
* Support WooCommerce Subscriptions for recurring membership plans.

= Integrations =

Advanced Accounts for WooCommerce works with these related tools:

* [Blacklist Manager](https://wordpress.org/plugins/wc-blacklist-manager/) - Recognizes verified phone numbers and email addresses during checkout checks.
* [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) - Premium membership plans can work with subscription products.
* [WooCommerce Loyalty - Points and Rewards](https://yoohw.com/product/woocommerce-loyalty/) - Premium membership roles can be used with loyalty rewards.

== Installation ==

1. Upload the `wc-advanced-accounts` folder to `/wp-content/plugins/`.
2. Activate **Advanced Accounts for WooCommerce** from **Plugins**.
3. Make sure WooCommerce is installed and active.
4. Go to **WooCommerce > Settings > Accounts & Privacy** and open the Advanced, Profile, Membership, Endpoints, or Tools sections.
5. Enable only the account, OTP, verification, or membership features your store needs.

== Frequently Asked Questions ==

= What does Advanced Accounts for WooCommerce do? =

It customizes WooCommerce customer accounts, adds OTP login and account verification, and provides role-based membership content controls.

= Can I add OTP login to WooCommerce My Account? =

Yes. Customers can log in with a one-time password sent to their phone or email, depending on the settings you enable.

= Can customers register with a phone number instead of an email address? =

Yes. You can enable phone number account login and use phone verification during registration.

= Does password reset by OTP work for phone numbers? =

Yes. When phone-based login is enabled, customers can request an OTP and use it to continue the WooCommerce password reset flow.

= Can I verify email addresses during WooCommerce registration? =

Yes. You can require email verification before a new customer account is fully activated.

= Can I create member-only content in WooCommerce? =

Yes. The free plugin includes membership roles and the `[yoaa_membership]` shortcode for showing or hiding content by role, logged-in status, or guest status.

= Can I restrict WooCommerce products by membership level? =

Product restriction is available in the Premium version. The free version focuses on role creation and shortcode-based content visibility.

= Does this plugin replace a full membership plugin? =

It covers lightweight membership access using WordPress roles and shortcodes. Stores that need product restrictions, discounts, multiple memberships, and membership plan pages can use the Premium version.

= Which SMS services are supported? =

The free plugin supports Yo Credits for SMS OTP delivery. Twilio and Textmagic are available in the Premium version when explicitly enabled.

= Does the plugin send visitor IP geolocation or product feed requests? =

No. The free plugin does not send visitor IP geolocation, product-news subscription, or catalog feed requests.

= Is Advanced Accounts for WooCommerce compatible with HPOS? =

Yes. The plugin declares compatibility with WooCommerce High-Performance Order Storage.

== External Services ==

This plugin connects to third-party or external services only when the related feature is enabled or triggered by an administrator or customer action.

= Yo Credits =

Service: https://yoohw.com/product/sms-credits/

Yo Credits is used to generate a site SMS key, send OTP messages for phone verification, OTP login, checkout verification, and password reset, and update remaining SMS quota.

Requests may include:

* Site domain.
* Administrator email when generating an SMS key.
* SMS key.
* Destination phone number.
* OTP message content.

= Twilio =

Service: https://www.twilio.com/

Twilio is an SMS provider available in the Premium version when explicitly enabled by the store administrator.

= Textmagic =

Service: https://www.textmagic.com/

Textmagic is an SMS provider available in the Premium version when explicitly enabled by the store administrator.

No visitor IP geolocation, product-news subscription, or catalog feed request is sent by the free plugin.

== Bundled Libraries ==

* Font Awesome Free 6.7.2: bundled in `font/fontawesome/`. License details and source are available at https://fontawesome.com/license/free and https://github.com/FortAwesome/Font-Awesome.
* International Telephone Input 29.0.5: bundled in `js/intl-tel-input/`, including UI locale data. License and source are available at https://github.com/jackocnr/intl-tel-input.

== Changelog ==

= 1.4.3 (June 13, 2026) =
* New: Added a Tools utility to migrate existing customer usernames to phone-number based usernames with dry-run previews, batch processing, progress tracking, conflict detection, and local-only or dial-local username formats.
* Security: Hardened OTP login, phone verification, and password reset verification with server-side expiration, failed attempt limits, resend cooldowns, resend limits, and automatic OTP invalidation.
* Security: Added stronger capability and nonce checks for settings, membership role management, endpoint saving, and checkout/account flows.
* Security: Improved SMS quota update protection with signed requests, replay-window validation, rate limiting, and safer response data.
* Fix: Improved phone and email input behavior on WooCommerce login, registration, and checkout forms, including phone formatting, placeholder overlap, strict-reject animation, and IME typing issues.
* Improve: Updated intl-tel-input to 29.0.5 with bundled UI locale data and WordPress locale matching for country selector text and country names.
* Improve: Updated WooCommerce checkout login template compatibility and declared HPOS compatibility.
* Improve: Refreshed WordPress.org metadata, bundled library information, external service disclosures, and the translation template.

= 1.4.2 (May 28, 2026) =
* Security: Hardened OTP login, phone verification, and password reset verification.
* Security: Added server-side OTP expiration, failed attempt limits, resend cooldown, resend limits, and automatic OTP invalidation.
* Security: Increased OTP length to 6 digits and improved OTP validation.
* Improve: Updated WooCommerce checkout login template compatibility.
* Improve: Added WooCommerce HPOS compatibility declaration.
* Improve: Cleaned WordPress.org package metadata, readme disclosures, and bundled library information.

For previous releases, see `changelog.txt`.
