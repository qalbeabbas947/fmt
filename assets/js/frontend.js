(function( $ ) { 'use strict';
    $( document ).ready( function() {
        var LDFMTFrontend = {
            init: function() {
                $('.ldfmt-plugins-filter').on('change', LDFMTFrontend.load_reviews).trigger('change');
                $('.ldfmt-sales-plugins-filter, .ldfmt-sales-interval-filter').on('change', LDFMTFrontend.load_sales).trigger('change');


                $(document).on('click', '.ldfmt_review_image-link', function(e) {
		
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
                    
                });
                
                //Click anywhere on the page to get rid of lightbox window 
                $('body').on('click', '#lightbox', function() { //must use on, as the lightbox element is inserted into the DOM 
                    $('#lightbox').hide();
                });

            },
            load_sales: function(e) {
                e.preventDefault();
                $('.ldmft-filter-sales').html('');
                $('.ldfmt-loader-div').css('display', 'block');
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
                    $('.ldfmt-loader-div').css('display', 'none');
                });
               
            },
            load_reviews: function(e) {
                e.preventDefault();
                $('.ldmft-filter-reviews').html('');
                var plugin = $('.ldfmt-plugins-filter').val();
                $('.ldfmt-loader-div').css('display', 'block');
                $.ajax({
                    method: "POST",
                    url: LDNFT.ajaxURL,
                    data: { action: 'ldnft_load_reviews', plugin_id: plugin },
                    cache: false,
                  })
                .done(function( html ) {
                    $('.ldmft-filter-reviews').html( html );
                    $('.ldfmt-loader-div').css('display', 'none');
                });
               
            }
        };

        LDFMTFrontend.init();
    });
})( jQuery );