<?php
/**
 * Plugin Name: KO – News Announcements (Filters + Listings)
 * Description: Production build. AJAX Search + Year filter with Load More for custom News Listings.
 * Version: 1.2.0 (PRODUCTION)
 * Author: KO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class KO_News_Listings_Production {

	const SHORTCODE_FILTERS  = 'ko_post_filters';
	const SHORTCODE_LISTINGS = 'ko_post_listings';

	const AJAX_ACTION = 'ko_news_listings';

	public static function init() {

		add_shortcode( self::SHORTCODE_FILTERS, array( __CLASS__, 'filters_shortcode' ) );
		add_shortcode( self::SHORTCODE_LISTINGS, array( __CLASS__, 'listings_shortcode' ) );

		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handler' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_handler' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function enqueue_assets() {
		if ( is_admin() ) return;

		wp_enqueue_script(
			'ko-news-listings-js',
			plugins_url( 'assets/ko-news-listings.js', __FILE__ ),
			array(),
			'1.2.0',
			true
		);

		wp_localize_script(
			'ko-news-listings-js',
			'KO_NEWS_LISTINGS',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::AJAX_ACTION,
				'nonce'   => wp_create_nonce( 'ko_news_nonce' )
			)
		);

		wp_enqueue_style(
			'ko-news-listings-css',
			plugins_url( 'assets/ko-news-listings.css', __FILE__ ),
			array(),
			'1.2.0'
		);
	}

	public static function filters_shortcode() {

		ob_start(); ?>
		<div class="ko-post-filters" data-ko-news-filters="1">
			<form class="ko-post-filters__form">
				<div class="ko-post-filters__field">
					<label>Search</label>
					<input type="search" name="ko_s">
				</div>
				<div class="ko-post-filters__divider"></div>
				<div class="ko-post-filters__field">
					<label>Archive</label>
					<select name="ko_year">
						<option value="">All Years</option>
						<?php
						global $wpdb;
						$years = $wpdb->get_col("
							SELECT DISTINCT YEAR(post_date) FROM {$wpdb->posts}
							WHERE post_type='post' AND post_status='publish'
							ORDER BY post_date DESC
						");
						foreach($years as $year){
							echo '<option value="'.esc_attr($year).'">'.esc_html($year).'</option>';
						}
						?>
					</select>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function listings_shortcode($atts){

		$atts = shortcode_atts(array(
			'posts_per_page' => 9
		), $atts);

		$q = new WP_Query(array(
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => intval($atts['posts_per_page'])
		));

		ob_start(); ?>
		<div class="ko-post-listings" data-ko-news-listings="1" data-ppp="<?php echo intval($atts['posts_per_page']); ?>">
			<?php self::render_posts($q); ?>
			<div class="ko-load-more-wrap">
				<button class="ko-load-more">Load More</button>
			</div>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}

	private static function render_posts($q){
		if($q->have_posts()):
			while($q->have_posts()): $q->the_post(); ?>
				<article class="et_pb_post ko-news-item">
					<?php if(has_post_thumbnail()): ?>
						<a href="<?php the_permalink(); ?>" class="entry-featured-image-url">
							<?php the_post_thumbnail('large'); ?>
						</a>
					<?php endif; ?>
					<h3 class="entry-title"><?php the_title(); ?></h3>
					<p class="post-meta"><?php echo get_the_date('M j, Y'); ?></p>
					<div class="post-content"><?php the_excerpt(); ?></div>
				</article>
			<?php endwhile;
		else:
			echo '<p>No Results Found</p>';
		endif;
	}

	public static function ajax_handler(){

		check_ajax_referer('ko_news_nonce','nonce');

		$paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
		$search = sanitize_text_field($_POST['ko_s'] ?? '');
		$year = intval($_POST['ko_year'] ?? '');
		$ppp = intval($_POST['ppp'] ?? 9);

		$args = array(
			'post_type'=>'post',
			'post_status'=>'publish',
			'paged'=>$paged,
			'posts_per_page'=>$ppp
		);

		if($search) $args['s']=$search;
		if($year) $args['year']=$year;

		$q = new WP_Query($args);

		ob_start();
		self::render_posts($q);
		wp_reset_postdata();

		wp_send_json_success(array(
			'html'=>ob_get_clean(),
			'has_more'=>$q->max_num_pages > $paged
		));
	}

}

KO_News_Listings_Production::init();
