<?php
/*
Plugin Name: Cudoo Search
Plugin URI:  https://cudoo.com
Description: New Cudoo Search for advance user experience.Users can search for (products,courses,categories,tags,bundles).
Version:     1.0
Author:      Ali Usman Abbasi
Author URI:  http://learningonline.xyz
License:     GPL2
*/
if (!defined('WPINC')) {
    die;
}

add_action('plugins_loaded', 'cudoo_you_do_search_admin_settings');
/**
 * Starts the plugin.
 *
 * @since 1.0.0
 */
function cudoo_you_do_search_admin_settings()
{
    register_setting('cudoo-you-do-search-settings-group', 'is_ajax');
    register_setting('cudoo-you-do-search-settings-group', 'tile_based');
    register_setting('cudoo-you-do-search-settings-group', 'per_page');
    register_setting('cudoo-you-do-search-settings-group', 'post_type');
    register_setting('cudoo-you-do-search-settings-group', 'suggest_limit');
    register_setting('cudoo-you-do-search-settings-group', 'cache_expriy');
    register_setting('cudoo-you-do-search-settings-group', 'animation');
    register_setting('cudoo-you-do-search-settings-group', 'is_ajax');

    function cudoo_you_do_search_menu()
    {
        //create new top-level menu
        add_menu_page('Cudoo Search Settings', 'Cudoo Search Settings', 'administrator', __FILE__, 'cudoo_you_doo_settings_page', plugins_url('/images/icon.png', __FILE__));
        //call register settings function
        add_action('admin_init', 'register_my_cool_plugin_settings');
    }

    add_action('admin_menu', 'cudoo_you_do_search_menu');

    /* Import plugin CSS*/
    function load_cudoo_search_style()
    {
        //css files
        wp_register_style('cudoo_search_plugin_style', plugins_url('/cudoo-search/style.css'));
        wp_enqueue_style('cudoo_search_plugin_style');
    }

    add_action('wp_footer', 'load_cudoo_search_style');

    /* Import plugin JS */
    function load_cudoo_search_script()
    {
        //javascript files
        wp_register_script('cudoo_search_plugin_js', plugins_url('/cudoo-search/script.js'));
        wp_enqueue_script('cudoo_search_plugin_js');
    }

    add_action('wp_head', 'load_cudoo_search_script');
}

function cudoo_get_search_results()
{
    if (is_page('cudoo-search-results')) {
        ?>
        <script>
            document.getElementById('cudoo_search_input').value = '<?php echo($_POST['cudoo_search_input'] ? $_POST['cudoo_search_input'] : $_POST['header_search_input']);?>';
            find_all_results('cudoo_search_input', '<?php echo plugins_url();?>');
        </script>
        <?php
    }
}

add_action('wp_footer', 'cudoo_get_search_results', 99);

function cudoo_search_shortcode($atts)
{
    if (is_user_logged_in()) {
        $showing = 'courses';
    } else {
        $showing = 'products';
    }

    $atts = shortcode_atts(array(
        'tile_based' => 'false',
        'results_per_page' => '8',
        'post_type' => 'product',
        'suggestions_limit' => '10',
        'full_width' => 'false',
        'show_post_type' => 'true',
        //'cache_expriy' => '12',
        'animate' => 'false',
        'header_search' => 'false',
    ), $atts);

    /* process shortcode params to render search */
    // is tile based?    {true, false}
    //echo 'Tiles?:'.$atts['tile_based'];
    // results_per_page  {6,8,10,12 ...}
    //echo 'Results per page?:'.$atts['results_per_page'];
    // suggestions_limit { 6, 8, 10 ...}
    //echo 'Suggestion limit?:'.$atts['suggestions_limit'];
    // animate           {true, false}
    //echo 'Animation?:'.$atts['animate'];

    /* if tile based search */
    if ($atts['tile_based'] === "true") {
        $search_html .= '<div class="vc_row wpb_row section vc_row-fluid" style=" text-align:left;">
<div class=" full_section_inner clearfix">
<div class="wpb_column vc_column_container vc_col-sm-12">
<div class="vc_column-inner ">
<div class="wpb_wrapper">
<div class="vc_row wpb_row section vc_inner vc_row-fluid" style=" text-align:left;">
<div class=" full_section_inner clearfix">
   <div id="search_elem_wrap" style="opacity:1;">
        <div class="wpb_column vc_column_container vc_col-sm-12">
        <div class="vc_column-inner ">
        <div class="wpb_wrapper">
        <i class="fa fa-circle" id="search_ball" aria-hidden="true" style="font-size: 10px;"></i>
            <input autofocus id="cudoo_search_input" class="cudoo_search_input smart" autocomplete="off" name="cudoo_search_input" oninput="find_all_results(this.id,\'' . plugins_url() . '\');" type="text" placeholder="What will you learn today?">
        </div>
        </div>
        </div>
        
            <div class="wpb_column vc_column_container vc_col-sm-4">
            <div class="vc_column-inner " style="text-align: center;">
            <div class="wpb_wrapper">
        <label id="label_page_info"> </label>
                </div>
                </div>
                </div>
        <div class="wpb_column vc_column_container vc_col-sm-4">
            <div class="vc_column-inner " style="text-align: center;">
            <div class="wpb_wrapper">
                                            <span id="search_navigation"> </span>
                </div>
                </div>
                </div>
                                            <div class="wpb_column vc_column_container vc_col-sm-4">
            <div class="vc_column-inner " style="text-align: center;">
            <div class="wpb_wrapper">
                                            <label id="label_total_results"> </label>
                </div>
                </div>
                </div>
       
       <div class="wpb_column vc_column_container vc_col-sm-12" style="min-height: 30px;text-align: left;">
        <div class="vc_column-inner ">
        <div class="wpb_wrapper">
        <div id="cudoo_search_loading_wrap">
        <img id="cudoo_search_loading" style="background:#fff" src="' . plugins_url("/cudoo-search/fountain.gif") . '">
    </div>
        </div>
        </div>
        </div>
   </div>
 
    
    
    <div class="tile_based_results_wrap">
        <div id="cudoo_search_results"> </div>
    </div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>';
    }
    /* if not tile based search */
    if ($atts['tile_based'] === 'false' AND $atts['full_width'] === 'false' AND $atts['header_search'] === 'false') {
        if ($atts['show_post_type'] === 'false') {
            //hide post types
            echo '<style>.cudoo_search_results ._align_r{display:none !important}.cudoo_search_results .no_tile{float:left;width:100% !important;}</style>';
        }


        $search_html .= '<div class="vc_row wpb_row section vc_row-fluid" style=" text-align:left;">
<div class=" full_section_inner clearfix">
<div class="wpb_column vc_column_container vc_col-sm-3">
<div class="vc_column-inner "><div class="wpb_wrapper"></div></div></div>
<div class="wpb_column vc_column_container vc_col-sm-6">
<div class="vc_column-inner "><div class="wpb_wrapper"><div class="vc_row wpb_row section vc_inner vc_row-fluid" style=" text-align:left;"><div class=" full_section_inner clearfix">
<div class="wpb_column vc_column_container vc_col-sm-12"><div class="vc_column-inner ">
<div class="wpb_wrapper"><div class="wpb_text_column wpb_content_element ">
<div class="wpb_wrapper">
<div id="cudoo_search_wrap">
<form id="cudoo_search_form" action="/cudoo-search-results/" method="post">
<input id="cudoo_search_input" oninput="find_suggested_results(this.id,\'' . plugins_url() . '\',\'false\');" class="cudoo_search_input" autocomplete="off" name="cudoo_search_input" type="text" placeholder="What will you learn today?">
<input type="submit" value="Search" id="cudoo_search_btn" name="cudoo_search_btn">
</form>
<div id="cudoo_search_loading_wrap">
<img id="cudoo_search_loading" src="' . plugins_url("/cudoo-search/fountain.gif") . '"></div>
<div id="cudoo_search_results_wrap" class="suggestion_search_wrap">
<div id="cudoo_search_results"></div>
</div>
</div>
</div> 
</div></div></div></div></div></div></div></div></div><div class="wpb_column vc_column_container vc_col-sm-3"><div class="vc_column-inner "><div class="wpb_wrapper"></div></div></div></div></div>';
    }

    /* if not tile based search and full_width required */
    if ($atts['tile_based'] === 'false' AND $atts['full_width'] === 'true') {
        if ($atts['show_post_type'] === 'false') {
            //hide post types
            echo '<style>.no_tile ._align_r{display:none !important}.no_tile{float:left;width:100%}</style>';
        }
        $search_html .= '<div id="cudoo_search_wrap" style="min-height:50px;z-index;0;">
<input style="width: 70%;border: 1px solid silver !important;border-radius: 4px 0 0 4px;padding: 3px 1%;
    margin: 0;float: left;" oninput="find_suggested_results(this.id,\'' . plugins_url() . '\',\'false\');" id="cudoo_search_input"  class="cudoo_search_input" autocomplete="off" name="cudoo_search_input" type="text" placeholder="What will you learn today?">
<input style="width: 27%;margin: 0;" type="submit" value="Search" id="cudoo_search_btn" name="cudoo_search_btn" >
<div id="cudoo_search_loading_wrap">
<img id="cudoo_search_loading" style="margin-top: 42px;" src="' . plugins_url("/cudoo-search/fountain.gif") . '"></div>
<div id="cudoo_search_results_wrap" class="suggestion_search_wrap" style="margin-top:42px;">
<div id="cudoo_search_results"> </div>
</div>
</div>';
    }

    //if header search
    if ($atts['tile_based'] === 'false' AND $atts['full_width'] === 'false' AND $atts['header_search'] === 'true') {
        echo '<style>.fullscreen_search_holder.fade{display:none}</style>';
        $search_html .= '<div id="cudoo_search_wrap" style="opacity:0" class="header_search">
<form id="cudoo_search_form" action="/cudoo-search-results/" style="height:0px;" method="post">
<input id="header_search_input" oninput="find_suggested_results(this.id,\'' . plugins_url() . '\',\'true\');" class="cudoo_search_input" autocomplete="off" name="cudoo_search_input" type="text" placeholder="What will you learn today?">
<input type="submit" value="Search" id="cudoo_search_btn" class="header_search_btn" name="cudoo_search_btn">
</form>
<div id="header_search_loading_wrap">
<img id="header_search_loading" src="' . plugins_url("/cudoo-search/fountain.gif") . '"/></div>
<div id="header_search_results_wrap" style="margin: 0 auto;background: rgba(255, 255, 255, 0.9);width: 60%;box-shadow: rgb(220, 220, 220) 0px 1px 3px;border-bottom-color: rgb(255, 255, 255);" class="suggestion_search_wrap">
<div id="header_search_results" style="background:transparent !important;"></div>
</div>
</div>';
    }


    return $search_html;
}

// register shortcode for wp
add_shortcode('cudoo_search', 'cudoo_search_shortcode');

