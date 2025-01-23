<?php
/**
 * Class BbpressCourseForumElementor
 *
 * @sicne 4.0.6
 * @version 1.0.0
 */
namespace LP_Addon_bbPress\Elementor\Widgets;

use LearnPress\Models\CourseModel;
use LearnPress\ExternalPlugin\Elementor\LPElementorWidgetBase;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\SingleCourseBaseElementor;
use LP_Addon_bbPress;

class BbpressCourseForumElementor extends LPElementorWidgetBase {
    use SingleCourseBaseElementor;

	public function __construct( $data = [], $args = null ) {
		$this->title    = esc_html__( 'Bbpress Course Forum', 'learnpress-bbpress' );
		$this->name     = 'bbpress_course_forum';
		$this->keywords = [ 'bbpress forum', 'forum' ];
		$this->add_style_depends( 'learnpress-bbpress-forum' );
		parent::__construct( $data, $args );
	}

	protected function register_controls() {
		$this->controls = require LP_ADDON_BBPRESS_PATH . '/inc/Elementor/config/singlecourseforum.php';
		parent::register_controls();
	}

	/**
	 * Show content of widget
	 *
	 * @return void
	 */
	protected function render() {
		try {
			$courseModel = CourseModel::find( get_the_ID(), true );
			if ( ! $courseModel ) {
				return;
			}

			$forum_id = $courseModel->get_meta_value_by_key( '_lp_course_forum' );
			if ( ! $forum_id ) {
				return;
			}

			if ( ! in_array( get_post_type( $forum_id ), array( 'topic', 'forum' ) ) ) {
				return;
			}

			if ( ! LP_Addon_bbPress::instance()->can_access_forum( $forum_id, get_post_type( $forum_id ) ) ) {
				return;
			}

			if ( $courseModel->get_meta_value_by_key( '_lp_bbpress_forum_enable' ) !== 'yes' ) {
				return;
			}

			$forum = get_post( $forum_id );
			if ( empty( $forum ) || get_post_status( $forum_id ) !== 'publish' ) {
				return;
			}

			echo \LPbbPressTemplate::instance()->render_forum_html( (int) $forum_id );

		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
