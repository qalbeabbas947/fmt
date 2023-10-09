(function( $ ) { 'use strict';
    $( document ).ready( function() {
        var record_offset = 0;
        var record_per_page = 10;

        var record_sale_offset = 0;
        var record_sale_per_page =10;
        var LDFMTFrontend = {
            /**
             * displays record more buttons is clicked on sales
             */
            load_more_sales_records: function(e) {
                e.preventDefault();
                $('.ldfmt-load-more-btn').css('display', 'block');
                
                $('.ldfmt-loader-div-btm-reviews').css('display', 'block');
                var plugin = $('#ldfmt-sales-plugins-filter').val();
                var show_type = $('#ldfmt-sales-show-type').val();
                var link = $(this);
                link.css('disabled', true);
                $.ajax({
                    method: "POST",
                    url: LDNFT.ajaxURL,
                    data: { action: 'ldnft_load_sales', plugin_id: plugin, show: show_type, per_page:record_sale_per_page, offset:record_sale_offset },
                    cache: false,
                })
                .done(function( html ) {
                    $('.ldfmt-sales-list').append( html );
                    $('.ldfmt-loader-div-btm-reviews').css('display', 'none');
                    link.css('disabled', false);
                    
                    if( html.length==0 ) {
                        $('.ldfmt-load-more-sales-btn').css('display', 'none');
                    } else {
                        record_sale_offset += record_sale_per_page;
                    }
                });
            },
            /**
             * displays record more buttons is clicked on reviews
             */
            load_more_review_records: function(e) {
                e.preventDefault();
                var plugin = $('.ldfmt-plugins-filter').val();
                $('.ldfmt-loader-div-btm-sales').css('display', 'block');
                var link = $(this);
                link.css('disabled', true);
                $.ajax({
                    method: "POST",
                    url: LDNFT.ajaxURL,
                    data: { action: 'ldnft_load_reviews', plugin_id: plugin, per_page:record_per_page, offset:record_offset  },
                    cache: false,
                })
                .done(function( html ) {
                    $('.ldmft-filter-reviews').append( html );
                    $('.ldfmt-loader-div-btm-sales').css('display', 'none');
                    
                    link.css('disabled', false);
                    if( html.length==0 ) {
                        $('.ldfmt-load-more-btn').css('display', 'none');
                    } else {
                        record_offset += record_per_page;
                    }
                });
            },
            /**
             * displays image in popup
             */
            load_view_image: function(e) {
                //prevent default action (hyperlink) 
                e.preventDefault();
                                    
                //Get clicked link href 
                var image_href = $(this).attr("href");

                /* 
                    If the lightbox window HTML already exists in document, 
                    change the img src to to match the href of whatever link was clicked 
                    
                    If the lightbox window HTML doesn't exists, create it and insert it. 
                    (This will only happen the first time around) 
                */

                if ($('#lightbox').length > 0) { // #lightbox exists 
                    
                    //place href as img src value 
                    $('#content').html('<img src="' + image_href + '" />');
                    
                    //show lightbox window - you could use .show('fast') for a transition 
                    $('#lightbox').show();
                }

                else { //#lightbox does not exist - create and insert (runs 1st time only) 
                    
                    //create HTML markup for lightbox window 
                    var lightbox = 
                    '<div id="lightbox">' +
                        '<p>Click to close</p>' +
                        '<div id="content">' + //insert clicked link's href into img src 
                            '<img src="' + image_href +'" />' +
                        '</div>' +	
                    '</div>';
                        
                    //insert lightbox HTML into page 
                    $('body').append(lightbox);
                }
            },
            /**
             * close the image lightbox
             */
            close_image_lightbox: function(e) {
                $('#lightbox').hide();
            },
            /**
             * Initial function to load things
             */
            init: function() {
                $('.ldfmt-plugins-filter').on('change', LDFMTFrontend.load_reviews).trigger('change');
                
                $('.ldfmt-load-more-sales-btn').on('click', LDFMTFrontend.load_more_sales_records);
                $('.ldfmt-load-more-btn').on('click', LDFMTFrontend.load_more_review_records);
                $(document).on('click', '.ldfmt_review_image-link', LDFMTFrontend.load_view_image);
                LDFMTFrontend.load_sales();
                //Click anywhere on the page to get rid of lightbox window 
                $('body').on('click', '#lightbox', LDFMTFrontend.close_image_lightbox);

            },
            /**
             * displays record more buttons is clicked on sales
             */
            load_sales: function(e) {
                
                record_sale_offset = 0;
                $('.ldfmt-load-more-btn').css('display', 'block');
                $('.ldmft-filter-sales').html('');
                $('.ldfmt-loader-div').css('display', 'block');
                var plugin = $('#ldfmt-sales-plugins-filter').val();
                var show_type = $('#ldfmt-sales-show-type').val();
                $.ajax({
                    method: "POST",
                    url: LDNFT.ajaxURL,
                    data: { action: 'ldnft_load_sales', plugin_id: plugin, show: show_type, per_page:record_sale_per_page, offset:record_sale_offset },
                    cache: false,
                  })
                .done(function( html ) {
                    $('.ldmft-filter-sales').html( html );
                    $('.ldfmt-loader-div').css('display', 'none');
                    record_sale_offset = record_sale_per_page;
                });
               
            },
            /**
             * displays record more buttons is clicked on reviews
             */
            load_reviews: function(e) {
                e.preventDefault();
                record_offset = 0;
                $('.ldmft-filter-reviews').html('');
                var plugin = $('.ldfmt-plugins-filter').val();
                $('.ldfmt-load-more-btn').css('display', 'block');
                $('.ldfmt-loader-div').css('display', 'block');
                $.ajax({
                    method: "POST",
                    url: LDNFT.ajaxURL,
                    data: { action: 'ldnft_load_reviews', plugin_id: plugin, per_page:record_per_page, offset:record_offset  },
                    cache: false,
                  })
                .done(function( html ) {
                    $('.ldmft-filter-reviews').html( html );
                    $('.ldfmt-loader-div').css('display', 'none');
                    record_offset = record_per_page;
                });
               
            }
        };

        LDFMTFrontend.init();
    });
})( jQuery );