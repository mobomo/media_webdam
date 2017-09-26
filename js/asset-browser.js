(function ($, Drupal) {

    Drupal.behaviors.webdamAssetBrowser = {
        attach: function () {
            // Resize the asset browser frame.
            $(".webdam-asset-browser").height($(window).height() - 250);
            $(window).on('resize',function(){
              $(".webdam-asset-browser").height($(window).height() - 250);
            });
        }
    };

})(jQuery, Drupal);
