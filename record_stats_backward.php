<?php
/**
 * Created by PhpStorm.
 * User: ali usman
 * Date: 1/26/2017
 * Time: 3:11 PM
 */
/***** Backward Stats - Record when user pressed the "Backspace" ******/
require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
$user_id= get_current_user_id();

//get user IP
$IP = $_SERVER['REMOTE_ADDR'];

//get current date time
$now = new DateTime();

//{typed} user query
$searched_text = htmlspecialchars($_POST['searched_text']);

echo 'backspaced:'.$searched_text;
//if user logged in
if( $user_id!==0 AND $searched_text !== ""){
    //delete all "same" existing searches by this user
    print "finding:$searched_text";

    //find substring of {typed} query
    $existed_qry = $wpdb->get_row( "SELECT * FROM search_stats_recorded WHERE query ='$searched_text' AND user_id=$user_id" );
    echo 'id found:'. $existed_qry->ID;
    echo 'IP:'.$IP;
    if($existed_qry !== NULL){
        //delete the duplicates
        //delete all "same" existing searches by this user
        print "deleting:$searched_text";
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM search_stats_recorded WHERE query = %s AND ID <> %d",
                $searched_text, $existed_qry->ID
            )
        );

        echo 'id found:'. $existed_qry->ID;
        echo 'IP:'.$IP;
        //if deleted all
        if($deleted){
            //insert it as new
            echo 'deleted copies.';
        }

        print "Updating the found query now.";
        $updated = $wpdb->update(
            'search_stats_recorded',
            array(
                'address'   => $IP,
                'timestamp' => $now->format('Y-m-d H:i:s'),	// string
            ),
            array( 'ID' => $existed_qry->ID ),
            array(
                '%s',	// IP
                '%s',   // time
            ),
            array( '%d' )
        );
        if($updated){
            echo 'UPDATED.'.$updated;
        }else{
            echo 'Couldnt update.'.$updated;
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
                'address'   => $IP,
                'timestamp' => $now->format('Y-m-d H:i:s'),
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );
        if($recorded){
            echo 'Stats Recorded';
        }else{
            echo 'Cant record stats';
        }
    }
}
exit();
?>