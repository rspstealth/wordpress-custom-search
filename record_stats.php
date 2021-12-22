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

//get user IP
$IP = $_SERVER['REMOTE_ADDR'];

//get current date time
$now = new DateTime();

//{typed} user query
$typed = htmlspecialchars($_POST['searched_text']);

echo 'Typed:'.$typed;
//if user logged in
if( $user_id!==0 ){
    //if user not logged in
    //get substring of the {typed} query and then find the query in the database
    //- if it exists then get its {ID} and replace the {typed} query with the substring query
    // if substring NOT found
    //- find the {exact} query
    //- if {exact} query was found, replace {exact} query with its {id} by {typed} query

    //substring of the {typed} query
    $substr_qry = substr($typed,0,-1);
    print "finding:$substr_qry";
    //find substring of {typed} query
    $existed_qry = $wpdb->get_row( "SELECT * FROM search_stats_recorded WHERE query ='$substr_qry' AND user_id=$user_id" );
    echo 'id found:'. $existed_qry->ID;
    echo 'IP:'.$IP;
    if($existed_qry !== NULL){
        //was found
        print "Substr Query was found.";
        $updated = $wpdb->update(
            'search_stats_recorded',
            array(
                'query' => $typed,	// string
            ),
            array( 'ID' => $existed_qry->ID ),
            array(
                '%s',	// value1
            ),
            array( '%d' )
        );
        if($updated){
            echo 'UPDATED.'.$updated;
        }else{
            echo 'Coulnt update.'.$updated;
        }
    }else{
        //wasn't found
        print "Substr Query was NOT found.inserting new.";

        $recorded=  $wpdb->insert(
            'search_stats_recorded',
            array(
                'user_id' => $user_id,
                'query' => trim($_POST['searched_text']),
                'response' => ($got+1),
                'timestamp' => $now->format('Y-m-d H:i:s'),
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
            )
        );
        if($recorded){
            //echo 'Stats Recorded';
        }else{
            //echo 'Cant record stats';
        }
    }
}else{

}
exit();
?>