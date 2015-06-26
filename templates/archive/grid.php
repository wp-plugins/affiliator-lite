<ul class="afl_wrap afl_clearfix">
    <?php while ( have_posts() ) : the_post(); ?>
        <li>
            <div class="afl_img_wrapper ">
                <a href="<?php the_permalink()?>" rel="bookmark"><?php the_post_thumbnail(array(200,200),array('class'=>'afl_transition'))?></a>
            </div>
            <div class="afl_title_wrapper">
                <a href="<?php the_permalink()?>" rel="bookmark"><?php the_title()?></a>
            </div>
            <div class="afl_price_wrapper">
                <?php global $affiliator; echo $affiliator->get_price()?>
            </div>
            <div class="afl_buttons_wrapper">
                <a class="afl_button" href="<?php echo get_post_meta(get_the_ID(),'tracking_url',1)?>"><?php echo get_option('buy_now_button_text')?></a>
            </div>

        </li>
    <?php endwhile?>
</ul>
<?php
the_posts_pagination( array(
    'prev_text'          => __( 'Προηγούμενη σελίδα', 'affiliator' ),
    'next_text'          => __( 'Επόμενη σελίδα', 'affiliator' ),
    'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Σελίδα', 'affiliator' ) . ' </span>',
) );
?>
