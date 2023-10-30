<?php
/**
 * reviews shortcode template to display slider.
 */

$slide_index = 0;
foreach( $results as $review ) { 
    ?>
        <div class="slider-item <?php echo $slide_index == 0?'active':'';?>"><img src="<?php echo $review->sharable_img;?>" width="100%" /></div>
    <?php
}