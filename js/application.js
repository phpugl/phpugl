'use strict';

(function ($) {
  $('.language-switch a').click(function (e) {
    e.preventDefault();

    var $el = $(this),
        parentSwitch = $el.parents('.language-switch'),
        parentArticle = $el.parents('article'),
        activeBtn = $('a.btn-success', parentSwitch);


    $('*[lang="' + activeBtn.data('lang') + '"]', parentArticle).addClass('hide');
    activeBtn.removeClass('btn-success');
    $('*[lang="' + $el.data('lang') + '"]', parentArticle).removeClass('hide');
    $el.addClass('btn-success');
  });

  $('*[lang="en"]').addClass('hide');
}) (jQuery);
