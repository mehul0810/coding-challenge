<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		global $post;

		$post_types = get_post_types(  [ 'public' => true ] );
		$class_name = $attributes['className'];
		ob_start();
		?>
        <div class="<?php echo esc_attr( $class_name ); ?>">
			<h2><?php esc_html_e( 'Post Counts', 'site-counts' ); ?></h2>
			<ul>
				<?php
				foreach ( $post_types as $post_type_slug ) :
					$post_type_object = get_post_type_object( $post_type_slug  );
					$post_count       = wp_count_posts( $post_type_slug )->publish;
					?>
					<li>
						<?php
						echo sprintf(
							'%1$s %2$d %3$s',
							esc_html__( 'There are', 'site-counts' ),
							absint( $post_count ),
							esc_html( $post_type_object->labels->name )
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p>
				<?php
				echo sprintf(
					'%1$s %2$d.',
					esc_html__( 'The current post ID is', 'site-counts' ),
					absint( $post->ID )
				);
				?>
			</p>
			<?php
			$posts_to_exclude = [ get_the_ID() ];
			$site_count_query = new WP_Query(
				array(
					'post_type'     => [ 'post', 'page' ],
					'post_status'   => 'any',
					'date_query'    => array(
						array(
							'hour'    => 9,
							'compare' => '>=',
						),
						array(
							'hour'    => 17,
							'compare' => '<=',
						),
					),
					'no_found_rows' => true,
				)
			);

			if ( $site_count_query->have_posts() ) :
				?>
				<h2><?php esc_html_e( 'Any 5 posts with the tag of foo and the category of baz', 'site-counts' ); ?></h2>
				<ul>
				<?php
				foreach ( array_slice( $site_count_query->posts, 0, 5 ) as $post_item ) :
					// Filter out the posts to exclude.
					if ( in_array( $post_item->ID, $posts_to_exclude ) ) {
						continue;
					}

					// Get categories and tags of this post and prepare it.
					$tags_array       = get_the_tags( $post_item->ID );
					$categories_array = get_the_category( $post_item->ID );
					$tags             = is_array( $tags_array ) ? wp_list_pluck( $tags_array, 'slug' ) : [];
					$categories       = is_array( $categories_array ) ? wp_list_pluck( $categories_array, 'slug' ) : [];

					// Filter out the posts not having category name `baz` or tag name `foo`.
					if (
						! in_array( 'baz', $categories, true ) ||
						! in_array( 'foo', $tags, true )
					) {
						continue;
					}
					?>
					<li><?php echo esc_html( $post_item->post_title ); ?></li>
					<?php
				endforeach;
			endif;
		 	?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}
}
