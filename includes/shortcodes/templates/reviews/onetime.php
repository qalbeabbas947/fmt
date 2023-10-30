<?php
/**
 * reviews shortcode template to display fixed number of records with no pagination.
 */

foreach( $results as $review ) { 
    ?>
        <div class="review-container">
            <?php if( ! empty( $review->picture ) ) { ?>
                <a class="ldfmt_review_image-link" href="<?php echo $review->profile_url;?>"><img class="ldfmt_review_image" src="<?php echo $review->picture;?>" alt="Avatar"></a>
            <?php } ?>
            <h3 class="ldfmt_review_title"><a class="ldfmt_review_title-link" href="<?php echo $review->sharable_img;?>" data-lightbox="ldfmt-set" data-title="<?php echo $review->title;?>"><?php echo $review->title;?></a></h3>
            <p class="ldfmt_review_user"><span><?php echo $review->name;?></span> of <?php echo !empty($review->company)?'<a href="'.$review->company_url.'">'.$review->company.'</a>':''; ?></p>
            <p class="ldfmt_review_description"><?php echo $review->text;?></p>
            <p class="ldfmt_review_time_wrapper">
                <div class="ldfmt_review_time"><?php echo $review->created;?></div>
                <div class="ldfmt_review_rate">
                    <?php echo __( 'Rate:', LDNFT_TEXT_DOMAIN );?> 
                    <div class="ldnft-rating-div">
                        <?php 
                            $rates = $review->rate;
                            for($i=1; $i<=5; $i++) {

                                $selected = '';
                                if( $i*20 <= $rates ) {
                                    $selected = 'ldnft-checked';
                                }

                                echo '<span class="fa fa-star '.$selected.'"></span>';
                            }
                        ?>
                    </div>    
                </div>
            </p>
        </div>
    <?php
    
}