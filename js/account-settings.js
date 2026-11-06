jQuery(function ($) {
  'use strict';

  var avatar = $('#yoaa_wc_enable_avatar');
  function toggleProfile() {
    if (avatar.length) $('#yoaa_wc_welcome_name_display').prop('disabled', !avatar.is(':checked'));
  }
  toggleProfile();
  avatar.on('change', toggleProfile);

  var provider = $('#yoaa_wc_captcha_selected');
  function toggleCaptcha() {
    var selected = provider.val();
    var fields = {
      recaptcha_v3: ['yoaa_wc_recaptcha_site_key', 'yoaa_wc_recaptcha_secret_key', 'yoaa_wc_recaptcha_score_threshold'],
      recaptcha_v2: ['yoaa_wc_recaptcha_v2_site_key', 'yoaa_wc_recaptcha_v2_secret_key'],
      cloudflare: ['yoaa_wc_cloudflare_site_key', 'yoaa_wc_cloudflare_secret_key'],
      hcaptcha: ['yoaa_wc_hcaptcha_site_key', 'yoaa_wc_hcaptcha_secret_key']
    };
    Object.keys(fields).forEach(function (key) {
      fields[key].forEach(function (id) { $('#' + id).closest('tr').toggle(selected === key); });
    });
    $('#yoaa_wc_enable_captcha_login, #yoaa_wc_enable_captcha_register').closest('tr').toggle(selected !== 'none');
  }
  if (provider.length) {
    toggleCaptcha();
    provider.on('change', toggleCaptcha);
  }

  var form = document.getElementById('mainform');
  if (form) {
    // Match Woo's Save behavior only after a valid explicit action actually submits.
    form.addEventListener('submit', function (event) {
      var button = event.submitter;
      if (button && (button.name === 'yoaa_email_batch_run' || button.id === 'yoaa-confirm-link')) {
        window.onbeforeunload = null;
      }
    });
  }

  // Enhance the inline add-link form with the same focused dialog flow as AAP.
  var fields = document.getElementById('yoaa-new-link-fields');
  var open = document.getElementById('yoaa-add-link');
  var dialog = document.getElementById('yoaa-add-link-dialog');
  var cancel = document.getElementById('yoaa-cancel-link');
  if (fields && open && dialog && typeof dialog.showModal === 'function') {
    dialog.appendChild(fields);
    open.hidden = false;
    cancel.hidden = false;
    open.addEventListener('click', function () {
      fields.querySelectorAll('input[type="text"], input[type="url"]').forEach(function (input) { input.required = true; });
      dialog.showModal();
    });
    cancel.addEventListener('click', function () { dialog.close(); });
    dialog.addEventListener('close', function () {
      fields.querySelectorAll('input').forEach(function (input) {
        input.required = false;
        if (input.type === 'checkbox') input.checked = false;
        else input.value = '';
      });
      open.focus();
    });
    fields.querySelector('button[type="submit"]').addEventListener('click', function (event) {
      var title = fields.querySelector('[name="new_custom_link_title"]');
      var url = fields.querySelector('[name="new_custom_link_url"]');
      title.value = title.value.trim();
      if (!title.value || !url.value.trim() || !url.checkValidity()) {
        event.preventDefault();
        var invalid = !title.value ? title : url;
        invalid.reportValidity();
        invalid.focus();
      }
    });
  }
});
