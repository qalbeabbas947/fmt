<?php
/**
 * reviews shortcode template to display the records with pagination.
 */

$slides         = '';
$slide_ctrl     = '';
$slide_index = 0;
foreach( $results as $review ) {

    $client_name = isset( $review->name ) ? $review->name : '';
    $rating = isset( $review->rate ) ? $review->rate : '';
    $title = isset( $review->title ) ? $review->title : '';
    $description = isset( $review->text ) ? $review->text : '';
    $client_profile_pic = isset( $review->profile_url ) ? $review->profile_url : '';
    ?>

    <div class="slider-item ldnft-reviews-wrapper">
        <div class="ldnft-reviews-profile-wrap">
            <div class="ldnft-review-profile-img">
                <img src="<?php echo !empty( $client_profile_pic ) ? $client_profile_pic : LDNFT_ASSETS_URL .'images/customer-profile.png'; ?>">
            </div>
            <div class="ldnft-review-client-name"><?php echo $client_name; ?></div>
        </div>
        <div class="ldnft-rating-wrapper">
            <div class="ldnft-rating-div">
              <?php 
                  for( $i = 1; $i <= 5; $i++ ) {

                      $selected = '';
                      if( $i * 2 <= $rating ) {
                          $selected = 'ldnft-checked';
                      }
                      echo '<span class="fa fa-star '.$selected.'"></span>';
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

$result_check = $wpdb->get_results( $wpdb->prepare( "SELECT r.*, c.email as useremail FROM $table_name where is_featured = 1 and r.plugin_id = %d ORDER BY r.id LIMIT %d OFFSET %d", $plugin_id, $per_page, ( $offset+$per_page ) ) );
if( is_array( $result_check ) && count( $result_check ) > 0 ) {
    echo '<input type="hidden" id="ldnft-is-loadmore-link" value="yes" />';
} else {
    echo '<input type="hidden" id="ldnft-is-loadmore-link" value="no" />';
}