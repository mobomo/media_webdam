(function ($, Drupal) {

    Drupal.behaviors.webdamAssetBrowser = {
        attach: function () {
            // Resize the asset browser frame.
            $(".webdam-asset-browser").height($(window).height() - 240);
        }
    };

})(jQuery, Drupal);
