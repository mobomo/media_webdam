(function ($, Drupal) {

    Drupal.behaviors.webdamAssetBrowser = {
        attach: function () {
            // Resize the asset browser frame.
            $(".webdam-asset-browser").height(270);
        }
    };

})(jQuery, Drupal);
