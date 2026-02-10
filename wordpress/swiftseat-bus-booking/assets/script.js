jQuery(function ($) {
  $('.ssb-toggle').on('click', function () {
    $(this).closest('.ssb-nav').toggleClass('open');
  });

  $('.ssb-toast[data-autohide]').each(function () {
    const el = $(this);
    setTimeout(() => {
      el.fadeOut(400, function () {
        el.remove();
      });
    }, parseInt(el.attr('data-autohide'), 10) || 3000);
  });
});
