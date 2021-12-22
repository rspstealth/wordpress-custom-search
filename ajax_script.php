<?php
/**
 * Created by PhpStorm.
 * User: ali usman
 * Date: 1/26/2017
 * Time: 3:11 PM
 */
//get wp support
require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
$user_id= get_current_user_id();
$regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;

//config
$is_ajax = NULL;
$splitter = 'ET_SPLIT_ET';
$tile_based = $_POST['tile_based'];
if ($_POST['tile_based'] === 'true') {
    $tile_based = 'cudoo_search_tile';
    $limit = 1000;
} else {
    $tile_based = 'no_tile';
    $limit = 9;
}

if (false === ($search_results = get_transient('cudoo_search_results'))) {
        global $wpdb, $post;
        $cats = get_categories();
        //all results array
        $search_results = array();
        //$output .= '{"posts":[';
        $i = 0;

    foreach ($cats as $cat) {
            //echo $cat->name.'<br>';
            // title
            $search_results[$i]['title'] = $cat->name;
            // level
            // url
            $search_results[$i]['url'] = site_url() . '/product-category/' . $cat->slug;
            // thumb
            $search_results[$i]['image'] = '<img src="'.site_url().'/wp-content/uploads/2017/02/category.png" alt="category" />';//get_the_post_thumbnail($cat->ID, 'medium');
            $search_results[$i]['type'] = 'category';
            $search_results[$i]["desc"] = excerpt(12, category_description($result->ID));

            //order
            $search_results[$i]['order'] = 1;

            //index
            $i++;
    }

    $product_related_courses = array();
        // get all products and store related_courses in an array
        $results = $wpdb->get_results("SELECT ID,post_title,post_type,post_excerpt FROM $wpdb->posts WHERE post_type='product' AND post_status='publish' ORDER BY post_title ASC");
        foreach ($results as $result) {
            $search_results[$i]['title'] = $result->post_title;
            $search_results[$i]['url']   = get_permalink($result->ID);
            $search_results[$i]["desc"]  = excerpt(12, strip_tags(get_the_excerpt($result->ID)));
            $search_results[$i]['image'] = get_the_post_thumbnail($result->ID, 'medium');
            $search_results[$i]['type']  = ($result->post_type === 'sfwd-courses') ? 'course' : $result->post_type;
            $search_results[$i]['order'] = 2;
            $product_related_courses[$result->ID] = get_post_meta($result->ID, '_related_course');
            //index
            $i++;
        }
        // go thru each related_course element
        // and check if course_id is in array then add that product image to it
        $results = $wpdb->get_results("SELECT ID,post_title,post_type,post_excerpt FROM $wpdb->posts WHERE post_type='sfwd-courses' AND post_status='publish' ORDER BY post_title ASC");
        foreach ($results as $result) {
            $search_results[$i]['title'] = $result->post_title;
            $search_results[$i]['url'] = get_permalink($result->ID);
            $search_results[$i]["desc"] = strip_tags(excerpt(12, get_post_field('post_content', $result->ID)));
            /*foreach ($product_related_courses as $p_id => $related) {
                if (in_array($result->ID, $related[0])) {
                    //echo '<br>PID'.$p_id.' related to'.$result->ID;
                    //echo get_the_post_thumbnail($p_id,'medium');
                    $search_results[$i]['image'] = get_the_post_thumbnail($p_id, 'medium');
                }
            }*/

            ###########
            ###########
            ###########
            ###########
            // Get the course image
            $custom_data = get_post_meta($result->ID, '_sfwd-courses');
            $sfwd_courses_course_price_type = $custom_data[0]['sfwd-courses_course_price_type'];
            $sfwd_courses_custom_button_url = $custom_data[0]['sfwd-courses_custom_button_url'];
            if ($sfwd_courses_course_price_type == "closed" && !empty($sfwd_courses_custom_button_url)) {
                $product_url = $sfwd_courses_custom_button_url;   // http://cudoo.com/?post_type=product&amp;p=13377
                $product_id_array = explode('=', $product_url);
                $product_id = array_pop((array_slice($product_id_array, -1)));
                //echo 'pid:'.$product_id;
                $image = get_the_post_thumbnail( $product_id , 'medium' );
            }
            // var_dump("test");exit();
            $search_results[$i]['image'] = $image;//
            ###########
            ###########
            ###########
            ###########
            $search_results[$i]['type'] = ($result->post_type === 'sfwd-courses') ? 'course' : $result->post_type;
            $search_results[$i]['order'] = 2;
            //index
            $i++;
        }

        // now get tags
        $tags = get_tags();
        $tags_array = get_tags($args);
        //var_dump($tags_array);

        foreach ($tags_array as $tag) {
            $search_results[$i]['title'] = $tag->name;
            $search_results[$i]['url'] = site_url() . '/product-tag/' . $tag->slug . '/';
            $search_results[$i]["desc"] = strip_tags(excerpt(12, tag_description($tag->id)));
            $search_results[$i]['type'] = 'tag';
            $search_results[$i]['order'] = 3;
            $i++;
        }

        //get bundles
        $bundles = $wpdb->get_results("SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy='pa_bundles'");
        foreach ($bundles as $bundle) {
            //echo 'TERM ID:'.$bundle->term_id;
            $term_bundle = $wpdb->get_results("SELECT term_id,name,slug FROM $wpdb->terms WHERE (term_id=$bundle->term_id AND name<>'') ORDER BY name ASC");
            // title
            $search_results[$i]['title'] = $term_bundle[0]->name;
            // url
            $search_results[$i]['url'] = site_url() . '/pa_bundles/' . $term_bundle[0]->slug;
            // desc
            $search_results[$i]["desc"] = '';
            // thumb
            $search_results[$i]['image'] =  '<img src="'.site_url().'/wp-content/uploads/2017/02/bundle.png" alt="category" />';//get_the_post_thumbnail($bundle->term_id, 'medium');
            $search_results[$i]['type'] = 'bundles';
            //order
            $search_results[$i]['order'] = 4;
            $i++;
        }
        //var_dump($search_results);
        //save results in cache and exit file
        set_transient('cudoo_search_results', $search_results, 60 * 60 * 12);
        goto Search;
        exit();
} else {
    Search:
    $user_meta=get_userdata($user_id);
    $user_roles=$user_meta->roles;
    if(in_array("administrator", $user_roles)){
    }



    //remove except alphanemeric
    //$searched_text = preg_replace("/[^A-Za-z0-9\-]/", "", trim(htmlspecialchars($_POST['searched_text'])));
    $searched_text = preg_replace($regex, '$1', trim(htmlspecialchars($_POST['searched_text'])) );
    // search through the records now
    $search_results = get_transient('cudoo_search_results');
    //echo 'Total :'.count($search_results).'<br>';
    //var_dump($search_results);
    $got = 0;

    //limiting tags to two, max
    $two_limit = 0;
    $one_to_ten = 1;
    //if exact match
    foreach ($search_results as $key => $result) {
        //for comparison, we will replace every char except alphanumeric ones
        $valid_result_title = preg_replace($regex, '$1', $result['title']);
        if (!empty($valid_result_title)) {
            //echo '<br>search:'.$searched_text.' =>>> ';
            //echo 'valid:'.$valid_result_title;
            //exact
            if (strtolower($valid_result_title) === strtolower($searched_text)) {
                //echo 'status:'.is_user_logged_in().'<br>';

                if (is_user_logged_in()) {
                    //echo 'Matched'.$result['title'].'==='.$searched_text.' => '.$result['type'];
                    if ($result['type'] == 'product') {
                        //if logged in skip products
                        //echo 'Skipping this result:' . $result['title'] . '= >' . $result['type'];
                        continue;
                    }
                }

                if ( !is_user_logged_in() ) {
                    //echo 'Matched'.$result['title'].'==='.$searched_text.' => '.$result['type'];
                    if ($result['type'] == 'course') {
                        //if logged in skip courses
                        //echo 'Skipping this result:'.$result['title'].'=>'.$result['type'];
                        continue;
                    }
                }

                if ($result['type'] == 'category') {
                    if ( !is_user_logged_in() ) {
                        
                        $order_op1 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                        $order_op1 .= $result['url'];
                        $order_op1 .= '" title="Click or press Enter to open selected">';
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op1 .= $result['image'];
                        }
                        $order_op1 .= '<span class="search_title">' . $result['title'] . '</span>';
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op1 .= '<div class="search_post_desc">' . $result["desc"] . '</div>';
                            $order_op1 .= '<span class="view_related_btn" title="View All Bundled Products" href="' . $result["url"] . '">View Related</span>';
                        }
                        $order_op1 .= '</a>';
                        $one_to_ten++;


                        if ($tile_based === 'no_tile') {
                            $order_op1 .= '<span class="_align_r">';
                            if (in_array("administrator", $user_roles)) {
                                $order_op1 .= $result['type'];
                            }
                            $order_op1 .= '</span><br>';
                        }

                        //add splitter ET_SPLIT_ET
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op1 .= $splitter;
                        }
                    }
                    //remove this element from array now
                    unset($search_results[$key]);
                } elseif ($result['type'] == 'tag') {

                        if ($tile_based === 'no_tile') {

                            $two_limit++;
                            if ($two_limit < 3) {
                                if ( !is_user_logged_in() ) {

                                    
                                    $order_op2 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                                    $order_op2 .= $result['url'];
                                    $order_op2 .= '" title="Click or press Enter to open selected">';
                                    if ($tile_based === 'cudoo_search_tile') {
                                        $order_op2 .= $result['image'];
                                    }
                                    $order_op2 .= '<span class="search_title">' . $result['title'] . '</span>';
                                    if ($tile_based === 'cudoo_search_tile') {
                                        $order_op2 .= '<div class="search_post_desc">' . $result["desc"] . '</div>';
                                    }
                                    $order_op2 .= '</a>';
                                    if ($tile_based === 'no_tile') {
                                        $order_op2 .= '<span class="_align_r">';
                                        if (in_array("administrator", $user_roles)) {
                                            $order_op2 .= $result['type'];
                                        }
                                        $order_op2 .= '</span><br>';
                                    }
                                }
                                $one_to_ten++;

                                //remove this element from array now
                                //echo 'removing this item'.$search_results[$key]['tag'];
                                unset($search_results[$key]);
                            }

                        }

                } elseif ($result['type'] === 'bundles') {
                    //don't show the bundle results in search when user is (logged in)
                    if ( !is_user_logged_in() ) {
                        $order_op3 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                        $order_op3 .= $result['url'];
                        $order_op3 .= '" title="Click or press Enter to open selected">';
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op3 .= $result['image'];
                        }
                        $order_op3 .= '<span class="search_title">' . $result['title'] . '</span>';
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op3 .= '<div class="search_post_desc">' . $result["desc"] . '</div>';
                            $order_op3 .= '<span class="view_related_btn" title="View All Bundled Products" href="' . $result["url"] . '">View Related</span>';

                        }
                        $order_op3 .= '</a>';
                        $one_to_ten++;

                        if ($tile_based === 'no_tile') {
                            $order_op3 .= '<span class="_align_r">';
                            if (in_array("administrator", $user_roles)) {
                                $order_op3 .= $result['type'];
                            }
                            $order_op3 .= '</span><br>';
                        }
                        //add splitter ET_SPLIT_ET
                        if ($tile_based === 'cudoo_search_tile') {

                            $order_op3 .= $splitter;
                        }
                    }
                    //remove this element from array now
                    unset($search_results[$key]);
                } elseif ($result['type'] === 'product') {

                    $order_op4 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                    $order_op4 .= $result['url'];
                    $order_op4 .= '" title="Click or press Enter to open selected">';
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op4 .= $result['image'];
                    }
                    $order_op4 .= '<span class="search_title">' . $result['title'] . '</span>';
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op4 .= '<div class="search_post_desc">' . $result["desc"] . '</div>';
                    }
                    $order_op4 .= '</a>';
                    $one_to_ten++;

                    if ($tile_based==='no_tile') {
                        $order_op4 .= '<span class="_align_r">';
                        if(in_array("administrator", $user_roles)){
                            $order_op4 .= $result['type'];
                        }
                        $order_op4 .= '</span><br>';
                    }
                    //add splitter ET_SPLIT_ET
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op4 .= $splitter;
                    }

                //remove this element from array now
                unset($search_results[$key]);
                } elseif ($result['type'] === 'course') {
                    $order_op5 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                    $order_op5 .= $result['url'];
                    $order_op5 .= '" title="Click or press Enter to open selected">';
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op5 .= $result['image'];
                    }
                    $order_op5 .= '<span class="search_title">' . $result['title'] . '</span>';
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op5 .= '<div class="search_post_desc">' . $result["desc"] . '</div>';
                    }
                    $order_op5 .= '</a>';
                    $one_to_ten++;

                    if ($tile_based==='no_tile') {
                        $order_op5 .= '<span class="_align_r">';
                        if(in_array("administrator", $user_roles)){
                            $order_op5 .= $result['type'];
                        }
                        $order_op5 .= '</span><br>';
                    }
                    //add splitter ET_SPLIT_ET
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op5 .= $splitter;
                    }

                    //remove this element from array now
                    unset($search_results[$key]);
                }

                if ($got < $limit) {
                    $got++;
                } else {

                    //exit foreach
                    break;
                }
            }
        }


    }

    //if exact match
    foreach ($search_results as $key => $result) {
        //for comparison, we will replace every char except alphanumeric ones
        //$valid_result_title = preg_replace("/[^A-Za-z0-9\-]/", "", $result['title']);

        $valid_result_title = preg_replace($regex, '$1', $result['title']);
        if (!empty($valid_result_title)) {
            //contains
            if ((strpos(strtolower($valid_result_title), strtolower($searched_text)) !== false)) {
                //echo 'status:'.is_user_logged_in().'<br>';

                //echo '<br>matching:'.strtolower($valid_result_title).' with '.strtolower($searched_text);

                if (is_user_logged_in()) {
                    //echo 'Matched'.$result['title'].'==='.$searched_text.' => '.$result['type'];
                    if ($result['type'] == 'product') {
                        //if logged in skip products
                        //echo 'Skipping this result:' . $result['title'] . '= >' . $result['type'];
                        continue;
                    }
                }

                if ( !is_user_logged_in() ) {
                    //echo 'Matched'.$result['title'].'==='.$searched_text.' => '.$result['type'];

                    if ($result['type'] == 'course') {
                        //if logged in skip courses
                        //echo 'Skipping this result:'.$result['title'].'=>'.$result['type'];
                        continue;
                    }
                }

                if ($result['type'] == 'category') {
                    if ( !is_user_logged_in() ) {
                        
                        $order_op1 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                        $order_op1 .= $result['url'];
                        $order_op1 .= '" title="Click or press Enter to open selected">';
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op1 .= $result['image'];
                        }
                        $order_op1 .= '<span class="search_title">' . $result['title'] . '</span>';
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op1 .= '<div class="search_post_desc">' . $result["desc"] . '</div>';
                            $order_op1 .= '<span class="view_related_btn" title="View all category products" href="' . $result["url"] . '">View Related</span>';

                        }
                        $order_op1 .= '</a>';
                        $one_to_ten++;

                        if ($tile_based === 'no_tile') {
                            $order_op1 .= '<span class="_align_r">';
                            if (in_array("administrator", $user_roles)) {
                                $order_op1 .= $result['type'];
                            }
                            $order_op1 .= '</span><br>';
                        }
                        //add splitter ET_SPLIT_ET
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op1 .= $splitter;
                        }
                    }
                    //remove this element from array now
                    unset($search_results[$key]);
                } elseif ($result['type'] == 'tag') {
                    if ($tile_based==='no_tile') {

                        $two_limit++;
                        if ($two_limit < 3) {
                            if ( !is_user_logged_in() ) {
                                
                                $order_op2 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                                $order_op2 .= $result['url'];
                                $order_op2 .= '" title="Click or press Enter to open selected">';
                                if ($tile_based === 'cudoo_search_tile') {
                                    $order_op2 .= $result['image'];
                                }
                                $order_op2 .= '<span class="search_title">' . $result['title'] . '</span>';
                                if ($tile_based === 'cudoo_search_tile') {
                                    $order_op2 .= '<div class="search_post_desc">' . $result["desc"] . '</div>';
                                }
                                $order_op2 .= '</a>';
                                $one_to_ten++;

                                if ($tile_based === 'no_tile') {
                                    $order_op2 .= '<span class="_align_r">';
                                    if (in_array("administrator", $user_roles)) {
                                        $order_op2 .= $result['type'];
                                    }
                                    $order_op2 .= '</span><br>';
                                }
                            }
                            //remove this element from array now
                            //echo 'removing this item'.$search_results[$key]['tag'];
                            unset($search_results[$key]);
                        }

                    }

                } elseif ($result['type'] === 'bundles') {
                    //don't show the bundle results in search when user is (logged in)
                    if ( !is_user_logged_in() ) {
                        $order_op3 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                        $order_op3 .= $result['url'];
                        $order_op3 .= '" title="Click or press Enter to open selected">';
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op3 .= $result['image'];
                        }
                        $order_op3 .= '<span class="search_title">' . $result['title'] . '</span>';
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op3 .= '<div class="search_post_desc">' . $result["desc"] . '</div>';
                            $order_op3 .= '<span class="view_related_btn" title="View All Bundled Products" href="' . $result["url"] . '">View Related</span>';

                        }
                        $order_op3 .= '</a>';
                        $one_to_ten++;

                        if ($tile_based === 'no_tile') {
                            $order_op3 .= '<span class="_align_r">';
                            if (in_array("administrator", $user_roles)) {
                                $order_op3 .= $result['type'];
                            }
                            $order_op3 .= '</span><br>';
                        }
                        //add splitter ET_SPLIT_ET
                        if ($tile_based === 'cudoo_search_tile') {
                            $order_op3 .= $splitter;
                        }
                    }
                    //remove this element from array now
                    unset($search_results[$key]);
                } elseif ($result['type'] === 'product') {

                    $order_op4 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                    $order_op4 .= $result['url'];
                    $order_op4 .= '" title="Click or press Enter to open selected">';
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op4 .= $result['image'];
                    }
                    $order_op4 .= '<span class="search_title">' . $result['title'] . '</span>';
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op4 .= '<div class="search_post_desc">' . $result["desc"] . '</div>';
                    }
                    $order_op4 .= '</a>';
                    $one_to_ten++;

                    if ($tile_based==='no_tile') {
                        $order_op4 .= '<span class="_align_r">';
                        if(in_array("administrator", $user_roles)){
                            $order_op4 .= $result['type'];
                        }
                        $order_op4 .= '</span><br>';
                    }
                    //add splitter ET_SPLIT_ET
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op4 .= $splitter;
                    }

                    //remove this element from array now
                    unset($search_results[$key]);
                } elseif ($result['type'] === 'course') {
                    $order_op5 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                    $order_op5 .= $result['url'];
                    $order_op5 .= '" title="Click or press Enter to open selected">';
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op5 .= $result['image'];
                    }
                    $order_op5 .= '<span class="search_title">' . $result['title'] . '</span>';
                    if ($tile_based==='cudoo_search_tile') {
                        if(isset($result["desc"])){
                            $order_op5 .= '<div class="search_post_desc">' . $result["desc"] . '</div>';
                        }else{
                        }
                    }
                    $order_op5 .= '</a>';
                    $one_to_ten++;

                    if ($tile_based==='no_tile') {
                        $order_op5 .= '<span class="_align_r">';
                        if(in_array("administrator", $user_roles)){
                            $order_op5 .= $result['type'];
                        }
                        $order_op5 .= '</span><br>';
                   }
                    //add splitter ET_SPLIT_ET
                    if ($tile_based==='cudoo_search_tile') {
                        $order_op5 .= $splitter;
                    }

                    //remove this element from array now
                    unset($search_results[$key]);
                }

                if ($got < $limit) {
                    $got++;
                } else {

                    //exit foreach
                    break;
                }
            }
        }

    }
    // echo 'Total :'.count($search_results).'<br>';
    // echo 'Found:'.$got;

    //if startsWith
    if ($got < $limit) {
        //look starting chars matches
        foreach ($search_results as $key => $result) {
            if (!empty($result['title'])) {
                //for comparison, we will replace every char except alphanumeric ones
                //$valid_result_title = preg_replace("/[^A-Za-z0-9\-]/", "", $result['title']);
                $valid_result_title = preg_replace($regex, '$1', $result['title']);
                //if exact match
                if (strpos(strtolower($result['title']), strtolower(trim(htmlspecialchars($_POST['searched_text'])))) === 0) {
                    // echo 'status:'.is_user_logged_in().'<br>';
                    // echo 'startsWith:'.$searched_text.'==='.$result['title'].' => '.$result['type'];

                    if (is_user_logged_in()) {
                        if ($result['type'] == 'product') {
                            //if logged in skip products
                            //echo 'Skipping this result:' . $result['title'] . '= >' . $result['type'];
                            continue;
                        }
                    }

                    if ( !is_user_logged_in() ) {

                        if ($result['type'] == 'course') {
                            //if logged in skip courses
                            // echo 'Skipping this result:'.$result['title'].'=>'.$result['type'];
                            continue;
                        }
                    }

                    if ($result['type'] == 'category') {
                        if ( !is_user_logged_in() ) {
                            
                            $order_op1 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                            $order_op1 .= $result['url'];
                            $order_op1 .= '" title="Click or press Enter to open selected">';
                            if ($tile_based === 'cudoo_search_tile') {
                                $order_op1 .= $result['image'];
                            }
                            $order_op1 .= '<span class="search_title">' . $result['title'] . '</span>';


                            if ($tile_based === 'no_tile') {
                                $order_op1 .= '</a>';
                                $order_op1 .= '<span class="_align_r">';
                                if (in_array("administrator", $user_roles)) {
                                    $order_op1 .= $result['type'];
                                }
                                $order_op1 .= '</span><br>';
                            }
                                //add splitter ET_SPLIT_ET
                                if ($tile_based === 'cudoo_search_tile') {
                                    $order_op1 .= '<div class="search_post_desc">' . $result["desc"] . '</div></a>';
                                    $order_op1 .= '<span class="view_related_btn" title="View all category products" href="' . $result["url"] . '">View Related</span>';
                                    $order_op1 .= $splitter;
                                }


                        }
                        $one_to_ten++;

                        //remove this element from array now
                        unset($search_results[$key]);
                    } elseif ($result['type'] == 'tag') {
                        if ($tile_based==='no_tile') {
                            $two_limit++;
                            if ($two_limit < 3) {
                                if ( !is_user_logged_in()) {
                                    
                                    $order_op2 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                                    $order_op2 .= $result['url'];
                                    $order_op2 .= '" title="Click or press Enter to open selected">';
                                    if ($tile_based === 'cudoo_search_tile') {
                                        $order_op2 .= $result['image'];
                                    }
                                    $order_op2 .= '<span class="search_title">' . $result['title'] . '</span>';
                                    $order_op2 .= '</a>';

                                    if ($tile_based === 'no_tile') {
                                        $order_op2 .= '<span class="_align_r">';
                                        if (in_array("administrator", $user_roles)) {
                                            $order_op2 .= $result['type'];
                                        }
                                        $order_op2 .= '</span><br>';
                                    }
                                }
                                $one_to_ten++;
                                //remove this element from array now
                                //echo 'removing this item'.$search_results[$key]['tag'];
                                unset($search_results[$key]);
                            }
                        }
                    } elseif ($result['type'] === 'bundles') {
                        //don't show the bundle results in search when user is (logged in)
                        if ( !is_user_logged_in() ) {
                            $order_op3 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                            $order_op3 .= $result['url'];
                            $order_op3 .= '" title="Click or press Enter to open selected">';
                            if ($tile_based === 'cudoo_search_tile') {
                                $order_op3 .= $result['image'];
                            }
                            $order_op3 .= '<span class="search_title">' . $result['title'] . '</span>';


                            if ($tile_based === 'no_tile') {
                                $order_op3 .= '</a>';

                                $order_op3 .= '<span class="_align_r">';
                                if (in_array("administrator", $user_roles)) {
                                    $order_op3 .= $result['type'];
                                }
                                $order_op3 .= '</span><br>';
                            }
                            //add splitter ET_SPLIT_ET
                            if ($tile_based === 'cudoo_search_tile') {
                                $order_op3 .= '<div class="search_post_desc">' . $result["desc"] . '</div></a>';
                                $order_op3 .= '<span class="view_related_btn" title="View All Bundled Products" href="' . $result["url"] . '">View Related</span>';
                                $order_op3 .= $splitter;
                            }
                            $one_to_ten++;
                        }

                        //remove this element from array now
                        unset($search_results[$key]);
                    } elseif ($result['type'] === 'product') {

                        $order_op4 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                        $order_op4 .= $result['url'];
                        $order_op4 .= '" title="Click or press Enter to open selected">';
                        if ($tile_based==='cudoo_search_tile') {
                            $order_op4 .= $result['image'];
                        }
                        $order_op4 .= '<span class="search_title">' . $result['title'] . '</span>';

                        //add splitter ET_SPLIT_ET
                        if ($tile_based==='cudoo_search_tile') {
                            $order_op4 .= '<div class="search_post_desc">' . $result["desc"] . '</div></a>';
                            $order_op4 .= $splitter;
                        }

                        if ($tile_based==='no_tile') {
                            $order_op4 .= '</a>';
                            $order_op4 .= '<span class="_align_r">';
                            if(in_array("administrator", $user_roles)){
                                $order_op4 .= $result['type'];
                            }
                            $order_op4 .= '</span><br>';
                        }
                        $one_to_ten++;
                        //remove this element from array now
                        unset($search_results[$key]);
                    } elseif ($result['type'] === 'course') {
                        $order_op5 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                        $order_op5 .= $result['url'];
                        $order_op5 .= '" title="Click or press Enter to open selected">';
                        if ($tile_based==='cudoo_search_tile') {
                            $order_op5 .= $result['image'];
                        }
                        $order_op5 .= '<span class="search_title">' . $result['title'] . '</span>';


                        if ($tile_based==='no_tile') {
                            $order_op5 .= '</a>';
                            $order_op5 .= '<span class="_align_r">';
                            if(in_array("administrator", $user_roles)){
                                $order_op5 .= $result['type'];
                            }
                            $order_op5 .= '</span><br>';
                             }

                        //add splitter ET_SPLIT_ET
                        if ($tile_based==='cudoo_search_tile') {
                            $order_op5 .= '<div class="search_post_desc">' . $result["desc"] . '</div></a>';
                            $order_op5 .= $splitter;
                        }

                        $one_to_ten++;
                        //remove this element from array now
                        unset($search_results[$key]);
                    }

                    if ($got < $limit) {
                        $got++;
                    } else {

                        //exit foreach
                        break;
                    }
                }
            }

        }
    }

    //if startsWith
    if ($got < $limit) {


        $chunks = explode(" ", trim(htmlspecialchars($_POST['searched_text'])));
        foreach ($chunks as $chunk) {
            //echo '<br>C:'.$chunk;

            //look starting chars matches
            foreach ($search_results as $key => $result) {

                if ($got === $limit) {

                    break;
                }

                if (!empty($result['title'])) {
                    //for comparison, we will replace every char except alphanumeric ones
                    // $valid_result_title = preg_replace("/[^A-Za-z0-9\-]/", "", $result['title']);
                    $valid_result_title = preg_replace($regex, '$1', $result['title']);
                    //if exact match


                    //echo 'q:'.trim(htmlspecialchars($_POST['searched_text']));
                    //echo 'for:'.'<span class="search_title">'.$result['title'].'</span>';

                    if (preg_match("/$chunk/i", $result['title'])) {

                        if (is_user_logged_in()) {
                            // echo 'Matched'.$result['title'].'==='.$searched_text.' => '.$result['type'];
                            if ($result['type'] == 'product') {
                                //if logged in skip products
                                //echo 'Skipping this result:' . $result['title'] . '= >' . $result['type'];
                                continue;
                            }
                        }

                        if ( !is_user_logged_in() ) {
                            //echo 'Matched'.$result['title'].'==='.$searched_text.' => '.$result['type'];

                            if ($result['type'] == 'course') {
                                //if logged in skip courses
                                // echo 'Skipping this result:'.$result['title'].'=>'.$result['type'];
                                continue;
                            }
                        }

                        if ($result['type'] === 'category') {
                            if ( !is_user_logged_in() ) {
                                
                                $order_op1 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                                $order_op1 .= $result['url'];
                                $order_op1 .= '" title="Click or press Enter to open selected">';
                                if ($tile_based === 'cudoo_search_tile') {
                                    $order_op1 .= $result['image'];
                                }
                                $order_op1 .= '<span class="search_title">' . $result['title'] . '</span>';

                                if ($tile_based === 'no_tile') {
                                    $order_op1 .= '</a>';
                                    $order_op1 .= '<span class="_align_r">';
                                    if (in_array("administrator", $user_roles)) {
                                        $order_op1 .= $result['type'];
                                    }
                                    $order_op1 .= '</span><br>';
                                }
                                //add splitter ET_SPLIT_ET
                                if ($tile_based === 'cudoo_search_tile') {
                                    $order_op1 .= '<div class="search_post_desc">' . $result["desc"] . '</div></a>';
                                    $order_op1 .= '<span class="view_related_btn" title="View all products in this category" href="' . $result["url"] . '">View Related</span>';

                                    $order_op1 .= $splitter;
                                }
                                $one_to_ten++;
                            }
                            //remove this element from array now
                            unset($search_results[$key]);
                        } elseif ($result['type'] === 'tag') {
                            if ($tile_based==='no_tile') {
                                $two_limit++;

                                if ($two_limit < 3) {
                                    if ( !is_user_logged_in() ) {
                                        
                                        $order_op2 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                                        $order_op2 .= $result['url'];
                                        $order_op2 .= '" title="Click or press Enter to open selected">';
                                        if ($tile_based === 'cudoo_search_tile') {
                                            $order_op2 .= $result['image'];
                                        }
                                        $order_op2 .= '<span class="search_title">' . $result['title'] . '</span>';

                                        if ($tile_based === 'no_tile') {
                                            $order_op2 .= '</a>';

                                            $order_op2 .= '<span class="_align_r">';
                                            if (in_array("administrator", $user_roles)) {
                                                $order_op2 .= $result['type'];
                                            }
                                            $order_op2 .= '</span><br>';
                                        }
                                        //add splitter ET_SPLIT_ET
                                        if ($tile_based === 'cudoo_search_tile') {
                                            $order_op2 .= '<div class="search_post_desc">' . $result["desc"] . '</div></a>';
                                            $order_op2 .= '<a class="view_related_btn" title="View all tagged courses" href="' . $result["url"] . '">View Related</a>';
                                            $order_op2 .= $splitter;
                                        }
                                        $one_to_ten++;
                                    }
                                    //remove this element from array now
                                    unset($search_results[$key]);
                                }
                            }
                        } elseif ($result['type'] === 'bundles') {
                            //don't show the bundle results in search when user is (logged in)
                            if ( !is_user_logged_in() ) {
                                $order_op3 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                                $order_op3 .= $result['url'];
                                $order_op3 .= '" title="Click or press Enter to open selected">';
                                if ($tile_based === 'cudoo_search_tile') {
                                    $order_op3 .= $result['image'];
                                }
                                $order_op3 .= '<span class="search_title">' . $result['title'] . '</span>';


                                if ($tile_based === 'no_tile') {
                                    $order_op3 .= '</a>';
                                    $order_op3 .= '<span class="_align_r">';
                                    if (in_array("administrator", $user_roles)) {
                                        $order_op3 .= $result['type'];
                                    }
                                    $order_op3 .= '</span><br>';
                                }
                                //add splitter ET_SPLIT_ET
                                if ($tile_based === 'cudoo_search_tile') {
                                    $order_op3 .= '<div class="search_post_desc">' . $result["desc"] . '</div></a>';
                                    $order_op3 .= '<span class="view_related_btn" title="View All Bundled Products" href="' . $result["url"] . '">View Related</span>';
                                    $order_op3 .= $splitter;
                                }
                                $one_to_ten++;
                            }
                            //remove this element from array now
                            unset($search_results[$key]);
                        } elseif ($result['type'] === 'product') {

                            $order_op4 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                            $order_op4 .= $result['url'];
                            $order_op4 .= '" title="Click or press Enter to open selected">';
                            if ($tile_based==='cudoo_search_tile') {
                                $order_op4 .= $result['image'];
                            }
                            $order_op4 .= '<span class="search_title">' . $result['title'] . '</span>';


                            //add splitter ET_SPLIT_ET

                            if ($tile_based==='no_tile') {
                                $order_op4 .= '</a>';
                                $order_op4 .= '<span class="_align_r">';
                                if(in_array("administrator", $user_roles)){
                                    $order_op4 .= $result['type'];
                                }
                                $order_op4 .= '</span><br>';
                            }
                            if ($tile_based==='cudoo_search_tile') {
                                $order_op4 .= '<div class="search_post_desc">' . $result["desc"] . '</div></a>';
                                $order_op4 .= $splitter;
                            }
                            $one_to_ten++;
                            //remove this element from array now
                            unset($search_results[$key]);
                        } elseif ($result['type'] === 'course') {
                            $order_op5 .= '<a id="'.$one_to_ten.'" class="' . $tile_based . '" target="_self" href="';
                            $order_op5 .= $result['url'];
                            $order_op5 .= '" title="Click or press Enter to open selected">';
                            if ($tile_based==='cudoo_search_tile') {
                                $order_op5 .= $result['image'];
                            }
                            $order_op5 .= '<span class="search_title">' . $result['title'] . '</span>';


                            if ($tile_based==='no_tile') {
                                $order_op5 .= '</a>';
                                $order_op5 .= '<span class="_align_r">';
                                if(in_array("administrator", $user_roles)){
                                    $order_op5 .= $result['type'];
                                }
                                $order_op5 .= '</span><br>';
                            }
                            //add splitter ET_SPLIT_ET
                            if ($tile_based==='cudoo_search_tile') {
                                $order_op5 .= '<div class="search_post_desc">' . $result["desc"] . '</div></a>';
                                $order_op5 .= $splitter;
                            }
                            $one_to_ten++;
                            //remove this element from array now
                            unset($search_results[$key]);
                        }

                        if ($got < $limit) {
                            $got++;
                        } else {

                            //exit foreach
                            break;
                        }
                    }
                }

            }

        }
    }

    //record stats are move to new file called 'record_stats.php'

    //print all outputs
    echo $order_op1;
    echo $order_op2;
    echo $order_op3;
    echo $order_op4;
    echo $order_op5;
    exit();
}
