<?php
/**
 * reviews shortcode template to display fixed number of records with no pagination.
 */
foreach( $results as $review ) {

    $client_name        = isset( $review->name ) ? $review->name : __( 'anonymous', 'ldninjas-freemius-toolkit' );
    $rating             = isset( $review->rate ) ? $review->rate : '';
    $title              = isset( $review->title ) ? $review->title : '';
    $description        = isset( $review->text ) ? $review->text : '';
    $client_profile_pic = isset( $review->profile_url ) ? $review->profile_url : ''; 
    ?>
    <div class="ldnft-reviews-onetime-wrap">
        <div class="ldnft-reviews-profile-wrap">
            <div class="ldnft-review-profile-img">
                <img src="<?php echo !empty( $client_profile_pic ) ? $client_profile_pic : LDNFT_ASSETS_URL .'images/customer-profile.png';?>">
            </div>
            <div class="ldnft-review-client-name"><?php echo $client_name; ?></div>
        </div>
        <div class="ldnft-rating-wrapper">
            <div class="ldnft-rating-star">
            <?php 
                
                $odd = false;
                for( $i = 1; $i <= 5; $i++ ) {

                    if( $rating%20 > 0 && $i * 20 > $rating && !$odd ) {
                        $selected = 'half';
                        $odd = true;
                    } elseif( $i * 20 <= $rating ) {
                        $selected = 'filled';
                    } elseif( $odd || $i * 20 > $rating ) {
                        $selected = 'empty';    
                    }

                    echo '<span class="ldnft-rating-star dashicons dashicons-star-'.$selected.'"></span>';
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