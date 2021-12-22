

// return results for atleast two chars eg: 'en'
var min_keyword_len = 1;


function doneTyping (searchtext,header_search) {
    console.log("user finished tpying, record the query.");

    jQuery.ajax({
        type: "POST",
        url: 'https://sandbox.cudoo.com/wp-content/plugins/cudoo-search/record_stats.php',
        data: 'searched_text=' + searchtext + '&tile_based=false',
        success: function (response) {
            if (response === "") {
                console.log("failure"+response);
            }else{
                console.log("success"+response);
            }
        }

    });
}

function cxSearch(){
    //auto focus
    jQuery('#header_search_input').focus();
    if(jQuery('.header_search #cudoo_search_form').css('height')=='0px'){
        jQuery('#header_search_icon').css('color','#F05A2B');
        jQuery('.header_search').css('display','block');
        jQuery('#cudoo_search_wrap').css('opacity','1');
        jQuery('.header_search #cudoo_search_form').css('height','40px');
        jQuery('#header_search_arrow').css('opacity','1');
    }else{
        jQuery('#header_search_icon').css('color','#9d9d9d');
        jQuery('.header_search').css('display','none');
        jQuery('.header_search #cudoo_search_form').css('height','0px');
        jQuery('#header_search_arrow').css('opacity','0');
        jQuery('#cudoo_search_wrap').css('opacity','0');
    }
}

// show hide the search results wrapper when mouse is clicked outside the search input
jQuery(document).mouseup(function (e)
{
    var container = jQuery("#cudoo_search_results_wrap");
    var search_input = jQuery("#cudoo_search_input");
    if ( !container.is(e.target)// if the target of the click isn't the container...
        && container.has(e.target).length === 0)
    {
        if((!search_input.is(e.target)// if the target of the click isn't the container...
            && search_input.has(e.target).length === 0) ){ // ... nor a descendant of the container){
            //container.hide();
            jQuery('#cudoo_search_results').css("display", "none");

        }
    }

    var container = jQuery("#header_search_results_wrap");
    var search_input = jQuery("#header_search_input");
    if ( !container.is(e.target)// if the target of the click isn't the container...
        && container.has(e.target).length === 0)
    {
        if((!search_input.is(e.target)// if the target of the click isn't the container...
            && search_input.has(e.target).length === 0) ){
            jQuery('#header_search_results').css("display", "none");
        }
    }
});


//setup before functions
window.typingTimer;                //timer identifier
window.doneTypingInterval = 1000;  //time in ms, 5 second for example
// New cudoo suggestions search
function find_suggested_results(id,plugins_uri,header_search,backspaced) {
    //get the search input whether header search OR normal search
    var search_input = document.getElementById(id);
    search_input.onkeydown = function() {
        var key = event.keyCode || event.charCode;
        console.log("key:"+event.keyCode);
        if (key == 13) {
            event.preventDefault();
            console.log("Enter <_|: "+event.id);
           var focused_link = jQuery( "#header_search_results_wrap #header_search_results a.focused" ).attr('href');
           if(focused_link == undefined){
               //click programmatically
               jQuery('#cudoo_search_btn').click();
           }else{
               window.location = focused_link;
               console.log("link:"+focused_link );
           }
        }

        if (key == 40) {
            console.log("Moving to Next");
            //check if it was first time
            if(jQuery("#header_search_results_wrap #header_search_results .no_tile").hasClass('focused') == false){
                console.log("focusing");
                //create new window var
                if(window.focused == undefined){
                    window.focused = 1;
                }

                console.log("first:" + window.focused);
                jQuery("#header_search_results .no_tile:first").addClass('focused');
            }else{//if not first time
                //class must be set
                //get the element which has class 'focused'
                console.log("focused");
                //reset the selection to first
                var total = (jQuery('#header_search_results a').length);
                console.log("total:"+total);

                //if focused MAX then take to first
                if(window.focused == total){
                    console.log("focused === total");
                    window.focused = 1;
                    jQuery("#header_search_results .no_tile").removeClass("focused");
                    jQuery("#header_search_results .no_tile:first").addClass('focused');

                }else{
                    window.focused = (window.focused + 1);
                    console.log("now:"+window.focused);
                    jQuery("#header_search_results .no_tile").removeClass("focused");
                    jQuery( "#header_search_results a#"+window.focused ).addClass('focused');
                }

            }
        }

        if (key == 38) {
            console.log("Moving to Prev");
            //check if it was first time
            if(jQuery("#header_search_results_wrap #header_search_results .no_tile").hasClass('focused') === false){
                var total = (jQuery('#header_search_results a').length);
                //create new window var
                if(window.focused == undefined){
                    window.focused = total;
                    console.log("total 1:"+total);
                    console.log("last prev:" + window.focused);
                    console.log("window.focused:"+ window.focused);
                    jQuery("#header_search_results a:last").addClass('focused');
                }

            }else{//if not first time
                //class must be set
                console.log("focused prev");
                //get the element which has class 'focused'
                var total = (jQuery('#header_search_results a').length);
                if(window.focused == undefined){
                    //if undefined means using moving back and going to last record at first
                    window.focused = total;
                    jQuery("#header_search_results .no_tile").removeClass("focused");
                    jQuery("#header_search_results .no_tile:last").addClass('focused');
                    console.log("window.focused:"+window.focused);
                    console.log("total 2:"+total);

                }else if(window.focused == '1'){
                    window.focused = total;
                    jQuery("#header_search_results .no_tile").removeClass("focused");
                    jQuery("#header_search_results .no_tile:last").addClass('focused');
                    console.log("window.focused:"+window.focused);
                    console.log("total 2 2:"+total);

                }else{
                    console.log("window.focused:"+window.focused);
                    console.log("total 2 3:"+total);

                    window.focused = (window.focused -1);
                    console.log("now prev:"+ window.focused);
                    jQuery("#header_search_results .no_tile").removeClass("focused");
                    jQuery( "#header_search_results a#"+window.focused ).addClass('focused');

                }

            }
        }

        if(key == 13 || key == 8 || key == 46 ||  key == 37 ||  key == 38  ||  key == 39 || key == 40 || key == 17 || key == 18 || key == 90){
            console.log("Dont Record");
        }else{
            //reset the window.focused if set
            window.focused=undefined;//unset var
            jQuery("#header_search_results .no_tile").removeClass("focused");

            window.focused

            if(header_search==='true'){
                var search_results = document.getElementById("header_search_results");
                var search_results_wrap = document.getElementById("header_search_results_wrap");
                var s_wrap = "header_search_results_wrap";
            }else{
                var search_results = document.getElementById("cudoo_search_results");
                var search_results_wrap = document.getElementById("cudoo_search_results_wrap");
                var s_wrap = "cudoo_search_results_wrap";
            }

            if (search_input.value.length > min_keyword_len) {
                if(id == 'header_search_input'){
                    jQuery('#header_search_loading').css('display', 'block');
                }else{
                    jQuery('#cudoo_search_loading').css('display', 'block');
                }

                clearTimeout(window.typingTimer);
                typingTimer = setTimeout(function() {
                    doneTyping(search_input.value,header_search)
                }, window.doneTypingInterval);

                jQuery.ajax({
                    type: "POST",
                    url: plugins_uri + '/cudoo-search/ajax_script.php',
                    data: 'searched_text=' + search_input.value + '&tile_based=false',
                    success: function (posts_data) {
                        if(id == 'header_search_input'){
                            jQuery('#header_search_loading').fadeOut("linear");
                        }else{
                            jQuery('#cudoo_search_loading').fadeOut("linear");
                        }
                        var suggestions_msg = "";
                        if (posts_data === "") {
                            suggestions_msg = "No Suggestions found";
                            search_results.style.display = 'block';
                            search_results.innerHTML = posts_data + suggestions_msg + '<hr id="ending_line">';
                        }else{
                            search_results.style.display = 'block';
                            search_results.innerHTML = posts_data + suggestions_msg + '<hr id="ending_line">';
                            jQuery('#'+s_wrap).css("border-bottom-color", "#fff");
                            jQuery('.cudoo_search_tile').css("height", "300px");
                            jQuery('#ending_line').animate({width: "100%"}, 700);
                        }
                    }
                });
            } else {
                //hide results
                search_results.style.display = 'none';
            }
        }
    };
}

// Quick Real Time Tile Search (...heard of the RTS, its time for RTTS)
function find_all_results(id,plugins_uri) {
    var search_input = document.getElementById(id);

    if (search_input.value.length > min_keyword_len) {
        //change border color css
        jQuery('#cudoo_search_input').css('box-shadow','0px 2px 0px orangered');

        var loading = document.getElementById("cudoo_search_loading");
        jQuery('#cudoo_search_loading').css('display', 'block');
        jQuery('#cudoo_search_results').css('padding-top', '10px');

        jQuery.ajax({
            type: "POST",
            url: plugins_uri + '/cudoo-search/ajax_script.php',
            data: 'searched_text=' + search_input.value + '&tile_based=true',
            success: function (posts_data) {
                jQuery('#cudoo_search_loading').fadeOut("linear");
                var suggestions_msg = "";
                if (posts_data === "") {
                    suggestions_msg = "No Suggestions found";
                    jQuery("#label_page_info").html("0 Record Found");
                    jQuery("#label_total_results").html("Total: 0");
                    jQuery("#label_page_info").css("color","#fff");
                    jQuery("#label_total_results").css("color","#fff");
                    jQuery("#search_navigation").css("color","#fff");
                    jQuery(".fa-arrow-left").css("color","#fff");
                    jQuery(".fa-arrow-right").css("color","#fff");
                }else{
                    jQuery("#label_page_info").css("color","#333");
                    jQuery("#label_total_results").css("color","#333");
                    jQuery("#search_navigation").css("color","#333");
                    jQuery(".fa-arrow-left").css("color","#333");
                    jQuery(".fa-arrow-right").css("color","#333");
                }

                var a = posts_data.split("ET_SPLIT_ET"), i;
                var per_page = 12;

                // console.log("all data:" + posts_data);
                window.tiles = '';
                var first_render='';
                var total = 0;
                for (i = 0; i < (a.length - 1); i++) {
                    if (typeof a[i] !== 'undefined') {
                        window.tiles = window.tiles + '<div class="wpb_column vc_column_container vc_col-sm-3"><div class="vc_column-inner "><div class="wpb_wrapper">' + a[i] + '</div></div></div>';
                        //console.log(", printing:" + i + ' => ' + a[i]);
                        total++;
                        if (i < per_page) {
                            first_render = first_render + '<div class="wpb_column vc_column_container vc_col-sm-3"><div class="vc_column-inner "><div class="wpb_wrapper">' + a[i] + '</div></div></div>';
                        }
                    }
                }
                if(first_render){
                    first_render = first_render.replace("undefined", "");
                }
                if(window.tiles){
                    window.tiles = window.tiles.replace("undefined", "");
                }
                window.lastpage = Math.ceil(total / per_page);
                window.current = 1;
                //console.log("last page:" + lastpage);
                //render navigation
                jQuery('#search_navigation').html('<a onclick="goBack();"  id="nav_prev" href="#" title="show previous"><i class="fa fa-chevron-left" aria-hidden="true"></i>&nbsp;PREV</a>&nbsp;<b style="height: 24px;display: inline-block;vertical-align: baseline;font-size: 24px;text-transform: uppercase;color: #dcdcdc;">&nbsp;&nbsp; / &nbsp;&nbsp;</b><a id="nav_next" onclick="goForward();" href="#" title="show next">NEXT&nbsp;<i class="fa fa-chevron-right" aria-hidden="true"></i>');
                //disable prev arrow on first page
                jQuery('#nav_prev').css("color", "#dcdcdc");
                // disable:
                document.getElementById('nav_prev').style.pointerEvents = 'none';
                console.log("total:"+total);
                // if more than 1 page then show Next arrow
                if (lastpage === 1) {
                    console.log("last page is:" + lastpage);
                    //disable prev arrow on first page
                    document.getElementById('nav_next').style.pointerEvents = 'none';
                    jQuery('#nav_next').css("color", "#dcdcdc");
                }
                //update total results message
                jQuery('#label_page_info').html('Showing ' + window.current + ' of ' + window.lastpage);
                jQuery('#label_total_results').html('Total: ' + total);
                window.total = total;
                jQuery('.tile_based_results_wrap').css('display', 'block');
                jQuery('.tile_based_results_wrap').html(first_render + suggestions_msg + '<hr id="ending_line">');
                jQuery('.cudoo_search_tile').css("opacity", "1");
                //move the ball
                jQuery('#search_ball').css("left", "98%");
                jQuery('#search_ball').css("opacity", "0");
                jQuery('#ending_line').animate({width :"100%"},700);
            }
        });
    } else {
        //change border color css
        jQuery('#cudoo_search_input').css('box-shadow','0px 2px 0px black');
        //hide/disable navigation
        jQuery('#nav_next').css("color", "silver");
        document.getElementById('nav_next').style.pointerEvents = 'none';
        jQuery('#nav_prev').css("color", "silver");
        document.getElementById('nav_prev').style.pointerEvents = 'none';
        //hide page info
        jQuery('#' +
            '').html('Page 0 of 0');
        //update total results message
        jQuery('#label_total_results').html('Total: 0');
        //hide results
        jQuery('.tile_based_results_wrap').css('display', 'none');
    }


    // //change border color css
    // jQuery('#cudoo_search_input').css('border-color','black');
}

function goBack() {
    if ((window.current - 1) < 1) {
        // console.log("Do nothing : current=>" + window.current);
    } else {
        //  console.log("take me to the prev page: current=>" + window.current);
        window.current = (window.current - 1);
        //after decrement
        if (window.current == 1) {
            document.getElementById('nav_prev').style.pointerEvents = 'none';
            jQuery('#nav_prev').css("color", "#dcdcdc");

            //show first page records only
            var a = window.tiles.split("</a>"), i;
            var per_page = 12;
            var tiles = '';
            //show ten records only
            for (i = 1; i < per_page; i++) {
                if (typeof a[i] !== 'undefined') {
                    tiles = tiles + a[i] + '</a>';//adding removed </a> tag for split
                    jQuery( a[i] + '</a>' ).appendTo('.tile_based_results_wrap').show('slow');
                    // console.log(", displaying:" + i + ' => ' + a[i]);
                }
            }
            tiles = tiles.replace("undefined", "");
            jQuery('.tile_based_results_wrap').html(tiles);
        }

        //show current page records
        //show first page records only
        var a = window.tiles.split("</a>"), i;
        var per_page = 12;
        var tiles = '';

        //show ten records only
        //window.current+0 = 20
        if (window.current == 1) {
            var initial_tile = 0;
        } else {
            var initial_tile = (window.current-1)*per_page; // 20 instead of 2
        }

        // console.log("init tile:" + initial_tile);
        console.log("initial tile addition:" + parseInt(initial_tile) + per_page);
        for (i = (initial_tile); i < (parseInt(initial_tile) + per_page); i++) {
            if (typeof a[i] !== 'undefined') {
                tiles = tiles + a[i] + '</a>';//adding removed </a> tag for split
                //console.log(": Displaying:" + i + ' => ' + a[i]);
            }
        }
        tiles = tiles.replace("undefined", "");
        jQuery('.tile_based_results_wrap').html(tiles);

        //show prev arrow
        jQuery('#nav_next').css("color", "black");
        // enable:
        document.getElementById('nav_next').style.pointerEvents = 'auto';

    }
    // console.log("last page:" + window.lastpage + " current =" + window.current);

    //update nav info
    jQuery('#label_page_info').html('Showing ' + window.current + ' of ' + window.lastpage);
}

function goForward() {
    if ((window.current + 1) > window.lastpage) {
         //console.log("Do nothing : current =>" + window.current);
    } else {
        //console.log("take me to the next page: current=>" + window.current);
        window.current = (window.current + 1);

        //after increment
        if (window.current == window.lastpage) {
            //console.log("last Page results");
            document.getElementById('nav_next').style.pointerEvents = 'none';
            jQuery('#nav_next').css("color", "#dcdcdc");

            //show last page records only
            var a = window.tiles.split("</a>"), i;
            var per_page = 12;
            var tiles = '';
            //show ten records only
            for (i = parseInt(window.total - per_page); i < (window.total); i++) {
                if (typeof a[i] !== 'undefined') {
                    tiles = tiles + a[i] + '</a>';//adding removed </a> tag for split
                    //console.log("::::displaying from:"+ i );
                   // console.log(", displaying:" + i + ' => ' + a[i]);
                }
            }
            tiles = tiles.replace("undefined", "");
            jQuery('.tile_based_results_wrap').html(tiles);
        }

        //show current page records
        //show first page records only
        var a = window.tiles.split("</a>"), i;
        var per_page = 12;
        var tiles = '';
        //show ten records only
        //window.current+0 = 20
         //console.log("init page num:" + window.current);
         //console.log("init result index:" + (window.current*per_page));
        for (i = (window.current*per_page - per_page); i < (window.current*per_page); i++) {
            if (typeof a[i] !== 'undefined') {
                tiles = tiles + a[i] + '</a>';//adding removed </a> tag for split
                //console.log("> goNext display:");
               // console.log(": Displaying:" + i + ' => ' + a[i]);
            }
        }

        //console full tiles

        jQuery('.tile_based_results_wrap').html(tiles);
        //show prev arrow
        jQuery('#nav_prev').css("color", "black");
        // enable:
        document.getElementById('nav_prev').style.pointerEvents = 'auto';
    }
    // console.log("past page:" + window.lastpage + " current =" + window.current);
    //update nav info
    jQuery('#label_page_info').html('Showing ' + window.current + ' of ' + window.lastpage);
}