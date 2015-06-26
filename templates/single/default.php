<?php
// Start the loop.
while ( have_posts() ) : the_post();?>
<div class="afl_wrap wide afl_clearfix">
    <div class="afl_img_wrapper left">
        <?php the_post_thumbnail(array(500,500),array('class'=>'afl_transition'))?>
        <div class="afl_price_wrapper">
            <?php global $affiliator; echo $affiliator->get_price()?>
        </div>
        <div class="afl_buttons_wrapper">
            <a class="afl_button" href="<?php echo get_post_meta(get_the_ID(),'tracking_url',1)?>"><?php echo get_option('buy_now_button_text')?></a>
        </div>
        <div class="afl_more">
            <?php echo the_category('/')?>

        </div>
    </div>
    <div class="afl_other_wrapper right">
        <div class="afl_title_wrapper">
            <a href="<?php the_permalink()?>" rel="bookmark"><?php the_title()?></a>
        </div>
        <div class="afl_desc_wrapper_wide">
            <?php the_content()?>
        </div>

    </div>
</div>
<?php endwhile;?>