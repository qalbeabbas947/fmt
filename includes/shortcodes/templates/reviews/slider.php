<?php
/**
 * reviews shortcode template to display slider.
 */

 /**
   * 
   * Enqueue specific js and css
   */
wp_enqueue_style( 'ldnft-bxslider-css' );
wp_enqueue_style( 'ldnft-lightbox-css' );

wp_enqueue_script( 'jquery' );
wp_enqueue_script( 'ldnft-bxslider-js' );
wp_enqueue_script( 'ldnft-lightbox-js' );
wp_enqueue_script( 'ldnft-frontend-js' );

$slide_index = 0;
?><div class="bxslider"><?php
foreach( $results as $review ) {
  
    $client_name        = isset( $review->name ) ? $review->name : __( 'anonymous', 'ldninjas-freemius-toolkit' );
    $rating             = isset( $review->rate ) ? $review->rate : '';
    $title              = isset( $review->title ) ? $review->title : '';
    $description        = isset( $review->text ) ? $review->text : '';
    $client_profile_pic = isset( $review->picture ) ? $review->picture : '';
    ?>
        <div class="slider-item ldnft-reviews-wrapper <?php echo $slide_index == 0?'active':'';?>">
            <div class="ldnft-reviews-profile-wrap">
                <div class="ldnft-review-profile-img">
                    <img src="<?php echo !empty( $client_profile_pic ) ? $client_profile_pic : LDNFT_ASSETS_URL .'images/customer-profile.png';?>">
                </div>
                <div class="ldnft-review-client-name"><?php echo $client_name; ?></div>
            </div>
            <div class="ldnft-rating-wrapper">
            <div class="ldnft-rating-div">
                <?php 
                    for( $i = 1; $i <= 5; $i++ ) {

                        $selected = 'empty';
                        if( $i * 20 <= $rating ) {
                            $selected = 'filled';
                        }
                        echo '<span class="dashicons dashicons-star-'.$selected.'"></span>';
                    }
                ?>
                </div> 
            </div>
            <div class="ldnft-reviews-title-wrapper">
                <?php echo $title; ?>
            </div>
            <div class="ldnft-reviews-description-wrapper">
                <?php echo $description; ?>
            </div>
        </div>
    <?php
}
?>
</div>