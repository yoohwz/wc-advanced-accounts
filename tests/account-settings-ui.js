/* Browser state regressions: inactive settings retain values; cancelling never queues a link. */
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const controls = {};
function control(id, value = '') {
  return controls[id] = {value, checked: false, disabled: false, visible: true, listeners: {}};
}
const avatar = control('yoaa_wc_enable_avatar');
const name = control('yoaa_wc_welcome_name_display', 'first_last');
const provider = control('yoaa_wc_captcha_selected', 'none');
['recaptcha_site_key','recaptcha_secret_key','recaptcha_score_threshold','recaptcha_v2_site_key','recaptcha_v2_secret_key','cloudflare_site_key','cloudflare_secret_key','hcaptcha_site_key','hcaptcha_secret_key','enable_captcha_login','enable_captcha_register'].forEach(key => control('yoaa_wc_' + key, key + '-saved'));
function $(selector) {
  if (typeof selector === 'function') return selector($);
  const items = selector.split(',').map(s => controls[s.trim().slice(1)]).filter(Boolean);
  return {
    length: items.length,
    val: () => items[0]?.value,
    is: () => items[0]?.checked,
    prop: (key, value) => items.forEach(item => item[key] = value),
    on: (event, listener) => items.forEach(item => item.listeners[event] = listener),
    closest: () => ({toggle: visible => items.forEach(item => item.visible = visible)})
  };
}
function target(properties = {}) {
  return Object.assign({listeners: {}, addEventListener(event, handler) {this.listeners[event] = handler;}, focus() {this.focused = true;}}, properties);
}
const title = target({type: 'text', value: '', required: false, reportValidity() {this.reported = true;}});
const url = target({type: 'url', value: '', required: false, checkValidity() {return /^https?:\/\//.test(this.value);}, reportValidity() {this.reported = true;}});
const tab = target({type: 'checkbox', checked: false});
const submit = target({id: 'yoaa-confirm-link'});
const form = target();
const window = {onbeforeunload: 'warn'};
const fields = target({
  querySelectorAll(selector) {return selector.includes('input[type=') ? [title,url] : [title,url,tab];},
  querySelector(selector) {return selector.includes('submit') ? submit : selector.includes('title') ? title : url;}
});
const open = target({hidden: true});
const cancel = target({hidden: true});
const dialog = target({open: false, appendChild(child) {this.child = child;}, showModal() {this.open = true;}, close() {this.open = false;this.listeners.close();}});
const document = {getElementById(id) {return {'mainform':form,'yoaa-new-link-fields':fields,'yoaa-add-link':open,'yoaa-add-link-dialog':dialog,'yoaa-cancel-link':cancel}[id];}};
vm.runInNewContext(fs.readFileSync('js/account-settings.js','utf8'), {jQuery:$,document,window});
assert(name.disabled && name.value === 'first_last');
avatar.checked = true; avatar.listeners.change(); assert(!name.disabled && name.value === 'first_last');
avatar.checked = false; avatar.listeners.change(); assert(name.disabled && name.value === 'first_last');
for (const type of ['cloudflare','recaptcha_v2','recaptcha_v3','hcaptcha','none']) {
  provider.value = type; provider.listeners.change();
  const group = type === 'recaptcha_v3' ? 'recaptcha' : type;
  ['recaptcha','recaptcha_v2','cloudflare','hcaptcha'].forEach(key => {
    assert.strictEqual(controls['yoaa_wc_' + key + '_site_key'].visible, key === group);
    assert.strictEqual(controls['yoaa_wc_' + key + '_secret_key'].disabled, false);
    assert.strictEqual(controls['yoaa_wc_' + key + '_secret_key'].value, key + '_secret_key-saved');
  });
  assert.strictEqual(controls.yoaa_wc_enable_captcha_login.visible, type !== 'none');
  assert.strictEqual(controls.yoaa_wc_recaptcha_score_threshold.visible, type === 'recaptcha_v3');
}
assert(!open.hidden && dialog.child === fields);
open.listeners.click(); assert(dialog.open && title.required && url.required);
title.value = 'Support';url.value='https://example.test/help';tab.checked=true;
cancel.listeners.click();assert(!dialog.open && title.value==='' && url.value==='' && !tab.checked && !title.required && !url.required && open.focused);
open.listeners.click();title.value='   ';url.value='bad';let prevented=false;
submit.listeners.click({preventDefault() {prevented=true;}});assert(prevented && title.reported);
title.value='Support';url.value='https://example.test/help';prevented=false;
submit.listeners.click({preventDefault() {prevented=true;}});assert(!prevented);
form.listeners.submit({submitter:{name:'other'}});assert.strictEqual(window.onbeforeunload,'warn');
form.listeners.submit({submitter:submit});assert.strictEqual(window.onbeforeunload,null);
window.onbeforeunload='warn';form.listeners.submit({submitter:{name:'yoaa_email_batch_run'}});assert.strictEqual(window.onbeforeunload,null);
console.log('Account settings UI state regressions PASS');
