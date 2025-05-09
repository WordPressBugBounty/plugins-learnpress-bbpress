<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/bbPress/Classes
 * @version  3.0.5
 */

defined( 'ABSPATH' ) || exit;

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LP_Addon_bbPress\Elementor\BbpressElementorHandler;

if ( ! class_exists( 'LP_Addon_bbPress' ) ) {
	/**
	 * Class LP_Addon_bbPress.
	 *
	 * @since 3.0.0
	 */
	class LP_Addon_bbPress extends LP_Addon {
		public $text_domain = 'learnpress-bbpress';

		public $version = LP_ADDON_BBPRESS_VER;

		public $require_version = LP_ADDON_BBPRESS_REQUIRE_VER;

		public static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * LP_Addon_bbPress constructor.
		 */
		public function __construct() {
			parent::__construct();

			$this->hooks();
		}

		/**
		 * Define constants.
		 */
		protected function _define_constants() {
			define( 'LP_ADDON_BBPRESS_PATH', dirname( LP_ADDON_BBPRESS_FILE ) );
			define( 'LP_ADDON_BBPRESS_TEMPLATE', LP_ADDON_BBPRESS_PATH . '/templates/' );
			define( 'LP_ADDON_BBPRESS_URL', plugin_dir_url( LP_ADDON_BBPRESS_FILE ) );
		}

		/**
		 * Includes files.
		 */
		protected function _includes() {
			include_once 'functions.php';
			include_once 'LPbbPressTemplate.php';

			if ( is_plugin_active( 'elementor/elementor.php' ) ) {
				include_once 'Elementor/BbpressElementorHandler.php';
				BbpressElementorHandler::instance();
			}
		}

		/**
		 * Init hooks.
		 */
		protected function hooks() {
			// delete course and delete forum action
			add_action( 'before_delete_post', array( $this, 'delete_post' ) );
			//add_action( 'bbp_template_before_single_topic', array( $this, 'before_single' ) );
			//add_action( 'bbp_template_before_single_forum', array( $this, 'before_single' ) );
			//add_action( 'bbp_template_after_single_topic', array( $this, 'after_single' ) );
			//add_action( 'bbp_template_after_single_forum', array( $this, 'after_single' ) );
			// add_action( 'learn-press/after-single-course', array( $this, 'forum_link' ), 0 );
			add_action( 'learn-press/course-content-summary', array( $this, 'forum_link' ), 71 );
			// Show forum after section instructor
			add_filter( 'learn-press/single-course/modern/section-instructor', array( $this, 'single_course_show_forum' ), 9, 3 );
			add_filter(
				'bbp_user_can_view_forum',
				function ( $retval, $forum_id, $user_id ) {
					$course_id = (int) learn_press_bbp_get_course( $forum_id );
					if ( ! $course_id ) {
						return $retval;
					}

					$can_access = $this->can_access_forum( $forum_id, 'forum' );
					if ( ! $can_access ) {
						wp_enqueue_style( 'learnpress' );
						Template::print_message(
							sprintf(
								__( 'You have to enroll %s to view this forum!', 'learnpress-bbpress' ),
								sprintf( '<a href="%s">%s</a>', get_the_permalink( $course_id ), get_the_title( $course_id ) )
							),
							'warning'
						);
						return false;
					}

					return $retval;
				},
				10,
				3
			);

			add_filter( 'learnpress/course/metabox/tabs', array( $this, 'add_course_metabox' ), 10, 2 );
			add_action( 'learnpress/admin/metabox/select/save', array( $this, 'custom_save_metabox_forum' ), 10, 3 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'learnpress/course-settings/after-course_bbpress', array( $this, 'enqueue_course_settings' ) );
		}

		public function enqueue_assets() {
			wp_register_style(
				'learnpress-bbpress-forum',
				LP_ADDON_BBPRESS_URL . 'assets/css/forum.css',
				array(),
				time()
			);
		}
		public function enqueue_course_settings() {
			wp_enqueue_script(
				'bbpress-course-settings',
				plugins_url( 'assets/js/admin.js', LP_ADDON_BBPRESS_FILE ),
				array(),
				time()
			);
		}

		public function add_course_metabox( $data, $post_id ) {
			$args = array(
				'post_type'   => 'forum',
				'post_status' => 'publish',
				'numberposts' => -1,
			);

			$options        = array();
			$options['']    = esc_html__( 'Create New', 'learnpress-bbpress' );
			$options        = $this->get_forum_options( $options, absint( $post_id ) );
			$selected_forum = get_post_meta( $post_id, '_lp_course_forum', true );
			// Check forum is exists.
			if ( ! empty( $selected_forum ) && ! get_post( absint( $selected_forum ) ) ) {
				$selected_forum = '';
			}

			$data['course_bbpress'] = array(
				'label'    => esc_html__( 'Forum', 'learnpress-certificates' ),
				'icon'     => 'dashicons-list-view',
				'target'   => 'lp_bbpress_course_data',
				'priority' => 60,
				'content'  => array(
					'_lp_bbpress_forum_enable'        => new LP_Meta_Box_Checkbox_Field(
						esc_html__( 'Enable', 'learnpress-bbpress' ),
						esc_html__( 'Enable bbPress forum for this course.', 'learnpress-bbpress' ),
						'no'
					),
					'_lp_course_forum'                => new LP_Meta_Box_Select_Field(
						esc_html__( 'Course Forum', 'learnpress-bbpress' ),
						esc_html__( 'Select forum of this course, choose Create New to create new forum for course, uncheck Enable option to disable.', 'learnpress-bbpress' ),
						'',
						array(
							'options'     => $options,
							'value'       => $selected_forum,
							'custom_save' => true,
						)
					),
					'_lp_bbpress_forum_enrolled_user' => new LP_Meta_Box_Checkbox_Field(
						esc_html__( 'Restrict User', 'learnpress-bbpress' ),
						esc_html__( 'Only user(s) enrolled course can access this forum.', 'learnpress-bbpress' ),
						'no'
					),
				),
			);

			return $data;
		}

		public function custom_save_metabox_forum( $id, $raw_value, $post_id = 0 ) {
			if ( $id === '_lp_course_forum' ) {
				$forum_enable = get_post_meta( $post_id, '_lp_bbpress_forum_enable', true );

				if ( $forum_enable === 'yes' ) {
					if ( empty( $raw_value ) ) {
						$course = get_post( $post_id );
						$forum  = array(
							'post_title'   => $course->post_title . ' Forum',
							'post_content' => '',
							'post_author'  => $course->post_author,
						);

						$forum_id = bbp_insert_forum( $forum, array() );

						update_post_meta( $post_id, '_lp_course_forum', $forum_id );
					} else {
						update_post_meta( $post_id, '_lp_course_forum', absint( $raw_value ) );
					}
				} else {
					update_post_meta( $post_id, '_lp_course_forum', '' );
				}
			}
		}

		/**
		 * Save post.
		 *
		 * @param $post_id
		 */

		/**
		 * Delete forum when delete parent course and disable forum for course when delete it's forum.
		 *
		 * @param $post_id
		 */
		public function delete_post( $post_id ) {

			$post_type = get_post_type( $post_id );

			switch ( $post_type ) {
				case LP_COURSE_CPT:
					$forum_id = get_post_meta( $post_id, '_lp_course_forum', true );

					if ( ! $forum_id ) {
						return;
					}

					wp_delete_post( $forum_id );
					break;

				case 'forum':
					$course_id = learn_press_bbp_get_course( $post_id );

					update_post_meta( $course_id, '_lp_bbpress_forum_enable', 'no' );
					break;
				default:
					break;
			}
		}

		/**
		 * Forum link in single course page.
		 */
		public function forum_link() {
			$courseModel = CourseModel::find( get_the_ID(), true );
			if ( ! $courseModel ) {
				return;
			}

			// $forum_id = get_post_meta( $courseModel->get_id(), '_lp_course_forum', true );
			$forum_id = $courseModel->get_meta_value_by_key( '_lp_course_forum' );
			if ( ! $forum_id ) {
				return;
			}

			if ( ! in_array( get_post_type( $forum_id ), array( 'topic', 'forum' ) ) ) {
				return;
			}

			if ( ! $this->can_access_forum( $forum_id, get_post_type( $forum_id ) ) ) {
				return;
			}

			if ( $courseModel->get_meta_value_by_key( '_lp_bbpress_forum_enable' ) !== 'yes' ) {
				return;
			}
			$forum = get_post( $forum_id );
			if ( empty( $forum ) || get_post_status( $forum_id ) !== 'publish' ) {
				return;
			}
			$content = LPbbPressTemplate::instance()->render_forum_html( (int) $forum_id );
			wp_enqueue_style( 'learnpress-bbpress-forum' );
			echo $content;
		}

		/**
		 * Show forum in single course page.
		 *
		 * @param array $section
		 * @param CourseModel $courseModel
		 * @param UserModel|false $userModel
		 *
		 * @return array
		 */
		public function single_course_show_forum( array $section, CourseModel $courseModel, $userModel ): array {
			if ( ! $courseModel->get_meta_value_by_key( '_lp_bbpress_forum_enable' ) ) {
				return $section;
			}

			$forum_id = (int) $courseModel->get_meta_value_by_key( '_lp_course_forum' );
			if ( ! $forum_id ) {
				return $section;
			}

			$forum = get_post( $forum_id );
			if ( empty( $forum ) || get_post_status( $forum_id ) !== 'publish' ) {
				return $section;
			}

			$html = LPbbPressTemplate::instance()->render_forum_html( $forum_id );

			return apply_filters(
				'learn-press/addon/bbpress/single-course/position',
				Template::insert_value_to_position_array( $section, 'after', 'wrapper_end', 'forum', $html ),
				$html,
				$section,
				$courseModel,
				$userModel
			);
		}

		/**
		 * Check allow user access forum.
		 *
		 * @param $id
		 * @param $type
		 *
		 * @return bool
		 */
		public function can_access_forum( $id, $type ) {
			// invalid forum
			if ( ! $id ) {
				return false;
			}

			// admin, moderator, key master always can access forum
			if ( current_user_can( 'manage_options' ) || current_user_can( 'bbp_moderator' ) || current_user_can( 'bbp_keymaster' ) ) {
				return true;
			}

			if ( $type == 'forum' ) {
				$forum_id = $id;
			} elseif ( $type == 'topic' ) {
				$forum_id = get_post_meta( $id, '_bbp_forum_id', true );
			} else {
				return false;
			}

			$forum = get_post( $forum_id );

			// restrict access bases on ancestor forums
			$ancestor_forums = get_post_ancestors( $forum );

			if ( $ancestor_forums ) {
				foreach ( $ancestor_forums as $ancestor_forum_id ) {
					if ( ! $this->_restrict_access( $ancestor_forum_id ) ) {
						return false;
					}
				}
			}

			$can_access = $this->_restrict_access( $forum_id );

			return $can_access;
		}

		/**
		 * Check forum accessibility.
		 *
		 * @param $forum_id
		 *
		 * @return bool
		 */
		private function _restrict_access( $forum_id ) {
			$course_id = (int) learn_press_bbp_get_course( $forum_id );
			$course    = CourseModel::find( $course_id, true );
			if ( ! $course ) {
				return false;
			}

			// allow access not require enroll course's forum
			if ( $course->has_no_enroll_requirement() ) {
				return true;
			}

			if ( $this->is_public_forum( $course_id ) ) {
				return true;
			}

			$user_id = get_current_user_id();
			$user    = UserModel::find( $user_id, true );
			if ( ! $user ) {
				return false;
			}

			// allow post author access
			if ( $user->get_id() == get_post_field( 'post_author', $course_id ) ) {
				return true;
			}

			$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
			if ( ! $userCourseModel ) {
				return false;
			}

			// restrict user not enroll
			if ( $userCourseModel->has_enrolled_or_finished() ) {
				return true;
			}

			return false;
		}

		/**
		 * Check forum public.
		 *
		 * @param $course_id
		 *
		 * @return bool
		 */
		public function is_public_forum( $course_id ) {
			$course = CourseModel::find( $course_id, true );
			if ( ! $course ) {
				return false;
			}

			$restrict = $course->get_meta_value_by_key( '_lp_bbpress_forum_enrolled_user' );

			if ( $restrict !== 'yes' ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Before single topic and single forum.
		 */
		public function before_single() {
			global $post;

			if ( ! $this->can_access_forum( $post->ID, $post->post_type ) ) {
				$this->_start_forum = true;
				ob_start();
			}
		}

		/**
		 * After single topic and single forum.
		 */
		public function after_single() {
			global $post;

			$course_id = learn_press_bbp_get_course( $post->ID );

			if ( $this->_start_forum ) {
				ob_end_clean(); ?>
				<div id="restrict-access-form-message" style="clear: both;">
					<p><?php esc_html_e( 'You have to enroll the respective course!', 'learnpress-bbpress' ); ?></p>
					<?php if ( $course_id ) : ?>
						<p>
							<?php esc_html_e( 'Go back to ', 'learnpress-bbpress' ); ?>
							<a href="<?php echo esc_url_raw( get_permalink( $course_id ) ); ?>"> <?php echo get_the_title( $course_id ); ?></a>
						</p>
					<?php endif; ?>
				</div>
				<?php
			}
		}
		/**
		 * get forum options for course
		 * @param  array       $options
		 * @param  int|integer $course_id
		 * @return array       $options
		 */
		public function get_forum_options( array $options = [], int $course_id = 0 ): array {
			global $wpdb;
			if ( empty( $course_id ) ) {
				return $options;
			}
			$tb_post     = $wpdb->posts;
			$tb_postmeta = $wpdb->postmeta;
			$sql         = $wpdb->prepare(
				"SELECT forum.ID, forum.post_title FROM {$tb_post} AS forum
					WHERE
					forum.ID NOT IN (
					SELECT cm.meta_value FROM {$tb_postmeta} AS cm
					INNER JOIN {$tb_post} AS c ON cm.post_id = c.ID
					WHERE c.post_type=%s
					AND cm.meta_key=%s
					AND c.post_status=%s
					AND cm.meta_value IS NOT NULL AND c.ID != %d)
					AND forum.post_type=%s AND forum.post_status=%s",
				array( LP_COURSE_CPT, '_lp_course_forum', 'publish', $course_id, 'forum', 'publish' )
			);
			$result      = $wpdb->get_results( $sql, ARRAY_A );
			if ( ! empty( $result ) ) {
				foreach ( $result as $forum ) {
					$options[ $forum['ID'] ] = $forum['post_title'];
				}
			}
			return $options;
		}
	}
}
