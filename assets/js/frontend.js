(function( $ ) { 'use strict';
    $( document ).ready( function() {
        var LDFMTFrontend = {
            init: function() {
                $('.ldfmt-plugins-filter').on('change', LDFMTFrontend.load_reviews).trigger('change');
                $('.ldfmt-sales-plugins-filter, .ldfmt-sales-interval-filter').on('change', LDFMTFrontend.load_sales).trigger('change');
            },
            load_sales: function(e) {
                e.preventDefault();
                $('.ldmft-filter-sales').html('');
                var plugin = $('.ldfmt-sales-plugins-filter').val();
                var interval = $('.ldfmt-sales-interval-filter').val();
                var show_type = $('#ldfmt-sales-show-type').val();
                $.ajax({
                    method: "POST",
                    url: LDNFT.ajaxURL,
                    data: { action: 'ldnft_load_sales', plugin_id: plugin, interval: interval, show: show_type },
                    cache: false,
                  })
                .done(function( html ) {
                    $('.ldmft-filter-sales').html( html );
                });
               
            },
            load_reviews: function(e) {
                e.preventDefault();
                $('.ldmft-filter-reviews').html('');
                var plugin = $('.ldfmt-plugins-filter').val();
                
                $.ajax({
                    method: "POST",
                    url: LDNFT.ajaxURL,
                    data: { action: 'ldnft_load_reviews', plugin_id: plugin },
                    cache: false,
                  })
                .done(function( html ) {
                    $('.ldmft-filter-reviews').html( html );
                });
               
            }
        };

        LDFMTFrontend.init();
    });
})( jQuery );