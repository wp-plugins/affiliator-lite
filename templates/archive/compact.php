<table class="afl_table">
    <tr>
        <th class="afl_small_td"></th>
        <th>Προϊόν</th>
        <th>Τιμή</th>
        <th></th>
    </tr>
    <?php while ( have_posts() ) : the_post(); ?>
    <tr>
        <td><?php the_post_thumbnail(array(80,80))?></td>
        <td><div class="afl_title_wrapper">
                <a href="<?php the_permalink()?>" rel="bookmark"><?php the_title()?></a>
            </div></td>
        <td><?php global $affiliator; echo $affiliator->get_price()?></td>
        <td><a class="afl_button" href="<?php echo get_post_meta(get_the_ID(),'tracking_url',1)?>"><?php echo get_option('buy_now_button_text')?></a></td>
    </tr>
    <?php endwhile?>
</table>
<?php
the_posts_pagination( array(
    'prev_text'          => __( 'Προηγούμενη σελίδα', 'affiliator' ),
    'next_text'          => __( 'Επόμενη σελίδα', 'affiliator' ),
    'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Σελίδα', 'affiliator' ) . ' </span>',
) );
?>
