jQuery(document).ready(function($) {
    if ( $('form.woocommerce-form-register').length ) {
        var $regEmail    = $('#reg_email'),
            $regUsername = $('#reg_username'),
            domain       = siteData.siteDomain,
            $regForm     = $('form.woocommerce-form-register');

        function updateEmail(){
        var u = $regUsername.val().trim(),
            g = u && domain ? u + '@temp-email-' + domain : '';
        $regEmail.val(g);
        }

        // hide + strip validation
        $regEmail
        .closest('.woocommerce-form-row').hide()
        .end()
        .removeAttr('required')
        .removeAttr('aria-required');

        // bind & init
        $regUsername.on('input change paste keyup', updateEmail);
        updateEmail();

        // on submit, force a last update
        $regForm.on('submit', updateEmail);
    }

    // Append a hidden field to store the temporary email for account creation if it doesn't already exist
  if ( ! $('form.checkout').length ) {
    return;
  }

  var $acctUsername = $('#account_username'),
      $createChk    = $('#createaccount'),
      $billing      = $('#billing_email'),
      domain        = siteData.siteDomain,
      $form         = $('form.checkout');

  // Helper: toggle billing_email’s required attribute
  function toggleBillingRequired() {
    if ( $createChk.is(':checked') ) {
      // creating account → don’t require user to type an email
      $billing
        .removeAttr('required')
        .removeAttr('aria-required');
    } else {
      // normal checkout → force email
      $billing
        .attr('required','required')
        .attr('aria-required','true');
    }
  }

  // Initial state on page load
  toggleBillingRequired();

  // Whenever they (un)check “Create an account”
  $createChk.on('change', toggleBillingRequired);

  // On final submit, if they’ve requested an account, generate + inject
  $form.on('submit', function(){
    var user = $acctUsername.val().trim();
    if ( $createChk.is(':checked') && user ) {
      var gen = user + '@temp-email-' + domain;
      $billing.val( gen );
    }
  });
});
