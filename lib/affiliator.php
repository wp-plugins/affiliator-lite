<?php
define('LW_XML_FORMAT', 'PRODUCT_FEED/PROGRAM/PRODUCT/');
define('FV_XML_FORMAT', 'PRODUCTS/PRODUCT/');
class affiliator{


    function __construct(){

    }

    function deactivate(){
        flush_rewrite_rules();
        $lw = get_page_by_path('linkwise-affiliate-network','OBJECT','networks');
        $fv = get_page_by_path('forestview-affiliate-network','OBJECT','networks');

        wp_delete_post( $lw->ID, true );
        wp_delete_post( $fv->ID, true );
    }

    function check_is_post(){
        if(isset($_POST['ajaction']) || isset($_POST['fields']['ajaction'])){
            $action = ($_POST['ajaction'])?$_POST['ajaction']:$_POST['fields']['ajaction'];
            switch($action){
                case "populate_products":
                    return $this->parse_feed();
                    break;
                case "populate_programs":
                    return $this->select_network();
                    break;
                case "addprogram":
                    return $this->addprogram($_POST['fields']['program']);
                    break;
                case "addproduct":
                    return $this->addproduct();
                    break;
                case "removeproduct":
                    return $this->removeproduct();
                    break;

            }
        }
        if(isset($_POST['action']) && $_POST['action']=='update'){
            $this->savesettings();
        }
    }

    function activate()
    {
        global $wpdb, $jal_db_version;

        $this->create_post_types();
        flush_rewrite_rules();





        $post = array(
            'ID' => '',
            'post_content' => 'H Linkwise αποτελεί το πρώτο και μεγαλύτερο Affiliate Δίκτυο στην Ελλάδα. Από το 2008 που ιδρύθηκε, οδηγεί συνεχώς τις εξελίξεις στον τομέα του Affiliate Marketing και συνεργάζεται με τα μεγαλύτερα ελληνικά και διεθνή brands με παρουσία στον ελληνικό online χώρο. Αποκορύφωμα της πορείας της αποτελεί το λανσάρισμα της δικής της custom-built πλατφόρμας το 2012 (της μοναδικής στην αγορά), μια κίνηση που ανεβάζει στο επόμενο επίπεδο τις υπηρεσίες της και ως αποτέλεσμα το Affiliate Marketing στην Ελλάδα. ',
            'post_name' => 'linkwise-affiliate-network',
            'post_title' => 'Linkwise Affiliate Network',
            'post_status' => 'publish',
            'post_type' => 'networks',
            'post_author' => '1',
            'ping_status' => 'closed',

        );wp_insert_post($post);

        $post = array(
            'ID' => '',
            'post_content' => 'ForestView provides access to the most profitable affiliate programs in the European market. Our experience and expertise attracts some of the most respected and well-known advertisers to run their performance campaigns with us.

    Access to leading programs and high-quality, niche and fast-developing brands across the board
    Attractive commission rates across all programs, with additional rewards and bonuses for top performers
    Timely and easy payout options - We work with you to find the best payment plan
    Straightforward and easy-to-use user interface offers all the information you need on the progress of your programs
    Dedicated account management and consultancy on individual programs
    Ongoing communication and support - including latest updates, new program launches, promotions and events
',
            'post_name' => 'forestview-affiliate-network',
            'post_title' => 'ForestView Affiliate Network',
            'post_status' => 'publish',
            'post_type' => 'networks',
            'post_author' => '1',
            'ping_status' => 'closed',

        );wp_insert_post($post);

    }

    function plugin_menu()
    {

        $hook = add_menu_page('Affiliator Options', 'Affiliator', 'manage_options', 'affiliator-plugin-menu', array($this,'plugin_options'), 'dashicons-store', 100);
        add_submenu_page('affiliator-plugin-menu', 'Networks', 'Affiliate Δίκτυα', 'manage_options', 'affiliator-plugin-networks', array($this,'plugin_networks'));
        add_submenu_page('affiliator-plugin-menu', 'Sites', 'Affiliate Προγράμματα', 'manage_options', 'affiliator-plugin-sites', array($this,'plugin_sites'));
        add_submenu_page('affiliator-plugin-menu', 'Products', 'Εισαγωγή Προϊόντων', 'manage_options', 'affiliator-plugin-products', array($this,'plugin_products'));
        add_action('load-'.$hook,array($this,'flush_permalinks'));
    }

    function flush_permalinks()
    {
        flush_rewrite_rules();
    }

    function affiliator_admin_notice_update() {
        ?>
        <div class="updated">
            <p><?php _e( 'Υπάρχει νέα έκδοση του Affiliator!', 'affiliator' ); ?></p>
        </div>
    <?php
    }






    function get_price(){
        $price = get_post_meta(get_the_ID(),'price',1);
        $fullprice = get_post_meta(get_the_ID(),'full_price',1);

        if(!trim($fullprice)){
            return $price;
        }
        $percent = round(100 - (($price/$fullprice) *100));
        $diafora = $fullprice - $price;
        return "<span class='afl_price_wrapper'><span class='afl_old_price'>{$fullprice}&euro;</span> <span class='afl_current_price'>{$price}&euro;</span><span class='afl_sale_percent'>{$diafora}&euro; ({$percent}%)</span> </span>";
    }
    function plugin_options()
    {
        echo '<div class="wrap">';
        wp_enqueue_media();
        echo $this->lumen_get_contents('demo');
        echo $this->lumen_get_contents('options');
        echo $this->lumen_get_contents('marketing');

        echo '</div>';
    }


    function lumen_get_contents($what){
        return $this->api($what);
    }

    function plugin_networks()
    {
        echo '<div class="wrap">';
        echo $this->lumen_get_contents('demo');
        echo $this->lumen_get_contents('networks');
        echo '</div>';
    }

    function plugin_sites(){
        echo '<div class="wrap">';
        echo $this->lumen_get_contents('demo');
        echo $this->lumen_get_contents('programs');
        ?>
        <script>

            jQuery(document).on('click','[data-extra="ajaxpost"]',function(e){
                e.preventDefault();
                jQuery(this).parent().find('.ajaxresult').show();
                var ele = jQuery(this);
                var thefields = {};
                jQuery(this).parent().parent().find('input,select').each(function(){
                    if(jQuery(this).prop('disabled')){

                    }else{
                        thefields[jQuery(this).attr('name')]=jQuery(this).attr('value');
                    }

                });


                jQuery.post(jQuery(this).attr('href'),{
                    fields: thefields
                },function(response){
                    if(response=='Ok'){
                        ele.parent().find("[name='ajaction']").val('removeproduct');
                        ele.html('Αφαίρεση').removeClass('button-primary');
                    }else{
                        ele.parent().find("[name='ajaction']").val('addproduct');
                        ele.html('Προσθήκη').addClass('button-primary');
                    }
                    ele.parent().find('.ajaxresult').hide();
                });
                return false;
            });

        </script>
        <?php
        echo '</div>';
    }


    function plugin_products(){
        echo '<div class="wrap">';
        echo $this->lumen_get_contents('demo');
        echo $this->lumen_get_contents('products');
        ?>
        <script>
            var $ = jQuery;
            jQuery(document).on('click','[data-extra="ajaxpost"]',function(e){
                e.preventDefault();
                jQuery(this).parent().find('.ajaxresult').show();
                var ele = jQuery(this);
                var thefields = {};
                jQuery(this).parent().parent().find('input,select').each(function(){
                    if(jQuery(this).prop('disabled')){

                    }else{
                        thefields[jQuery(this).attr('name')]=jQuery(this).attr('value');
                    }

                });

                jQuery.post(jQuery(this).attr('href'),{
                    fields: thefields
                },function(response){
                    if(response=='Ok'){
                        ele.parent().find("[name='ajaction']").val('removeproduct');
                        ele.html('Αφαίρεση').removeClass('button-primary');
                    }else{
                        ele.parent().find("[name='ajaction']").val('addproduct');
                        ele.html('Προσθήκη').addClass('button-primary');
                    }
                    ele.parent().find('.ajaxresult').hide();
                });
                return false;
            });

            jQuery(document).on('change',".checkall",function(){
                if($(this).is(":checked")){
                    $(".checkme").prop('checked',$(this).attr('checked'));
                    $(".checkall").prop('checked',$(this).attr('checked'));
                }else{
                    $(".checkme").prop('checked',false);
                    $(".checkall").prop('checked',false);
                }
                $(".checkme").trigger('change');
            });
            jQuery(document).on('click',"[name='wp_cat']",function(){
                if(jQuery(this).is(':checked')){
                    jQuery(this).parent().parent().find('#cat').prop('disabled',false).show();
                    jQuery(this).parent().parent().find(".hiddenmsg").hide();
                }else{
                    jQuery(this).parent().parent().find('#cat').prop('disabled',true).hide();
                    jQuery(this).parent().parent().find(".hiddenmsg").show();
                }

            });

            jQuery(document).on('click','.postall',function(e){
                var ele = $(this);
                $(".checkme").each(function(){
                    if($(this).is(":checked")){
                        $(this).parent().parent().find("input[name='tags']").val(ele.parent().find("input[name='tags']").val());

                        if(ele.parent().find("#cat").prop('disabled')){
                            $(this).parent().parent().find("#cat").prop('disabled',true);
                        }else{
                            $(this).parent().parent().find("#cat").val(ele.parent().find("#cat").val());
                        }

                        $(this).parent().parent().find(".button").trigger('click');
                    }
                });
                return false;
            })
        </script>
        <?php
        echo '</div>';
    }

    function get_contents($what)
    {
        $res = file_get_contents(plugins_url( "lumen/public/api/{$what}", __FILE__ ));
        return $res;
    }





    function add_permastruct( $rules ) {
        global $wp_rewrite;




        // set your desired permalink structure here
        $struct = get_option('affiliator_permalink_struct');
//
//    // use the WP rewrite rule generating function
        $rules = $wp_rewrite->generate_rewrite_rules(
            $struct,       // the permalink structure
            EP_PERMALINK,  // Endpoint mask: adds rewrite rules for single post endpoints like comments pages etc...
            false,         // Paged: add rewrite rules for paging eg. for archives (not needed here)
            false,          // Feed: add rewrite rules for feed endpoints
            false,          // For comments: whether the feed rules should be for post comments - on a singular page adds endpoints for comments feed
            true,         // Walk directories: whether to generate rules for each segment of the permastruct delimited by '/'. Always set to false otherwise custom rewrite rules will be too greedy, they appear at the top of the rules
            true           // Add custom endpoints
        );
//    echo "<pre>";
//    print_r($rules);
//    exit;

        return $rules;
    }

    function pre_get_posts( $query ) {
        global $wpdb;
        $type = get_option('products_post_type_name')?get_option('products_post_type_name'):'products';
        $p = $wpdb->get_row( 'SELECT * FROM '.$wpdb->prefix.'posts WHERE post_name = "'.$query->query['name'].'"', OBJECT );

        if ( ! is_admin() && $query->is_main_query() ) {

            if($p->post_type==$type){
                $query->set('post_type',$p->post_type);
                $query->is_home = FALSE; // Tell WordPress we are not at home page
            }elseif($query->query['category_name']){
                $query->set('post_type','any');
            }
        }

//    echo "<pre>";
//    print_r($query);
//    exit;
    }



    function custom_post_permalink( $permalink, $post, $leavename, $sample ) {

        $type = get_option('products_post_type_name')?get_option('products_post_type_name'):'products';

        // only do our stuff if we're using pretty permalinks
        // and if it's our target post type
        if ( $post->post_type == $type && get_option( 'permalink_structure' ) ) {

            // remember our desired permalink structure here
            // we need to generate the equivalent with real data
            // to match the rewrite rules set up from before

            $struct = get_option('affiliator_permalink_struct');

            $rewritecodes = array(
                '%category%',

                '%year%',
                '%monthnum%',
                '%day%',
                '%postname%',
                '%post_id%'
            );



            // setup data
            $terms = get_the_terms($post->ID, 'category');
            $unixtime = strtotime( $post->post_date );

            // this code is from get_permalink()
            $category = '';

            $cats = get_the_category($post->ID);

            if ( $cats ) {
                usort($cats, '_usort_terms_by_ID'); // order by ID
                $category = $cats[0]->slug;
//                if ( $parent = $cats[0]->parent )
//                    $category = get_category_parents($parent, false, '/', true) . $category;
            }
            // show default category in permalinks, without
            // having to assign it explicitly
            if ( empty($category) ) {
                $default_category = get_category( get_option( 'default_category' ) );
                $category = is_wp_error( $default_category ) ? '' : $default_category->slug;
            }


            $replacements = array(
                $category,

                date( 'Y', $unixtime ),
                date( 'm', $unixtime ),
                date( 'd', $unixtime ),
                $post->post_name,
                $post->ID
            );

            // finish off the permalink
            $permalink = home_url( str_replace( $rewritecodes, $replacements, $struct ) );
            $permalink = user_trailingslashit($permalink, 'single');
        }

        return $permalink;
    }


    function close_connection(){

        //make it a little more user friendly...
        ignore_user_abort(true);
        ob_start();
        echo('<!--THIS IS A VERY LONG COMMENT THAT IS NEEDED IN ORDER TO CLOSE THE CONNECTION. CLOSING THE CONNECTION WILL ALLOW THE USER TO LOAD THE PAGE WITHOUT WAITING FOR THIS SCRIPT TO END. THANK YOU FOR READING THIS SIR!-->');
        $size = ob_get_length();
        header("Content-Length: $size");
        header("Connection: close");
        ob_end_flush();
        ob_flush();
        flush();// Yeap! We need all of them.

    }




    function check_post(){

        if(!defined('AFFILIATOR_RUNNING')){
            if(get_option('auto_post_internal')=='on'){
                $this->close_connection();
                $this->lumen_get_contents('checkpost/1');
            }else{
                if(isset($_GET['affiliator_check_post']) && $_GET['affiliator_check_post']===substr(NONCE_KEY,0,5)){
                    $this->lumen_get_contents('checkpost/'.$_GET['check']);
                }
            }
            define('AFFILIATOR_RUNNING',true);
        }
        return;
    }



    function create_post_types()
    {
        register_post_type('networks',
            array(
                'labels' => array(
                    'name' => __('Δίκτυα'),
                    'singular_name' => __('Δίκτυο')
                ),
                'public' => true,
                'has_archive' => false,
                'show_ui' => true,
                'map_meta_cap' => true,
                'supports' => array(
                    'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', ''
                ),
                'menu_icon' => 'dashicons-networking',
                'menu_position' => 110
            )
        );

        register_post_type('merchants',
            array(
                'labels' => array(
                    'name' => __('Προγράμματα'),
                    'singular_name' => __('Πρόγραμμα')
                ),
                'public' => true,
                'has_archive' => true,
                'show_ui' => true,
                'map_meta_cap' => true,
                'taxonomies' => array(
                    'category', 'post_tag'
                ),
                'supports' => array(
                    'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', ''
                ),
                'menu_icon' => 'dashicons-admin-site',
                'menu_position' => 115
            )
        );

        if(get_option( 'products_post_type' )=='on'){
            $type = get_option('products_post_type_name')?get_option('products_post_type_name'):'products';
            register_post_type($type,
                array(
                    'labels' => array(
                        'name' => __('Προϊόντα'),
                        'singular_name' => __('Προϊόν')
                    ),
                    'public' => true,
                    'has_archive' => true,
                    'show_ui' => true,
                    'map_meta_cap' => true,
                    'taxonomies' => array(
                        'category', 'post_tag'
                    ),

                    'supports' => array(
                        'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'post-formats'
                    ),
                    'menu_icon' => 'dashicons-products',
                    'menu_position' => 120
                )
            );

        }



    }



    function custom_template($template) {

        wp_enqueue_style( 'affiliator_plugin_style', plugins_url( '/../templates/style.css', __FILE__ ));
        // Post ID
        $post_id = get_the_ID();

        $type = get_option('products_post_type_name');


        // For all other CPT
        if ( get_post_type( $post_id ) != $type ) {
            return $template;
        }

        // Else use custom template
        if ( is_single() ) {
            return __DIR__.'/../templates/single.php';
        }

        //else return archive
        return __DIR__.'/../templates/archive.php';
    }

    function get_single_template(){
        $tpl = get_option( 'products_single_template' );

        include __DIR__.'/../templates/single/'.$tpl.'.php';
    }

    function get_archive_template(){
        $tpl = get_option( 'products_archive_template' );

        include __DIR__.'/../templates/common/views.php';
        $tpl = ($_GET['mode'])?$_GET['mode']:$tpl;

        include __DIR__.'/../templates/archive/'.$tpl.'.php';
    }

    function affiliator_get_option($option, $default = '')
    {

        $options = get_option($option);

        if (trim($options)) {
            return $options;
        }

        return $default;
    }

    private function affiliator_set_option($option, $value)
    {
        add_option( $option, $value, '', 'yes' );
    }

    public function addprogram($id)
    {

        if (substr($id,0,3)== 'fv_') {

            if(!$programs = wp_cache_get('fv_programs')){
                $programs = file_get_contents('http://affiliate.gurus.gr/fv.php');
                wp_cache_add('fv_programs',$programs,'affiliator_fv',3600);
            }
            $programs = json_decode($programs, 1);
            $programs = $programs['data']['offers'];
            $id = str_replace('fv_', '', $id);
            $is_fv = true;
        } else {

            if(!$programs = wp_cache_get('lw_programs')){
                $programs = file_get_contents('http://affiliate.gurus.gr/lw.php');
                wp_cache_add('lw_programs',$programs,'affiliator_lw',3600);
            }
            $programs = json_decode($programs, 1);
            $is_fv = false;
        }


        foreach ($programs as $k => $v) {
            if ($v['id'] == $id) {
                $program = $v;
            }
        }
        $post = array(
            'ID' => '',
            'post_content' => $program['description'],
            'post_title' => $program['name'],
            'post_status' => 'publish',
            'post_type' => 'merchants',
            'post_author' => '1',
            'post_excerpt' => ($is_fv) ? '' : $program['short_description'],
            'ping_status' => 'closed',
        );

        $pid = wp_insert_post($post);

        if (!$is_fv) {
            add_post_meta($pid, 'logo', $program['logo']);
        }

        add_post_meta($pid, 'datafeed', $_POST['fields']['feedurl']);
        add_post_meta($pid, 'last_checked', time() - 86400);

        if ($is_fv) {
            $this->affiliator_set_option('program_fv_' . $program['id'], $pid);
        } else {
            $this->affiliator_set_option('program_' . $program['id'], $pid);
        }

        echo 'Ok';
        exit;
    }


    public function postFromMerchant($feed, $program)
    {


        if(!$file = wp_cache_get('post_datafeed_' . $program)){
            $file  = file_get_contents($feed);
            wp_cache_add('post_datafeed_' . $program,$file,'affiliator_post_'.$program,3600);
        }

        list($format, $file) = $this->decideFormat($feed, $file);

        $handle = fopen(__DIR__ . '/../feeds/' . $program . '_posted.xml', 'w');

        fwrite($handle, $file);
        fclose($handle);



        Parser_start(__DIR__ . '/../feeds/' . $program . '_posted.xml', array($this, 'parse_helper'), $format);
        $passed = array();
        foreach ($this->parsed as $k => $fields) {
            if (!in_array($fields['LW_PRODUCT_ID'], $passed)) {
                if (get_option('affiliator_auto_post_limit') && $k <= get_option('affiliator_auto_post_limit')) {

                    if (!$this->affiliator_get_option('product_' . $fields['LW_PRODUCT_ID'], false)) {
                        if ($cid = get_option('auto_assign_catid')) {
                            $categories = array($cid);
                        } else {
                            $categories = $this->parseCategories($fields['CATEGORY']);
                        }

                        $type = (get_option('products_post_type') == 'on') ? 'products' : 'post';

                        $post_status = get_option('affiliator_post_status');

                        $post = array(
                            'ID' => '',
                            'post_content' => $fields['DESCRIPTION'],
                            'post_title' => $fields['PRODUCT_NAME'],
                            'post_status' => $post_status,
                            'post_type' => $type,
                            'post_author' => '1',
                            'ping_status' => 'closed',
                            'post_category' => $categories
                        );

                        $pid = wp_insert_post($post);

                        //add tags
                        if (trim($fields['tags'])) {
                            wp_set_post_tags($pid, $fields['tags'], false);
                        }


                        //add thumbnail
                        if (!$this->attachImageToPost($pid, $fields['IMAGE_URL']) || !isset($fields['PRICE'])) {
                            $this->removeproduct($fields);
                            continue;
                        }

                        unset($fields['DESCRIPTION']);
                        unset($fields['PRODUCT_NAME']);
                        foreach ($fields as $k => $v) {
                            if (trim($k) && trim($v)) {
                                add_post_meta($pid, strtolower($k), $v);
                            }
                        }
                        $this->affiliator_set_option('product_' . $fields['LW_PRODUCT_ID'], $pid);

                    }
                }
                $passed[] = $fields['LW_PRODUCT_ID'];
            }
        }
        return true;
    }

    private function transform_to_lw($file, $net)
    {
        $search = array('ID>', 'NAME>', 'CATEGORY_NAME>', 'PRODUCT_URL>', 'PRICE_WITH_VAT>');
        $replace = array('LW_PRODUCT_ID>', 'PRODUCT_NAME>', 'CATEGORY>', 'TRACKING_URL>', 'PRICE>');

        $file = str_replace($search, $replace, $file);
        return $file;
    }

    public function removeproduct($fields = null)
    {
        if (is_null($fields)) {
            $fields = $_POST['fields'];
        }

        $postid = $this->affiliator_get_option('product_' . $fields['LW_PRODUCT_ID']);
        wp_delete_post($postid, true);
        unset($fields['DESCRIPTION']);
        unset($fields['PRODUCT_NAME']);
        foreach ($fields as $k => $v) {
            if (trim($k) && trim($v)) {
                delete_metadata('post', $postid, strtolower($k));
            }
        }

        delete_option( 'product_' . $fields['LW_PRODUCT_ID'] );



        echo "Done";
        exit;
    }

    public function addproduct()
    {
        $fields = $_POST['fields'];

        if (isset($fields['cat'])) {
            $categories = array($fields['cat']);
        } else {
            $categories = $this->parseCategories($fields['CATEGORY']);
        }



        $type = (get_option('products_post_type') == 'on') ? 'products' : 'post';

        $post_status = get_option('affiliator_post_status');

        $post = array(
            'ID' => '',
            'post_content' => $fields['DESCRIPTION'],
            'post_title' => $fields['PRODUCT_NAME'],
            'post_status' => $post_status,
            'post_type' => $type,
            'post_author' => '1',
            'ping_status' => 'closed'
        );

        $pid = wp_insert_post($post);

        wp_set_post_categories( $pid, $categories);

        //add tags
        if (trim($fields['tags'])) {
            wp_set_post_tags($pid, $fields['tags'], false);
        }


        //add thumbnail
        $this->attachImageToPost($pid, $fields['IMAGE_URL']);

        unset($fields['DESCRIPTION']);
        unset($fields['PRODUCT_NAME']);
        unset($fields['ajaction']);
        foreach ($fields as $k => $v) {
            if (trim($k) && trim($v)) {
                add_post_meta($pid, strtolower($k), $v);
            }
        }
        $this->affiliator_set_option('product_' . $fields['LW_PRODUCT_ID'], $pid);
        echo  'Ok';
        exit;
    }

    private function parseCategories($str)
    {
        $delim = $this->detect_delimiter($str, null);
        $delim = (trim($delim)) ? $delim : '/';
        $path = explode($delim, $str);
        require ( ABSPATH . 'wp-admin/includes/taxonomy.php' );
        $cid = 0;
        foreach ($path as $cat_name) {

            if (!get_cat_ID(sanitize_title($cat_name))) {
                $cid = wp_create_category($cat_name, $cid);
            } else {
                $cid = get_cat_ID(sanitize_title($cat_name));
            }
        }
        return array($cid);
    }


    private function detect_delimiter($line, $chars = null)
    {

        if (!$chars)
            $chars = array(',','|','\t',';','.',':','->','/','\\','::','-','>','<','&gt','-->');


        $res = array();

        foreach ($chars as $char) {
            $res[$char] = count(explode($char, $line));
        }

        arsort($res);

        $guess = key($res);
        return $guess;
    }


    private function attachImageToPost($post_id, $image_url)
    {

        $upload_dir = wp_upload_dir();
        try {
            $image_data = @file_get_contents($image_url);

            if (!$image_data) {
                if (!trim(get_option('no_image_placeholder'))) {
                    return false;
                }
                $image_data = @file_get_contents(get_option('no_image_placeholder'));
            }

            $filename = basename($image_url);
            if (wp_mkdir_p($upload_dir['path']))
                $file = $upload_dir['path'] . '/' . $filename;
            else
                $file = $upload_dir['basedir'] . '/' . $filename;
            file_put_contents($file, $image_data);

            $wp_filetype = wp_check_filetype($filename, null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attach_id = wp_insert_attachment($attachment, $file, $post_id);

            require ( ABSPATH . 'wp-admin/includes/image.php' );

            $attach_data = wp_generate_attachment_metadata($attach_id, $file);
            wp_update_attachment_metadata($attach_id, $attach_data);

            set_post_thumbnail($post_id, $attach_id);
            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    public function removeprogram($id)
    {
        $postid = $this->affiliator_get_option('program_' . $id);
        wp_delete_post($postid, true);
        delete_metadata('post', $postid, 'logo');
        delete_metadata('post', $postid, 'datafeed');
        delete_metadata('post', $postid, 'last_checked');

        delete_option( 'program_' . $id );

        return "Done";
    }

    public function savesettings()
    {
        $all = $_POST;
        if (isset($all['affiliator_texts'])) {
            $general = $all['affiliator_texts'];
        } elseif (isset($all['affiliator_auto_post'])) {
            $general = $all['affiliator_auto_post'];
        } else {
            $general = $all['general_settings'];
        }

        foreach ($general as $option => $value) {
            update_option( $option, $value );
        }

    }

    public function api($what, $param = null)
    {
        $check = explode("/",$what);

        if($check[1]){
            $param = $check[1];
            $what = $check[0];
        }
        if (method_exists($this, $what . '_action')) {
            $do = $what . '_action';
            return $this->$do($param);
        }
        return $what;
    }

    private function marketing_action(){
        $file = file_get_contents('http://affiliate.gurus.gr/affiliator_marketing.php');
        echo $file;
    }

    private function demo_action(){
        $file = file_get_contents('http://affiliate.gurus.gr/demo.php');
        echo $file;
    }

    public function options_action()
    {

        $sections = array(
            array(
                'id' => 'general_settings',
                'title' => __('Γενικές Ρυθμίσεις', 'general_settings')
            ),
            array(
                'id' => 'affiliator_texts',
                'title' => __('Ρυθμίσεις Λεκτικών & Εμφάνισης', 'affiliator')
            ),
            array(
                'id' => 'affiliator_auto_post',
                'title' => __('Αυτόματη εισαγωγή', 'wpuf')
            )
        );

        $fields = array(
            'general_settings' => array(
                array(
                    'name' => 'products_post_type',
                    'label' => __('Δημοσίευση σε νέο τύπο post με όνομα "προϊόντα"', 'affiliator'),
                    'desc' => __('Αν το επιλέξετε, θα γίνετε δημοσίευση σε τύπο post με όνομα προϊόντα και θα μπορείτε να επιλέξετε τρόπο εμφάνισης κτλ. Αν δεν το επιλέξετε, τότε θα γίνετε δημοσίευση σαν απλά posts και θα πρέπει να προσαρμόσετε την εμφάνιση του μόνοι σας. ', 'affiliator'),
                    'type' => 'checkbox',
                    'default' => $this->affiliator_get_option('products_post_type', 'on')
                ),
                array(
                    'name' => 'products_post_type_name',
                    'label' => __('Το slug του τύπου "Προϊόντα"', 'affiliator'),
                    'desc' => __('Εδώ μπορείτε να αλλάξετε το slug του τύπου "Προϊόντα". Μπορείτε πχ να το κάνετε "shop" ', 'affiliator'),
                    'type' => 'text',
                    'default' => $this->affiliator_get_option('products_post_type_name', 'products')
                ),
                array(
                    'name' => 'products_single_template',
                    'label' => __('Σχέδιο προβολής προϊόντος', 'affiliator'),
                    'desc' => __('Επιλέξτε τον τρόπο εμφάνισης ενός προϊόντος. ', 'affiliator'),
                    'type' => 'select',
                    'options' => array(
                        'default' => 'Προεπιλεγμένος'
                    ),
                    'default' => $this->affiliator_get_option('products_single_template', 'default')
                ),
                array(
                    'name' => 'products_archive_template',
                    'label' => __('Σχέδιο προβολής αρχείου προϊόντων', 'affiliator'),
                    'desc' => __('Επιλέξτε τον τρόπο εμφάνισης πολλών προϊόντων. ', 'affiliator'),
                    'type' => 'select',
                    'options' => array(
                        'list' => 'Λίστα',
                        'grid' => 'Πλέγμα',
                        'compact' => 'Μικρή λίστα'
                    ),
                    'default' => $this->affiliator_get_option('products_archive_template', 'list')
                ), array(
                    'name' => 'affiliator_post_status',
                    'label' => __('Κατάσταση άρθρου', 'affiliator'),
                    'desc' => __('Επιλέξτε την κατάσταση άρθρου για κάθε φορά που εισάγετε ένα προϊόν. ', 'affiliator'),
                    'type' => 'select',
                    'options' => array(
                        'publish' => 'Δημοσιευμένο',
                        'draft' => 'Πρόχειρο',
                    ),
                    'default' => $this->affiliator_get_option('affiliator_post_status', 'publish')
                ),

                array(
                    'name' => 'lw_cd',
                    'label' => __('Linkwise CD', 'affiliator'),
                    'desc' => __('Το CD σας στην Linkwise', 'affiliator'),
                    'type' => 'text',
                    'default' => $this->affiliator_get_option('lw_cd', 'CD421')
                ),
                array(
                    'name' => 'fv_cd',
                    'label' => __('ForestView AID', 'affiliator'),
                    'desc' => __('Το AID σας στην ForestView', 'affiliator'),
                    'type' => 'text',
                    'default' => $this->affiliator_get_option('fv_cd', '1296')
                ), array(
                    'name' => 'affiliator_permalink_struct',
                    'label' => __('Δομή permalink', 'affiliator'),
                    'desc' => __('Η δομή που θέλετε να έχουν τα links', 'affiliator'),
                    'type' => 'text',
                    'default' => $this->affiliator_get_option('affiliator_permalink_struct', '/%category%/%year%/%monthnum%/%postname%/')
                ), array(
                    'name' => 'affiliator_feed_paging',
                    'label' => __('Αντικείμενα ανάλυσης feed ανά σελίδα', 'affiliator'),
                    'desc' => __('Πόσα αντικείμενα ενός feed θέλετε να βλέπετε ανά σελίδα; Προσοχή αυτό αφορά μόνο την διαχείριση', 'affiliator'),
                    'type' => 'text',
                    'default' => $this->affiliator_get_option('affiliator_feed_paging', 50)
                ),
            ),
            'affiliator_texts' => array(
                array(
                    'name' => 'buy_now_button_text',
                    'label' => __('Κουμπί Αγοράς', 'affiliator'),
                    'desc' => __('Το λεκτικό του κουμπιού που μεταφέρει τον χρήστη στο κατάστημα', 'affiliator'),
                    'type' => 'text',
                    'default' => $this->affiliator_get_option('buy_now_button_text', 'Τσέκαρε την τιμή του')
                ),
                array(
                    'name' => 'no_image_placeholder',
                    'label' => __('Εικόνα fallback', 'affiliator'),
                    'desc' => __('Αυτή η εικόνα θα προστίθεται σε κάθε προϊόν που δεν έχει εικόνα. Αν το αφήσετε κενό, τότε όσα προϊόντα δεν έχουν εικόνα δεν θα εισάγονται στο site.', 'affiliator'),
                    'type' => 'file',
                    'default' => $this->affiliator_get_option('no_image_placeholder', '')
                ),
            ),
            'affiliator_auto_post' => array(
                array(
                    'name' => 'auto_post_internal',
                    'label' => __('Αυτόματη εισαγωγή προϊόντων στο WordPress', 'affiliator'),
                    'desc' => __('Αν επιλέξετε αυτό το πεδίο, θα γίνεται αυτόματα εισαγωγή βάση μιας εσωτερικής συνθήκης.<br/> Αν το αφήσετε κενό, θα πρέπει να ορίσετε 1 cron job τις ώρες που θέλετε να γίνεται εισαγωγή. <br/>Η εντολή που πρέπει να δώσετε είναι:<br/> <code>' . home_url() . '/index.php?affiliator_check_post=' . substr(NONCE_KEY, 0, 5) . '&check=10</code><br/>Η παράμετρος check=10 σημαίνει ότι θα ελέγχονται μέχρι και 10 προγράμματα κάθε φορά. Αν θέλετε μπορείτε να το αλλάξετε σε κάτι μεγαλύτερο αλλά θα πρέπει να προσέξετε να μην δημιουργηθεί μεγάλος φόρτος εργασίας στο σέρβερ σας', 'affiliator'),
                    'type' => 'checkbox',
                    'default' => $this->affiliator_get_option('auto_post_internal', 'on')
                ), array(
                    'name' => 'auto_assign_catid',
                    'label' => __('Το ID της κατηγορίας που θέλετε να γίνονται assign τα προϊόντα', 'affiliator'),
                    'desc' => __('Κατά την αυτόματη εισαγωγή, τα προϊόντα θα εισάγονται σ\' αυτή την κατηγορία. Αν θέλετε να χρησιμοποιηθεί η κατηγοριοποίηση του καταστήματος τότε συμπληρώστε "0" (μηδέν)', 'affiliator'),
                    'type' => 'text',
                    'default' => $this->affiliator_get_option('auto_assign_catid', '0')
                ), array(
                    'name' => 'affiliator_auto_post_limit',
                    'label' => __('Όριο εισαγωγών κάθε φορά', 'affiliator'),
                    'desc' => __('Επιλέξτε ένα όριο εισαγωγών κάθε φορά. Αν δεν θέλετε να υπάρχει όριο (δεν το συστήνουμε) συμπληρώστε "0" (μηδέν)', 'affiliator'),
                    'type' => 'text',
                    'default' => $this->affiliator_get_option('affiliator_auto_post_limit', '50')
                ),
            ),
        );

        $settings_api = new affiliator_Settings_API();

        $settings_api->admin_enqueue_scripts();

        //set sections and fields
        $settings_api->set_sections($sections);
        $settings_api->set_fields($fields);

        //initialize them
        $settings_api->admin_init();
        $this->settings = $settings_api;
        echo '<div class="wrap">';
        settings_errors();

        $settings_api->show_navigation();
        $settings_api->show_forms();

        echo '</div>';
    }

    public function checkpost_action($num)
    {

        $args = array(
            'post_type' => 'merchants',
            'meta_key' => 'last_checked',
            'posts_per_page' => $num,
            'orderby' => 'meta_value',
            'order' => 'ASC',

        );

        $loop = new WP_Query($args);


        while ($loop->have_posts()) {
            $loop->the_post();
            $last_check = get_post_meta(get_the_ID(), 'last_checked', 1);
            $feed = get_post_meta(get_the_ID(), 'datafeed', 1);

            if ((time() - $last_check) > 3600) {
                update_metadata('post', get_the_ID(), 'last_checked', time());
                $this->postFromMerchant($feed, get_the_ID());
            }

        }

    }


    private function networks_action()
    {
        ?>
        <div class="wrap">
            <ul>
                <li style="float: left;display: inline-block;margin: 10px"><a
                        style="display: inline-block;line-height:14;text-align: center;width: 300px;height: 300px;background: #fff;border: 1px solid #ccc"
                        href="http://mikk.ro/EuH" target="_blank"><img
                            src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAP4AAABJCAYAAAAdWZucAAAr40lEQVR42u2dCVxVx/XHB/doNs2eZk+3NEnTxDZp0yYxSdOmaZa2abam7T9LY9LUmNXEuiuCwGNfFBVBRRFlE1EUVJR930RZZVXAXXYE0fM/c9+5MFzmLWwG08vn8/sA7829d9599ztz5syZMwwAmC5duv63pN8EXbp08HXp0qWDr0uXLh18Xbp06eDr0qVLB1+XLl06+Lp06dLB16VLlw6+Ll26dPB16dI1IsCfO3+RoIVSzZm3gC1cbMu2R+9ki23tlP9NlTWj61E/QP0QdYO8zCITWsjsHQzM4OLO5i1YNJBrs7nz+mrRnLnsnrV5jG1tYSz8rFYxKBB0moWdvRrFLCr87DTURlQo6g0z5Yzq/rtBkPa9IdE3ms8EVNf+nUetf0Qzuzm4kjk5uzAXZwNzdnYeIhlMyMrjDQaZbPC9j1ARKD/Uz0yUG8LPMZwy3pORCv59qCWoFNQpVAfpNCoZ9TXqxhEK/nYNIPWoq01C0CMOUofm2A8tQnRpwP9SAv6T3znw5bJDgaCzqJ9cHpBfPuBfh/JGtaHAgo6iPv7WwA9A8CNaZL1xlAaQOin4lhsMriLU2EsKvtzK+F8F/0ZUkwZ8LoMO/tCB/xCqUAUby8I3/50LX8+eA7PnzOv1N39PaABWYh1sRlCPP1DwEyVwVaHGm4VT7/GHE/w7UZ0S8Ffo4A8N+Ny0rxOBX7RkKXgv94UNQcEQGr5V0foNm8DNw0sBnpcR4P/EVB0uI/BnSuBappv63yr4o1GxEvCn6eAPHvwxNG5XevP5CxfDusCNsD8hCbKycuBAbi4cJPG/MzOzYWfMbqUBUOCft5AfU7Ngke1VKCYK3QQjA3yZc68XrIpGY9klqKOo4yhPaW+vgq6DPzzg93XYfY8ce6dQZaj/u3yhH1ngv6z24HbLHCFm917Iy82DguwsiE/JgIA9aWC/PQXsUPzvhNQMOJSbozQAAesCYc7cBYDXADz/c/waotBqYMscR2qP3yCRAgYvO8U0QA06+Jeix+/rqb8edcXlDf3IAn/jf+fOB1u7ZRC3P0Hp2XclpcO7wUlwvft+YI77YIzzPmAG1LI4uMtrP3wZngIZ2blQfOgQ+K70wx5/CT9+lu3SZUzUElt7BN8ZwXcboaa+FHxmEXod/Etj6l82U3SXH/hjUUXcxI+IjILCvDzw2pUKN3PgbffC1FXx4In/Ryemww7U9NBUbAASgTmnwNT12ZBRdBgyUlNh8VJ7WGrv6GZn78hE4WvMwclleMAfeq++ZQ/7SAI/DME3F2Pw3ZnO08EfBvCnIPSnHA0ukJeZCT4xqTDOEAfMLg7e2ZwM+TnZUJSfBwcLCqC0qAjqysvgt6EHgfnkA/PKh2nhxVBZXg6eXj4cfF8+ntfK0eCqg2/0FVyBGjUo8MP+J+fxh0JjaJgwdpjOP4rOP2GEgb+LLVlqr7zGARR0HY7tz6xfHwjJ2Xlwqw/25g4JMG1DBhwuKoSy0jIor6yCI0drof7Yceg4ewo+TzoKbHUpMH+UXwlEHDoKm9atQ/AdPBzQrNfKydltBJv6vXQHarqgDxGSB02APx7/n476FMuhGu7SgD8a33sJf69EpaFKURWoAqrfV6g7h7zHlx/7D2MdUWGKvmIRTc/cZAR/NIL/OT6QiwXNR03u54P/K9SCAYzBeVTeTM3139aUeQL1IWo66QMr68ePc0DtRRWiylHFqP0od9SrqJsGAfsjdK92oA7S+bnzMRcViVqI+g01CpcefO5dR4ed0vOi911pCASNR5AO742OBsM+7Mld08HGKxuCssrgVO0RBfazDY3Q3t4O5zvPA1zsgufiG4BtrEdhA+BfCbPTj0FUyGbe4y/g19CKO/a+dfCtC9l9RTKOnmUCJu78OyeUCxDgexSVIjmXVmcI6OEE/yvpdSNa7r8/MI95ODnwh3GrZLrsb/2EYD8d9+d+HvczybXnacqslZR50Mw5H0DtlBwj00lUMOqP1AhZU+epBHaXldfIQL1+ycHnU2rotGMrV/tjI7BE6flFYcOwJXX/PnhzRzGa73kw3r8QsivroPnsGWhta4MLFy6A+rPnTBewXU3AtiH84WeAbTgG/0g+BdtCtwCa9bN4766Vs6vHyAS/73F/lEDyqYljJqNOCuWaUNdimd/Q39APOQyTc+9l1AXNMU1Kw7S1lb28ehfzcbLnD+MLkod1Sz/gfVAIstnVT/BtNddtQ92lKeOrKXMedb+J8/2apv2gn0qxEvzPqI4wAK1HXXlJwd8Xn9ht7uOUXS8tWmL3fPzePTAtqgLYymKw8T8Mm0tP4SU6Qfwpa7kAr2a3wo+TWmD8vmZgOxuBhZ6F11IaIGJzMDg4Odsae/feGhHgW2fqy8GXl9WCz7UCdVhyjrOoZgvwvzh48Hv5GR4gi0IsfxEbptdYGH+/kX3itZ55GpQefxKqRvOQcniusxJeF+G4TupxrR13H9JcN0ZSzlrwuflfaQa8RlSDife+tKK+BjPnvkjnbqC/TZXDYCTDxEsG/p64/SwjK4f5+69ldgg/Tr11ayEOBUI3bdrybHQNmu7lwNbXwB3bTkBIbSeUtV6AtMYuWF7dAf/KaYOZuW3wVX4b/CWzFa5LQPi3N8K7Oa2wLWQLoFkfzCHXysXNU+n5LwPn3mDBF1WC+hrr8wjqFvz7dhS3BlxQ7ZLyWagxgwK/5x5ch/8fkgwNvjHenyZ29ZajzA4bZVdnJ/Wh9pE8pNaY7VejajXHuVsJ/uMSSN4bBPgzTcC2DvUU6jYKBvoFahaqQChzv4W6zjVx7njU+6ifom4lPYT6FyrRxDFr+DkvCfi79+5jOdnZbP3uJBayN5mV5mWxg9mZigqyMljdobxrZ6XUVrB1CP/mY9iToxkf2QBjdzfBxPhmmJbSokBvf6gdXIrOge3Bdvh7VhtMiGuGT0s7YW/UNkC4SxHysRz03vIaHvAH2+P3HeNbBr+nrDnw7VBXmRlW/MEE/I8PeIzfU45HH+6QnHtVdxlsLH+yIR97+2Wix/1JE6apJXj/ITnuuJXONzfNcQ0mnG3Wgr9DUpelZq7PPfD/Jf/EaDPlnpU0UG3kaLTkuPzMhC/g+UsGfj6C7xadwn4XVsB2Hm1jW6tbUM3K7501zWx+9qnn2SaEPgyhj8Ix/C405fc2wd1o2r+NPfzX2NM7FZ6D5aXnwAdlh/A/k9oKX1VdhNzUFA7+RQT98b7ge45M8Ie+x+9ACN83vZCn1//ekmvNM9NYmAa/dzl3yXn3YrnxPVN5rezF1THq+F7VOPJ4iw9nHfXo5h7uBBO92ocWjuPe/wrNMSEmyloDvg1508Vy52iRj6UG6EoLjUOB5rx8OPNKP/wYMyT3Z/8lBX9lbCpj9nsYW13CcHzO2OZTPQpBhZ1x4z0924HQY29vs78ZHk4xgv9lvrHH9yo5B75l58ADf/8Hzf8vy7rgSHU1uLp7AZr2Ac6ufFzfW/8j4O+UQi53JD6ujLl7Hx82SOfedEmZYtSNvf0AjWxGz/he1DLJA/qiBe+2Kc92toVe9HeSY/46SPBzJID+aJDz83+X1NNuAOfZq7VuLjH4aYw5xjHmtI+xwErGIhGciAZRo9n2BgOLaezivf04HMc/huC/ieB/gqb+/IJ27PXbFejdi8/BnAPt8GZuO9S1dkAgxgLgOL8NI/V+wqP1VPEpPVs7h4FBPxK8+taDv8X8Cj6Lx6cPyNQ3vv+0ZnpRzTykiUNoZFdtqWN26HtxNTjJQL4gG4+akI8FL7a5FXRrNGWPoa4ZBPhc4ZI6zB0k+HEDHMZo9VfNeU5fevA59E4IvwEVWIXwN/eGfzsqtukXLK7Z84qE5txHU1pO/Sm9teO97Lbzn+a1tSHspxYdbO9YjKb+7Px2+HlyCwTixyjLz+Vz+bjQxykGg3mUcF2uxbb2A4f+2+7xw6yezgNK12VtQM4oCuYRjy80mfRD3uM/RXW6l7IMie+1Kr4ESeTh2LDTbJaHH/Po2+OPkvSaR0yYwtzjf0IoxxuMo5pjg01AwAGv15T1NwONteD/UwJ+Sz/NclF3SKbuAgZ4rps15/qWwDcQ/E4S+I3gM7avmY1PaLb5WXLLjX9Ia/3+W5mtP/5XdtvdM/Pabvwsr+2+97JbnZ9Ka73IHXz3J7VBU3MrrPFboyzYwXiB2TxYiGtQ0I80U9+8cy+0nyG02iCfUtPLgKU9/mNUPlNSb0eT18U5/N+v2YtjfDvZAzpHAs/vJeU+0JQpQT1DUIrQycbYf7HyGv0Fn/sN8iXnvkDWyX39hPUlybk8aVbg0X7qWc1U4rcIfjf8+3rD3xt8huAzBJ8h+AzBZ9jrM5zSY3/JaGM2sU3TlWCejceUCL7SvBxYtHgpX9d/Aa//z0FDf3lN54X2c9FMwiDAv0gRgn4mZhbylZkF2bkimtgtm8oVj74kTv8+ScabFZLxdJqmzBITY9mFEqA2a8rwufeJQwA+18McKhNDj1bUBppGtAb8rwYYqGONvmXwZT2/BfA/R/A/R/DviDzO2MZaxjYc2cjWlMLo5bngsT8f4ndFAzrycH3/vC6sw4wRC74103nDZ+oPFvxO1G4LQUFOppYW26Bmeq2jKb0+D7x2/rlKE4P/K830FgfqbhNj2QoN1DyH3pl+zvv3B3yuxyhm3hx40WShmLuu6zCCf+bbB78X/Ojw29liFvxZBR1samQ1Yz4Inm8+1wPMO6uDuSTCeFyz77grExL37AaDo5Oao28d1uOuAab2/q6O8S2D3/u6X1oR+tskaRx+Ycrcn+afoJ3SU/WJ5EEVIVmleS9IM/1VqnlfiFNXsudoz/2bIQZfbWC8Ue0WAAwwE6HoN4zgN4wM8HvBX8bYbuz5E9rY+KRW9nBKK3shvU0Bf8aBDvbH2DrGnOMZc0lgzDXRKOf4dCVRh5NRbwWnQkRcCmwJCgIvZwM4LbU97bBkse/ShQtenT933mOz/zvnR7gq8FZMADLpOzSdF9qvzLmD6/G1DdRS/P1rVJfmvTR8b2zfazex6zZXM0cXV4zeM8gSXLZqHlQveu8GjSl9keLjxeO/1hwbJ7wXrXmviGIIhhp8cR2BH/kbTEGYL1gslsCfRRbFYDV15ICvwu+4F3vzDMa2VLJxsSfZgwmN7PnUFvY29vavJZ1hE90TjWV6H+ulgK/Cj5l6rnKPhyfXpcIr6xPgtYA9MH19LMwLigbv4K2wYeOmroCVvg32dvZVOCRIQqtgBfkE7tJ7/H6D7yWs//eRvP+FqbH+hz5BzMtJau5rV7cdptc/tGJxy82U+14t00WgXi8B0Jo58cGAr+qHFCnYbCbuQDt74S4p9+pQreEfWeCr4mA7oFzi2VjPFDZpeTq7FjVegT5Odsw33eCLDYADJvVwwN+OmNHHKR5fT4ArPRLhZ34p8MXWNNgZnwy7MNwXg3yUnH9Y32asdwTqmcuyxx+sc0+MsLMO/JWaxCB8uXCNpkwj6h6Zuf/LtanMW27uvyN56J+g2HTxtb9bCau9ZLrtAjnjLgX4qvhGHHtMwP+1puwsSZnZlw348xYsVsAvzM1ma/dYCb6i/b1/O9JQQF6+L/imxBsER6NV8D3PeHCOyYQDmNgzeEuo4hTk+f8oZfdaZYMPE+DfPZgAnktt6g9Pj78eZSMp+4akbLjM3L8GF+ssw5WTkmCemzS9Nle6Zi662ky468OaqL5aSlYhni+rb5KKYQefazxquwTqAloxqJZ7WVImfMSDz6GfNXvOtfa2S+9MSYifGpuS+eqnYclvI3jvIYBvoH5oEXzn/TSejxcaAqm8rQZfFG8AHONgdmSKkuJr2/Zogr97w448BP32vuDPMXr1sdeyAvzaPlNbYd8B8Pk8vum9/SIl5V/rXa5BmRX5x4owU06+MAsOqkUWHu5YC8db23sONfiMVuppG7Z2TS6A2yW+jlM0ZBmR4N+JZvOsRYtt923ZEFi3Mmpv6/NrE+EqV2PGXAG8dtQ21K+HAPzMAYFPFsAY/M1Tdx/EZJ9BwVs0m3UsSsfGYKKYMsx27hz2nFekteDzdenXW7FN1fAF8AyPc+9JM9e4l0x8sXx1n7ThmHvv9qAyxcEnmdN/3Qy0LVYsgHnFzPEdNO7+tsAXswaJekRTJkZSZs5IA/8qlD1OoZ3xwM0usJeHr6MyYKJrvGJWKya2HL4u1PxBgP9z1Pl+mfra17B+T/onQG5WJqSkZfCkHsrmHvPmL+JRgJi3f+kCnqNfle2ixeyzZR5sbCguLIpo1EKyWRLs8myvnl5u8l/qyL2hBb+vvpAcs7yvk6+FvbM8RDbWn0yx6TJwA614uMeR1152/P5+QNIf8Ef147yJkmQaD2nK/FlS9yZKGTYiwH8AlcN7yhUrV0N+VhZMD0s1As8dbMtIjmahXDZA8LdbZdJT42MjOv6cehqDiWiRbEtIg7ycXAjcuAnBn4/Q2ypbeWF+/hNLli67bgnP1U85+3EIw+4KLJQF8ThIHvg4SoA5vOAPZeSe5Xn8Jy1YFmNp4Y94zAV8/ale5fD+3RlUwtxwnC/p9deZyDbzKysf8M9NgP/xMIHvQt74KRbO+T1JNh5ZPoDRJhJqVFjpmBQX6cwYavAfRujrOfR8WyvuKHOMTusG/scr4uE/ocnwUUgy3O4Vr4ypzUD6TjfQzlaB/4lF6LEet3ruh+lbksE3Ng2C4tJgBabxfiMoCcYa9vXUBxsHr5g0KDyQr+zmswBj/jElOCDk6uKf11FMlavdUvbcyt1o7rdZY7KDMu7lq9W4Q0wO5uXm3HvS4pAiXInn75SE807odQya/O8t3yLr9f8geegT+5GY8gZJDrwWGmMPNfjXCBZKJYXc3mZifL9L8rn2mokFaDKRymsONSKy48ZSePB6wYdw9xCAr5j3fI/6cr7RJTeJ9+2Lh6SMbLjNO17Jjf/S+kRIQ9P5IJrQh7IzIRZ3yLnXJ95cz9+EeshK8N+nYYJpkx6h/3NgIsQlpwNm+oGcjAzISs+A3MwMKMzJAo+YdJjgmgA86o8ZksA+Ngcqi4sgA7fnsndwUnb3Qch5Ik9u/nuKy31dHBzZTI+1Svhpr3z24WcnEkQy+DtQeagXRvx0nqUe35osu8bX3CSfa77W3L87qBh7fUdtrz+JvPfWTOGZkrfm+Kh+Hm8t+DKfxBkKGuKWgDN55U+asEJeNlOHv5jJPcCvsRu1nHIacIsjlFJ6y5KYjhkK8Neq+96tXhMAh9A55rQjBZh9HPxwebwyXs5E0PhvLky1BQ78fWmvr74WV4aaypw5/NQAuKjQx9mgHsG/10lhx8Qiilx44xIP/9ySquyzl5udo8CciZtwZufkQW7+AcgvOAhHSovg4x24OYd7JjDPLHCKL4T66ko4VFgMmKhTgR4h5+v8eYafKDF7r8HZFb8QV3ZHUKnR3O/90L9kIbx1uqSX/K6N8dU6X4u/K/ss2w07+5PeJn8z+5fPJlmv723lFJ41WXiB0nUNNfh8bL9vECG0K6w01xsHGarbqFohgwGf72fPF8Ioc9+YRVeB7G+bkoAtjYOF21Kwl83shp4rAxuB7TiOnuQqONmcEVKXhB65JvIeuB1/70MForxRXvjeJlQuqqtPeTe8pjs2KB44xPDKwL8z4PFNuVBeWqpsylFcUgalZYfhcHkFVFZVQ3XNEUXHa49CYkkNjFtTBMy3EAJyq6Hp5HEor6jEYYs38B1+eAOAmX3Axc0jwdkNE3gK8nZxZu/6hhP4jdre712JZ1vtLd+T9JIvScp9YWIGYLLk3JH99Oqnao6vMgP+15K6TZOft1F2rb9K7gNveMaJvf69GwuZe9+x/pP9mMIzJTUf3ukBTIfJQmcfkDgSfQeQ+poHETn1wyn4swE2MPw628RZg8GA76zuZ88dYHxba+7Uexan7jjU4fHpSm+fnJrerYyMTIhPz4brl6PjzyMdt8LKBoar6tiKPAQPe96VB1AFwFbh5hqrDuGOOVyFxr9X4mu++J7vAWN5ftzynN7HrypQyo7xK4KdRUegESE+dvwEnDx1Gs6cbYDm5mZlc45z585BO6oTdQrX8d8agRt0bDgKyUcboKu9TWkU3D19EHZPJaUXbwRQ8Sgmyh3l4+bKXlm1Ex/eZuPcdFgv7/195Mmu1jz0H0ngeoZ6RlHvdsOEPaJQ9hpUjqbs8r5mdiMFGTXIYN6iOX5fLxB76wNJ3R7tW45n2Kmle9HH5F+rOf4I5d7vScuFYbwfYK/v2TuMd5yQfvvOAYL/kuV8/Sa32rKjMbuqMjNTgffRbjxZFuLz62hm4vEBfBYb6v2jJbEAopopFJib/z8fQq/+wnS1t8eMN5CYnKqA//y6RGV3Wz6eT0fw0zOyIDs3HwoOFUJNRTmkltbAhHVlwAJ4Ku0qYIHVKMyuu+EIsKBaYJsQws3HSSewZzkF10WdhltQV4YdVwBlAXjcWszDv/Zwj9bx81Xie5XwWHQttLe2QDtuyNHR0QFdXV1w8eJFkP00dQFMjmmCKZinv7HTWKYKrQIOvqf3ClFRKKaVh6eXklTi5dU7jT1/RHPvaTvjg80DeB5BvUpm/n0SuEaTf0BQwxienPKKkGPs1k2HCX4+fdhgQ3vhieXH9+S158C3sytD6pXEluNDT8iiDCdojp8g3YPPqDH4WTR1E/bhQ2BZZDv7+bp0tsjNG730pcr1JVbQxO7zGH9f2es6kR3sLd9IJaAHB8WieKKMNwaxhx53dH2Evx80vb+ewdy04ESNrOmheSP1WwoV/pjWGbxOi2QmD1EE3i2opzXXeJOspNuHaTpvYb0KPm6UAQlJKcqe9tNDUpSY+Jj0PCgtLlbM5rr6Y9CAW2FhFwuryluBhZwGtpWn0D6rpNFWkmlEUZJNvlPOLp5dtxUWVpyHtIYLUNt2Ho61dkJlSxfsPnkBZhZ0wPXb8NiN2BAEY2be4HoS/r3xBHyR14xVlIOu/SnH3P2TcIOOlzGZp/pzEBsp3PkH/PzXwiq/APBdtQbl54tiolauXsO8vJcr2ynzxSav+25jt2OSCaXHiyALwNyW1xwYJQ5AUm6rcfjwREAyW+jmo6Sqeiog0TiLEN5o4pyNxqAivPZDuEXV15jiytfRls3y9GPfDypS4DRjAfRYFrwB4+fhZdU4BZnzLsJoUdy0uUrZGQd3fFDG6PaYM/8Fvz1sYsiJnoZQ2xiKG34q12zChiOTLXb1JnO/F/hDsIGmNa/R1tiG7/4Ou4OZzjuDUqLbuEefm/qluF99YDKa25454JpWAV0Np6C1tWcbrHZk8VnMh38TgnZjfAvcgMk0b0hogZvx952JLfAA5s/7ZUorOFR0Ql1zOzQdr4eqslIoyM+H7MwsyExLh0L0I5yoqYLSs+3w8cFObEAajOYz/82Fu+vYl3WAtT/7TpyHV9NaIO7k+e7XUvA6QcEhsGlzCGwI2gzrAoNg7foNn6GYqHWBG5nP8hXMQLHmHH4ed/6W71Z2U3A1GxN6muBp6gMLf+BvQGAmYa+sPPz8ta0EHIJ3B/bw//IJVno/d/R2880nPJ1U+FupXKNwbJty3KPrMth/vDco+9LxxsJA9XLAJbAv+cWyBzCn/ajwMz0WAK+bej4E/wZcLnsvNhKvr9zGnvaPZxPCTtK5xWsanXHjw06xP6Glw3e9NfbSBuV6/B4sd7Rj/3VfyW4JruiJdQg3xuirn5H/Ho05+G7YUqNcjzcabnQvBw++s2XIdfD7Dz7CXm2MarPF+PbFEBO7B2pqaqD2yBF4MLwSbg2pg8rmHpja0aTeiDvi8Gy5szA7LubNU/6ekWvcIYf/Pxtf33/8PDSfPgllaC0UHCxED3vRORwmHM3Jy69AB2E97spzPmr7ToiLjYW6ysMQVt8JN8a1Gi2G6EbFepiBmXit+enA9iigvANWYLruji56raMTcK8/xVm5ddsOCI2IhC2h4XwRzxMoJmpzSBj2+D7d4BsfWIMyRjUgDHPwwf/zqmg2BRejKPAgbFch6D8NzGUzvAOV8ouwN//K01/pMXkv/RiC+0+MX+cPonbJKg9t5TB/gA3Co9g7Xo0Za8cifA+vz8YGIUmZC+fX5sdpYeHHcjj5e596rTVaAAjvFISO97TPIOT/9tmo1Js3NBzC5TiE+cpzDXt8bZry/mP4m9efRZ5j14bUsc89A7DMUtma+u6GkJv+t+DOuIrpj5+fD1kexSHBjzccZL/G1Xlf4mfn1+SfyxTgLlKY+wu/qWNNgP8dh3/A4GNwSyJmsMW5bgd07tnxHhFOnDwFHU0NsLe2RdlL/Qbs2V0rOyC6rhP8ETBvTIu9Fn/7He5QNsbwLDamyea747iXdEDu6U44UXcUiopK0AtfHoVe+L+VHa74AXrlr8FG4IqsnLzJ6CS8D/fi+3v0rtiw8Iht7XkpSZB+rBEeTD9nBB+HCT9Ey6HTCks/41QX+GNdSpu6ul+rx2EJnh/2YkzCLmzMeAMQGbXjWETk9mtQTBU2CiwsIpK5uuHUnuQh4Q0AB4iP/5e4erKnAxKURsAW/+ZweioPukHp4fhDz0Hjqai8usFVzjmNgj3SxWAQfjyHmEP1jcdqpTy/jqd8bXsfqdf+BBsfXh9+Lh/l+sbXRdDURkAtw4cdL/vtVjLleltxPW6lzHNfgY1dPnsDe3VuEfDzeOB5eb09+s7d9wPkPnvLvTMIq8BGSUxpMNwycPD72yhdhuCjQ8+Bz3MvczR0z3UXFZd0A+SLe999D7e/ej29VdnyioMfUd0JkTWdEIa/g7FBCKwwNgLrK89DydkOqDtSg1NvpVkI/LTD5ZUMfzMEnyH4DMFnCD5D8JVNOGP3xDGE/4GwrdvW79u1Cw7W1MNv89H0R0cdpu6CJZWmzf0ubBTycOfdELRAsk71WCV8SMKtDD71mITOSt4A4JJi3gBs2Bmzm3Vr127l+sGbQ5iTk5PFm+ymPOT2itwMTsxKz7Ia1tlEoavXy87LwRzIF69YJgi6q8GpX8fx+nvT8KM/x/DGTRkKDRoMk3AdtZCH38QqOcNNdM5radrLbhjq9m3oHr5VlilH5IDBx0i2qTjPfRGDWZRpLz7XjWNeHNO3doOU19CFPblxy6vgqk7YfqQTdh7F36hwbAD4azH1XVDfbIS+pPTwSgR9EgfeGvB37IxlUTt28h74xZ3boipKcJ7+nZLzwPbghppxTTAfG5b6cz3bbLci8WXYu+/ERmkLXjv79HmlEVB/jh0/rgT38CAfPhuRlJKm+C7Q9H+Gb/WtSr2+lxcm/jEP/mjTY0vZF2IQp2z+SNMyn5vY6dXcQ2Yjr4fJ99X6mPNWj7L8OWV1HGqZhKuY0lhb+pyit/5YTySg0uM/YmL/vMHWbYwV93GUFd+jNfd2NB1XQolKtUOowUXuGben8tjK57n51JeP7yrFE74rdje0tLR0w9SJ3BXjjreJOHaPqz8Pe9Ds34vj8iR0qlW0XITWtnY4ir4BBHyOCnp/wcd19Gxr1I5boyK3bS/HkFvHo+dhcnIrTEb4n0PH3SzcgccBhxRORe3ghsMKDn1J44Vefv+mpmZubShRezyqT4UfrxePDYANiqlKTc9im4KDmYOD1NTlvbMHhWkeNaaRMtwrPBSf0FxwpbAm/A7KDTedlmIGCokjKoVMsH+ludk645y0QV2//SYNCd6lUM2fU4z2YtosopxCRp+lPd4O0jHqlFAkPSSHad53LD08vB4OdP0qCgW9Ufis79OcdS1lhVWDTPbSa5H02bQx7QEEHC+XSVNSvD559DnupbJ8C6qtdI19xiw8yj38AfXufIovg/7PF8CfSRtqTKbPMY+GSwl0LUbvq3n3/CkicJWwbZcnDR2C6H7NF+CcSKm0CujYhSbW9/+I5ttrKZ3YPZRcc7ewiQcfziVRQ/8i3ZMZVK8izfDlYeHeRgjDPy/67Avp3KspwvAcbVLyGE0v7qBnJ37A4CPsXN/38Fp+ZjlC7782UMliExaxTTGR+VhZ9eZ3m9jceYa0nUddvHgBmhsb+Jz5BZzy+xDFBgM+V2RU9OjIyCiXUkyqUdzcCcuPnIcvcb+9Lw60gR1CH1SFfgQ08VvO93YAcOgrKquw8SlXovzQoQh5BxT4uzDM95co1q3sXKUeq1f7MUdHqblbIGxh7C8sLBF3W+FplhbQ3y/R9sZqfrga+gK3CMki36PUU+q51I0n8Es18IfxU2GdeTaleFJTPEcQOOpWUQEUXNJEq8hupgfnDSE89V2qr5qxdo+wsMSF3vuPkC+ONw5v0UNdT5D+jRbI7NX0XFMohLactnOOpfpspYf8JMGigvM+NWR+dL4JFDmn7rTjQYtxDlKj9Qt67590jsV0rV/TfVfDVl+n+8Uh+RNt0NkhbHtVSEEwX9Cut+LW3f703t8ppz9/b5NkC+9yuvdvUV0zBFCBYN9H8fZX01y8mnBjBX1fQMt1r6b/+fPwNgXvxND59gi76K6nzqWRzutODeNuqjO/L98MGHxcfkvyeyFgbWA7n/qKiIyCnTF7FK94cko6HCoqhmPHjkMzWgA8kKazsxM6MFqusbERamvrOGwtGEL7WkVlNRsK8LmiduxikdujZ2ampV3obGow79XH+pxEhyQP4+Uqr8BpQoS/CHv+g4eKIP/AwXl5BwqYKLQGGA4H2MqVK5mTHPxMWgk1RRMuynuxZHoAHqRWuJN61RtofFkoxKK/SMe9o9kI4geaGPYHhbTRzoK5V06NiLoXPBAkam8GBIm6Y+tbAvirhUaskczDK6gHiafz11DvMUn47NPp+Pep8VEbr1uFMtfS/fmC/lcbtKn0/wIKrR0vrDJ7kT4bUNTcPZLltQVkkZTQ9tDq7jYnKefej6jH7BDuaZsQGDSJQJklgC9uw1VHdbiCvquPNcOMDZrn4DWq42d0L/yF6MOJVM9TVB91gc5HVGaGZtefWVRP8d6qGYquos+t3WSUf/+HhP8L6bt8dlCmPg9eMcqfbQgK/n341m1HEETYGxevjI0zcWEMHy/ziL1ijJfngTwcrqrqGvV3Geo3lVU1bKjBj0bnG+pVnPprKDh4SLE+mpqaoA1jClpaWuEshu/WY4Ok1oWH6Kp/H8Z68rj+ouLS1dhwMa0Ki0qUBsAM+Dn0sE2g/x3pS3mObv456uFa6YvwJ3NbmyNeXe31b/o/hRqKazTbH/9e2Cf+FQGWSoKB0TZK4o4zai/1U+pNaqmnjtZsVnmIhiuj6IFvJPAnm1hKai8sBlE/H9/j7vsa8FvpARYbpQeENfSnqXF5gj5HDDV8F2mBzD1CAk71vBnCCrY3BQdpG0GWRyqgezuG3nvHBPhFGp9BGVk7d9A1nhLeS5P0+F9r7kUTOWt/Su8vFDII22jAVy0L1bIx0HBFe2+PkU9C3bDzJs33XyjU5xl6TbEChwD8NSwwKJib2bchjL7xicnNqemZGKabBwcQOu7px6k5EfxOBG016ib8mw0X+DG74xhOyf0Cy5YkYlRhOq7Oy807oPTkCLVi1qsNkbpoB+uCVkg1X8zjwK+J5aSyAH4WfTFqT0iJFQ3fpwevisbJEwmmK4UHOdgM+KGaHl/dLfZ+ocd/VfPFqy3+L+n9pfS/arreJyS7uJYcXuLmjIeoURhDn6eJxsnjqLc6ounxv6DjXxM+H5qohtGC00sF/wP6/zdCI8T//5IazjHUiIbR63cLn1e9X08L1z5En2UOWRTfozq0E1CyDD/naEhiCnxx1dxhGtdfSdf+SNMobDCRKfj93vdC+VzX0XOgLjl+TwP++5oMPJ/Ra5J7qzTKavLO24Tvv4qGatrPrVhOQwj+DiWbLsbs35uRlf0Z9vaRCNkhNJuPoPlchWBnIGjOCNrDCBnjugTgs8Sk1JvT0jM3ZWXnKuN27rwrQQuEr9Tj43oRfPyb1/EFfl109A0U/FT6ErYLcMbSe58J2VJn0pj0LqEXiRDO8zd6baYmKUWCMMZPpy9+hmbXmLHUG1RoelVHjRVyv1DHefTwgvAQlxMMKvhdBKPYu2fR+d6m+PCz9EB/Q/o/CXDUoBnE1XdqOqnZZBGNoc+XQt72tYJlcC/9/VvhvNXCxhuVNKwaTfCeJHN6KpnD48kiO0PfxVSC6ZyQ065KMz1Ya3T+KXXeROPlf5MVcJ58ENr8/nWUnGMuNSjT6b0AYail+hKmUAMAZGU50fGdNEy5hSwG9d7OFpYYq2P82yX7CwZQQ2lLz8mG4QKfIfgsL7+AHSwsGoMAXY3gT+JgI1QK7JcS/ISkVJaWkcWPfQLH5z5oruch+MfLyisaEfzjeP18rIsf6o9YnzG8joMEP5MeqDAyn0OFLCmjqEcrovfyqaebQg/4EuE8z9JrfxFe41/0ATp2Iz6Eagv/KpWdJkzZhBnLdJuMKbSIQ92YIoUAuo1M6aMESRRBzegh2UYAXUEP2CphOmwhmdF11Iipe9vtpjpWkXNNvD9XkoNKHdc+RHVRhwNvkQfcBoNoHiXnYS49uDFkpdwqNAhM8NJ/RX8/RQ3Sk1RPd+qVi6lBvppmWd6jxm0zldtFDZgK9yzh/CH03amfwZkcrbOojmtNLKPdTveimu7rLdQouQgzNRnUk6tDtlCqV77m+39cuLeV5A9Re/EUzYzLM3QPjgqzPLV0Dz4fbvAVgBB8NgLAVxxzCL4Ngj8Fwb8ZwZ+CdRrVuz6DBl8d44+2sD3TOMthpCbnhseZn8c3HUhiJvZ9nLPZkFmDqUAjG7IwZOezGVSAS0/03DjL8+fSEN/RmlRUEyR1GTvImIMx1GPPsvBd28jm6116z+N/JGTjsbH87Dhbcz/HaO4BGyLn3mUHPkPwGYKP161S6jTE4JeQeWZlppgBgc+GAXw2QPCHL7LN6rBZg5nY/mGJsptJw7dVNI4u1vS2/by3vc7bz/RihksbsquDz8F3kC31/MBochrGWfdw6eBfhuA/RjnuQgQ/DRsC8KdSoNBPRiz4unTp+u5Kvwm6dOng69KlSwdfly5dOvi6dOnSwdelS5cOvi5dunTwdenSNWL1/6AhIKgN1FH1AAAAAElFTkSuQmCC">
                        <br/>Create Account</a></li>
                <li style="float: left;display: inline-block;margin: 10px"><a
                        style="display: inline-block;line-height:14;text-align: center;display: inline-block;width: 300px;height: 300px;background: #fff;border: 1px solid #ccc"
                        href="http://forestvieweu.go2cloud.org/SH1Mc" target="_blank"><img
                            src="data:image/png;base64,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">
                        <br/>Create Account</a></li>
            </ul>
        </div>
    <?php
    }

    private function programs_action()
    {

        $this->show_networks_select();

    }

    private function products_action()
    {

        $this->show_programs_select();

    }

    private function show_programs_select()
    {
        global $wp_query;
        ?>
        <select id="program_selector" name="programs">
            <option selected value="">Επιλογή Προγράμματος</option>
            <?php $loop = new $wp_query(array('post_type' => 'merchants', 'posts_per_page' => -1)); ?>
            <?php while ($loop->have_posts()) : $loop->the_post(); ?>
                <option value="<?php the_ID() ?>"><?php the_title() ?></option>
            <?php endwhile;
            wp_reset_query(); ?>
        </select>
        <div id="populate_products"></div>
        <script>
            jQuery(document).ready(function ($) {
                $("#program_selector").change(function () {
                    $("#populate_products").html('<div style="text-align:center;background:#fff;height:600px"><img src="<?php echo plugins_url('/../images/loading.gif',__FILE__);?>"></div>');
                    $.post('?page=affiliator-plugin-menu', {
                        ajaction: 'populate_products',
                        program: $(this).val()
                    }, function (response) {
                        $("#populate_products").html(response);
                    });
                });

                $(document).on('change', '#paginator_products', function () {
                    var q = $("#afiliator_search").val();
                    var cat = $("#afiliator_categories").val();

                    $("#populate_products").html('<div style="text-align:center;background:#fff;height:600px"><img src="<?php echo plugins_url('/../images/loading.gif',__FILE__);?>"></div>');
                    $.post('?page=affiliator-plugin-menu', {
                        ajaction: 'populate_products',
                        program: $("#program_selector").val(),
                        page: $(this).val(),
                        query: q,
                        category: cat
                    }, function (response) {
                        $("#populate_products").html(response);
                    });
                });


                $(document).on('click', '#do_afiliator_search', function () {
                    var q = $("#afiliator_search").val();
                    var cat = $("#afiliator_categories").val();
                    $("#populate_products").html('<div style="text-align:center;background:#fff;height:600px"><img src="<?php echo plugins_url('/../images/loading.gif',__FILE__);?>"></div>');
                    $.post('?page=affiliator-plugin-menu', {
                        ajaction: 'populate_products',
                        program: $("#program_selector").val(),
                        query: q,
                        category: cat
                    }, function (response) {
                        $("#populate_products").html(response);
                    });
                });

                $(document).on('change', '#afiliator_categories', function () {
                    var q = $("#afiliator_search").val();
                    var cat = $("#afiliator_categories").val();
                    $("#populate_products").html('<div style="text-align:center;background:#fff;height:600px"><img src="<?php echo plugins_url('/../images/loading.gif',__FILE__);?>"></div>');
                    $.post('?page=affiliator-plugin-menu', {
                        ajaction: 'populate_products',
                        program: $("#program_selector").val(),
                        query: q,
                        category: cat
                    }, function (response) {
                        $("#populate_products").html(response);
                    });
                });
            });
        </script>
    <?php
    }

    private function decideFormat($feed, $file)
    {
        if (preg_match("#affiliate\.linkwi#", $feed)) {
            return array('xml|' . LW_XML_FORMAT, $file);
        } else {
            $file = $this->transform_to_lw($file, 'fv');
            return array('xml|' . FV_XML_FORMAT, $file);
        }
    }


    private function show_networks_select()
    {
        ?>
        <select id="network_selector" name="networks">
            <option selected value="">Επιλογή Δικτύου</option>
            <option value="linkwise">Linkwise</option>
            <option value="forestview">ForestView</option>
        </select>
        <div id="populate_programs"></div>
        <script>
            jQuery(document).ready(function ($) {
                $("#network_selector").change(function () {
                    $("#populate_programs").html('<div style="text-align:center;background:#fff;height:600px"><img src="<?php echo plugins_url('/../images/loading.gif',__FILE__);?>"></div>');
                    $.post('?page=affiliator-plugin-menu', {
                        ajaction: 'populate_programs',
                        network: $(this).val()
                    }, function (response) {
                        $("#populate_programs").html(response);
                    });
                });
            });
        </script>
    <?php
    }

    public function select_network()
    {
        $network = $_POST['network'];

        switch ($network) {
            case "linkwise":

                $this->loadLinkwisePrograms();
                break;
            case "forestview":
                $this->loadForestViewPrograms();
                break;
            default:
                echo "Παρακαλώ επιλέξτε ένα δίκτυο απο τα παραπάνω";

        }
        exit;
    }

    private function loadForestViewPrograms()
    {
        if(!$programs = wp_cache_get('fv_programs')){
            $programs = file_get_contents('http://affiliate.gurus.gr/fv.php');
            wp_cache_add('fv_programs',$programs,'affiliator_fv',3600);
        }
        $programs = json_decode($programs, 1);

        ?>
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
            <tr>
                <th class="manage-column column-author">#
                </th>
                <th>Λογότυπο
                </th>
                <th class="manage-column column-title sortable desc">Τίτλος
                </th>
                <th>Περιγραφή</th>


                <th>Ενέργειες</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($programs['data']['offers'] as $k => $v):

                ?>
                <tr>
                    <td><?php echo $v['id'] ?></td>
                    <td><img width="80" src=''/></td>
                    <td><?php echo $v['name'] ?><br/>
                        <input type="hidden" name="program" value="<?php echo 'fv_'.$v['id']?>">
                        <input type="hidden" name="ajaction" value="addprogram">
                        <input type="hidden" name="feedurl"
                               value="http://tools.forestview.eu/xmlp_v2/xml_feeds.php?aid=<?php echo $this->affiliator_get_option('fv_cd', '1296') ?>&cid=<?php echo $v['id'] ?>"/>
                    </td>
                    <td><?php echo mb_substr(strip_tags(html_entity_decode($v['description'])), 0, 400, 'UTF-8') ?></td>
                    <td>
                        <?php if (!$this->affiliator_get_option('program_fv_' . $v['id'])): ?>
                            <a class="button button-primary button-large" data-extra="ajaxpost"
                               href="?page=affiliator-plugin-sites">Προσθήκη</a>
                        <?php else: ?>
                            <a class="button submitdelete deletion button-large" data-extra="ajaxpost"
                               href="?page=affiliator-plugin-sites">Αφαίρεση</a>
                        <?php endif; ?>
                        <div class="ajaxresult" style="display: none"><img
                                src="<?php echo plugins_url('/../images/ajax-load.gif',__FILE__) ?>">
                        </div>
                    </td>
                </tr>
            <?php

            endforeach;?>
            </tbody>
        </table>

    <?php
    }

    private function loadLinkwisePrograms()
    {
        if(!$programs = wp_cache_get('lw_programs')){
            $programs = file_get_contents('http://affiliate.gurus.gr/lw.php');
            wp_cache_add('lw_programs',$programs,'affiliator_lw',3600);
        }

        $programs = json_decode($programs, 1);
        ?>
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
            <tr>
                <th class="manage-column column-author">#
                </th>
                <th>Λογότυπο
                </th>
                <th class="manage-column column-title sortable desc">Τίτλος
                </th>
                <th>Περιγραφή</th>


                <th>Ενέργειες</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($programs as $k => $v):

                ?>
                <tr>
                    <td><?php echo $v['id'] ?></td>
                    <td><img width="80" src='<?php echo $v['logo'] ?>'/></td>
                    <td><?php echo $v['name'] ?><br/>
                        <input type="hidden" name="program" value="<?php echo $v['id']?>">
                        <input type="hidden" name="ajaction" value="addprogram">
                        <input type="hidden" name="feedurl"
                               value="https://affiliate.linkwi.se/feeds/<?php echo $this->affiliator_get_option('lw_cd', 'CD421') ?>/columns-lw_product_id,product_id,barcode,sku,part_number,isbn,model_name,product_name,description,category,brand_name,site_url,tracking_url,thumb_url,image_url,in_stock,availability,valid_from,valid_to,on_sale,currency,price,full_price,discount,city,times_bought,longitude,latitude,address,size,colour,program_id,program_name,custom/catinc-0/catex-0/proginc-<?php echo $v['id'] ?>/progex-0/feed.xml"/>
                    </td>
                    <td><?php echo $v['short_description'] ?></td>
                    <td>
                        <?php if (!$this->affiliator_get_option('program_fv_' . $v['id'])): ?>
                            <a class="button button-primary button-large" data-extra="ajaxpost"
                               href="?page=affiliator-plugin-sites">Προσθήκη</a>
                        <?php else: ?>
                            <a class="button submitdelete deletion button-large" data-extra="ajaxpost"
                               href="?page=affiliator-plugin-sites">Αφαίρεση</a>
                        <?php endif; ?>
                        <div class="ajaxresult" style="display: none"><img
                                src="<?php echo plugins_url('/../images/ajax-load.gif',__FILE__) ?>">
                        </div>
                    </td>
                </tr>
            <?php

            endforeach;?>
            </tbody>
        </table>

    <?php
    }

    public function parse_feed()
    {
        set_time_limit(1800);
        $program = $_POST['program'];

        $page = $_POST['page'];

        $query = $_POST['query'];
        $category = $_POST['category'];


        if (is_null($page)) {
            $page = 0;
        } else {
            $page = ($page - 1) * get_option('affiliator_feed_paging');
        }

        if ($page < 0) {
            $page = 0;
        }

        //get datafeed of this program
        $feed = get_post_meta($program, 'datafeed', true);
        if(!$file = wp_cache_get('datafeed_'. $program)){
            $file = file_get_contents($feed);

            wp_cache_add('datafeed_'. $program,$file,'affiliator_feed_'.$program,3600);
        }


        list($format, $file) = $this->decideFormat($feed, $file);

        $handle = fopen(__DIR__ . '/../feeds/' . $program . '.xml', 'w');

        fwrite($handle, $file);
        fclose($handle);


        Parser_start(__DIR__ . '/../feeds/' . $program . '.xml', array($this, 'parse_helper'), $format);

        $this->show_products_table($this->parsed, $page, $query, $category);
        exit;
    }

    function makeHiddenFields($array)
    {
        foreach ($array as $k => $v) {
            $k = htmlspecialchars($k);
            $v = htmlspecialchars($v);
            echo "<input type='hidden' name='{$k}' value='{$v}'>";
        }

    }

    function show_categories_select()
    {
        wp_dropdown_categories('show_count=0&hierarchical=1');
    }

    function show_products_table($products, $page = 0, $query = null, $category = null)
    {

        $passed = $cats = array();


        foreach ($products as $k => $v) {
            $cats[$v['CATEGORY']] = $v['CATEGORY'];
            if (in_array($v['LW_PRODUCT_ID'], $passed)) {
                unset($products[$k]);
            }
            $passed[] = $v['LW_PRODUCT_ID'];

            if ($query) {

                str_ireplace($query, '', $v['PRODUCT_NAME'], $c);

                if ($c < 1) {
                    unset($products[$k]);
                }
            }

            if ($category) {
                if ($v['CATEGORY'] !== $category) {
                    unset($products[$k]);
                }
            }

        }


        if ($page) {
            $how_many_pages = round(count($products) / ($page / get_option('affiliator_feed_paging')));
        } else {
            $how_many_pages = round(count($products) / get_option('affiliator_feed_paging'));
        }

        $products = array_slice($products, $page, get_option('affiliator_feed_paging'), 0);

        ?>
        <div style="text-align: right">


            <input type="search" value="<?php echo $query ?>" id="afiliator_search">
            <button class="button" id="do_afiliator_search">Αναζήτηση</button>

            <label>
                Κατηγορία:
                <select id="afiliator_categories">
                    <?php foreach ($cats as $cat): ?>
                        <option
                            <?php if ($category == $cat): ?>selected<?php endif ?>
                            value="<?php echo $cat ?>"><?php echo $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Σελίδα:
                <select id="paginator_products">
                    <?php for ($i = 1; $i <= $how_many_pages; $i++): ?>
                        <option
                            <?php if (($page / get_option('affiliator_feed_paging')) + 1 == $i): ?>selected<?php endif ?>
                            value="<?php echo $i ?>"><?php echo $i ?></option>
                    <?php endfor; ?>
                </select>
            </label>
        </div>
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
            <tr>
                <th class="check-column"><input type="checkbox" class="checkall">
                </th>
                <th style="width: 150px">Εικόνα
                </th>
                <th class="manage-column column-title sortable desc">Τίτλος
                </th>
                <th>Περιγραφή</th>
                <th>
                    <label><input type="checkbox" checked name="wp_cat" value="1"> Χρήση:</label>

                    <?php $this->show_categories_select() ?><br/>
                    <label>
                            <span style="  display: inline-block;
  width: 66px;">Tags:</span> <input type="text" placeholder="Λέξεις κλειδιά" value="" name="tags">
                    </label><br/>
                    <a class="button button-primary button-large postall" href="?page=affiliator-plugin-products">Προσθήκη
                        επιλεγμένων</a>
                </th>
            </tr>
            </thead>
            <tbody>


            <?php foreach ($products as $k => $v):

                ?>
                <tr id="post-<?php echo $v['PRODUCT_ID'] ?>"
                    class="iedit author-self level-0 post-49 type-merchants status-publish hentry">
                    <th><input type="checkbox" name="product[]" class="checkme"
                               value="<?php echo $v['LW_PRODUCT_ID'] ?>">
                    </th>
                    <td style="width: 150px;height:150px;overflow: hidden"><img width="150"
                                                                                src='<?php echo $v['IMAGE_URL'] ?>'/>
                    </td>
                    <td>
                        <?php $this->makeHiddenFields($v) ?>
                        <?php echo $v['PRODUCT_NAME'] ?><br/>
                        <?php echo $v['CATEGORY'] ?><br/>
                        <?php echo $v['PRICE'] ?>
                    </td>
                    <td><?php echo strip_tags($v['DESCRIPTION'], '<br>') ?></td>
                    <td>
                        <label><input type="checkbox" checked name="wp_cat" value="1"> Χρήση:</label>

                        <?php $this->show_categories_select() ?>
                        <div style="display: none" class="hiddenmsg">Θα χρησιμοποιηθεί η κατηγορία του προγράμματος
                        </div>
                        <br/>
                        <label>
                            <span style="  display: inline-block;
  width: 66px;">Tags:</span> <input type="text" placeholder="Λέξεις κλειδιά" value="" name="tags">
                        </label>
                        <hr/>
                        <br/>
                        <?php if (!$this->affiliator_get_option('product_' . $v['LW_PRODUCT_ID'])): ?>
                            <a class="button button-primary button-large" data-extra="ajaxpost"
                               href="?page=affiliator-plugin-products">Προσθήκη</a>
                            <?php echo "<input type='hidden' name='ajaction' value='addproduct'>";?>
                        <?php else: ?>
                            <?php echo "<input type='hidden' name='ajaction' value='removeproduct'>";?>
                            <a class="button button-large" data-extra="ajaxpost"
                               href="?page=affiliator-plugin-products">Αφαίρεση</a>
                        <?php endif; ?>
                        <div class="ajaxresult" style="display: none"><img
                                src="<?php echo plugins_url('/../images/ajax-load.gif',__FILE__); ?>">
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php
    }

    function parse_helper($data)
    {
        $this->parsed[] = preg_replace("#\"#i", "", $data);
    }

}