<?php
/**
 * Created by PhpStorm.
 * User: ali usman
 * Date: 1/26/2017
 * Time: 3:11 PM
 */
/***** Forward Stats - Record when user stops typing ******/
require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
$user_id= get_current_user_id();

//get user IP
$IP = $_SERVER['REMOTE_ADDR'];

//get current date time
$now = new DateTime();

//{typed} user query
$typed = htmlspecialchars($_POST['searched_text']);

echo 'Typed:'.$typed;
//if user logged in AND query is not EMPTY
if( $user_id!==0 AND $typed !== ""){
    //if user logged in
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

        // get the id of the query => "pakistan"
        // delete all the typed queries => "pakistani"
        // eg: "pakistani" and then update the "pakistan" to "pakistani" as distinct
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM search_stats_recorded WHERE query = %s AND ID <> %d",
                $typed, $existed_qry->ID
            )
        );

        echo 'id found:'. $existed_qry->ID;
        echo 'IP:'.$IP;
        //if deleted all
        if($deleted){
            //insert it as new
            echo 'deleted copies.';
        }

        $updated = $wpdb->update(
            'search_stats_recorded',
            array(
                'query' => $typed,
                'address'   => $IP,
                'timestamp' => $now->format('Y-m-d H:i:s'),	// string
            ),
            array( 'ID' => $existed_qry->ID ),
            array(
                '%s',	// qry
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
        //now try finding the same
        $existed_qry = $wpdb->get_row( "SELECT * FROM search_stats_recorded WHERE query ='$typed' AND user_id=$user_id" );
        echo 'id found:'. $existed_qry->ID;
        echo 'IP:'.$IP;
        if($existed_qry !== NULL) {
            //was found
            print "Same Query was found.";
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
            if ($updated) {
                echo 'UPDATED {same}.' . $updated;
            } else {
                echo 'Coulnt update {same}.' . $updated;
            }
        }else{
            print "Substr Query was NOT found.inserting new.";

            $inserted =  $wpdb->insert(
                'search_stats_recorded',
                array(
                    'user_id' => $user_id,
                    'query'   => trim($_POST['searched_text']),
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
            if($inserted){
                echo 'Stats Recorded';
            }else{
                echo 'Cant record stats';
            }
        }

    }
}else{

}
exit();
?>