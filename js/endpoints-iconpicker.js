jQuery(function($){
  var icons   = YOAA_FA_ICONS,      // array of icon names, e.g. ["home","user",…]
      $overlay= $('#yoaa-icon-overlay'),
      $picker = $('#yoaa-icon-picker'),
      $search = $picker.find('#yoaa-icon-search'),
      $list   = $picker.find('.yoaa-icon-list');

  // Build list once: one icon per line, always using "fas"
  if ( ! $list.children().length ) {
    icons.forEach(function(name){
      var cls = 'fas fa-' + name;
      $list.append(
        '<div class="yoaa-icon-item" data-icon="'+cls+'" style="display:block;padding:6px 4px;cursor:pointer;border-bottom:1px solid #eee;">'+
          '<i class="'+cls+'" style="width:20px;display:inline-block;"></i>'+
          '<span style="margin-left:8px;vertical-align:middle;">'+ name +'</span>'+
        '</div>'
      );
    });
  }

  // 2) Open picker (same as before)
  $('#yoaa-endpoints-table').on('click', '.endpoint-icon-picker', function(e){
    e.preventDefault();
    var $btn       = $(this),
        $input     = $btn.siblings('input.endpoint-icon-input'),
        $container = $btn.closest('.endpoint-icon-container');

    $picker.appendTo($container).css({
      position: 'absolute',
      top:      $input.outerHeight() + 'px',
      left:     $input.position().left + 'px',
      width:    $input.outerWidth() + 'px'
    }).data('target-input', $input).show();

    $overlay.show();
    $search.val('').focus();
    $list.children().show();
  });

  // 3) Close on overlay click
  $overlay.on('click', function(){
    $overlay.hide();
    $picker.hide();
  });

  // 4) Filter on input
  $search.on('input', function(){
    var term = $(this).val().toLowerCase();
    $list.children().each(function(){
      var icon = $(this).data('icon').substr(3); // drop "fa-"
      $(this).toggle( term === '' || icon.indexOf(term) !== -1 );
    });
  });

  // 5) Pick icon
  $picker.on('click', '.yoaa-icon-item', function(){
    var cls    = $(this).data('icon'),
        $input = $picker.data('target-input');
    $input.val( cls );
    $overlay.hide();
    $picker.hide();
  });
});
