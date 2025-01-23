<?php
/**
 * Class BbpressElementorHandler
 *
 * Hook to register widgets, dynamic tags, ... for LearnPress Elementor handler.
 *
 * @since 4.0.6
 * @version 1.0.0
 */

namespace LP_Addon_bbPress\Elementor;

use LearnPress\Helpers\Singleton;
use LP_Addon_bbPress\Elementor\Widgets\BbpressCourseForumElementor;

class BbpressElementorHandler {
	use Singleton;

	/**
	 * Hooks to register widgets, dynamic tags, ...
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'lp/elementor/widgets', [ $this, 'register_widgets' ] );
	}

	/**
	 * @param $lp_widgets array
	 * @return mixed
	 */
	public function register_widgets( array $lp_widgets ): array {
		include_once LP_ADDON_BBPRESS_PATH . '/inc/Elementor/Widgets/BbpressSingleCourseForumElementor.php';

		$lp_widgets['bbpress-course-forum'] = BbpressCourseForumElementor::class;

		return $lp_widgets;
	}
}
