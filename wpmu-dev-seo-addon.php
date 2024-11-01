<?php
/*
Plugin Name: WPMU Infinite SEO Addon
Version: 0.2
Plugin URI: http://wordpress.org/extend/plugins/wpmu-dev-seo-addon/
Description: An addon on WPMU Infinite Seo, Fixes bugs with creating Meta-Description, and makes it working with multibyte.
Author: Alex (Shurf) Frenkel
Author URI: http://alex.frenkel-online.com/
*/


$objClass = new sirshurf_wpmu_dev_seo();
add_action('init', array($objClass, 'add_hooks'));



class sirshurf_wpmu_dev_seo {

	public $data;
	public $model;

	function __construct () {
	}

	function seo($strText){
	                $strText = strip_shortcodes( $strText );
			$strText = strip_tags ( $strText );
	                $strText = apply_filters('the_content', $strText);
	                $strText = str_replace(']]>', ']]&gt;', $strText);
	                $excerpt_length = apply_filters('excerpt_length', 55);
	                $excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
			if (function_exists('wp_trim_words')){
		                $strText = wp_trim_words( $strText, $excerpt_length, $excerpt_more );
			} else {
		                $strText = $this->wp_trim_words( $strText, $excerpt_length, $excerpt_more );
			}

		return $strText;
	}

	function wp_trim_words( $text, $num_words = 55, $more = null ) {

		if ( null === $more )

			$more = __( '&hellip;' );

		$original_text = $text;

		$text = wp_strip_all_tags( $text );

		$words_array = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );

		if ( count( $words_array ) > $num_words ) {

			array_pop( $words_array );

			$text = implode( ' ', $words_array );

			$text = $text . $more;

		} else {

			$text = implode( ' ', $words_array );

		}

		return apply_filters( 'wp_trim_words', $text, $num_words, $more, $original_text );

	}


	function add_hooks(){
		add_filter( 'wp_trim_excerpt', array($this,'seo'));
		add_action( 'admin_head', array($this,'change_meta_boxen' ));

	}

	function change_meta_boxen() {	
		// Remove old metaboxes
		foreach (get_post_types() as $posttype) {
			remove_meta_box( 'wds-wds-meta-box', $posttype, 'normal' );
		}

		// Add them back again
		$show = user_can_see_seo_metabox();
		if ( function_exists('add_meta_box') ) {
			$metabox_title = is_multisite() ? __( 'Infinite SEO' , 'wds') : 'Infinite SEO'; // Show branding for singular installs.
			foreach (get_post_types() as $posttype) {
				if ($show) add_meta_box( 'wds-wds-meta-box', $metabox_title, array(&$this, 'wds_meta_boxes'), $posttype, 'normal', 'high' );
			}
		}
	}


	function wds_meta_boxes() {
		global $post;

		echo '<script type="text/javascript">var lang = "'.substr(get_locale(),0,2).'";</script>';

		$date = '';
		if ($post->post_type == 'post') {
			if ( isset($post->post_date) )
				$date = date('M j, Y', strtotime($post->post_date));
			else
				$date = date('M j, Y');
		}

		echo '<table class="widefat">';

		$title = wds_get_value('title');
		if (empty($title))
			$title = $post->post_title;
		if (empty($title))
			$title = "temp title";


			$wds_options = get_wds_options();
		$desc = wds_get_value('metadesc');
		if (empty($desc))
			$desc = esc_attr( strip_tags( stripslashes( wds_replace_vars($wds_options['metadesc-'.$post->post_type], (array) $post ) )));


		if (empty($desc))
			$desc = 'temp description';

		$slug = $post->post_name;
		if (empty($slug))
			$slug = sanitize_title($title);

?>
	<tr>
		<th><label>Preview:</label></th>
		<td>
<?php
		$video = wds_get_value('video_meta',$post->ID);
		if ( $video && $video != 'none' ) {
?>
			<div id="snippet" class="video">
				<h4 style="margin:0;font-weight:normal;"><a class="title" href="#"><?php echo $title; ?></a></h4>
				<div style="margin:5px 10px 10px 0;width:82px;height:62px;float:left;">
					<img style="border: 1px solid blue;padding: 1px;width:80px;height:60px;" src="<?php echo $video['thumbnail_loc']; ?>"/>
					<div style="margin-top:-23px;margin-right:4px;text-align:right"><img src="http://www.google.com/images/icons/sectionized_ui/play_c.gif" alt="" border="0" height="20" style="-moz-opacity:.88;filter:alpha(opacity=88);opacity:.88" width="20"></div>
				</div>
				<div style="float:left;width:440px;">
					<p style="color:#767676;font-size:13px;line-height:15px;"><?php echo number_format($video['duration']/60); ?> mins - <?php echo $date; ?></p>
					<p style="color:#000;font-size:13px;line-height:15px;" class="desc"><span><?php echo $desc; ?></span></p>
					<a href="#" class="url"><?php echo str_replace('http://','',get_bloginfo('url')).'/'.$slug.'/'; ?></a> - <a href="#" class="util">More videos &raquo;</a>
				</div>
			</div>

<?php
		} else {
			if (!empty($date))
				$date .= ' ... ';
?>
			<div id="snippet">
				<p><a style="color:#2200C1;font-weight:medium;font-size:16px;text-decoration:underline;" href="#"><?php echo $title; ?></a></p>
				<p style="font-size: 12px; color: #000; line-height: 15px;"><?php echo $date; ?><span><?php echo $desc ?></span></p>
				<p><a href="#" style="font-size: 13px; color: #282; line-height: 15px;" class="url"><?php echo str_replace('http://','',get_bloginfo('url')).'/'.$slug.'/'; ?></a> - <a href="#" class="util">Cached</a> - <a href="#" class="util">Similar</a></p>
			</div>
<?php } ?>
		</td>
	</tr>
<?php
$objWpmu = new WDS_Metabox();
		echo $objWpmu->show_title_row();
		echo $objWpmu->show_metadesc_row();
		echo $objWpmu->show_keywords_row();
		echo $objWpmu->show_robots_row();
		echo $objWpmu->show_canonical_row();
		echo $objWpmu->show_redirect_row();
		echo $objWpmu->show_sitemap_row();
		echo '</table>';
	}



}


