<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/bbPress/Classes
 * @version  3.0.4
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Addon_bbPress' ) ) {
	/**
	 * Class LP_Addon_bbPress.
	 *
	 * @since 3.0.0
	 */
	class LP_Addon_bbPress extends LP_Addon {

		/**
		 * @var bool
		 */
		protected $_start_forum = false;

		/**
		 * LP_Addon_bbPress constructor.
		 */
		public function __construct() {
			$this->version         = LP_ADDON_BBPRESS_VER;
			$this->require_version = LP_ADDON_BBPRESS_REQUIRE_VER;

			parent::__construct();
		}

		/**
		 * Define constants.
		 */
		protected function _define_constants() {
			define( 'LP_ADDON_BBPRESS_PATH', dirname( LP_ADDON_BBPRESS_FILE ) );
			define( 'LP_ADDON_BBPRESS_TEMPLATE', LP_ADDON_BBPRESS_PATH . '/templates/' );
		}

		/**
		 * Includes files.
		 */
		protected function _includes() {
			include_once 'functions.php';
		}

		/**
		 * Init hooks.
		 */
		protected function _init_hooks() {
			// delete course and delete forum action
			add_action( 'before_delete_post', array( $this, 'delete_post' ) );
			add_action( 'bbp_template_before_single_topic', array( $this, 'before_single' ) );
			add_action( 'bbp_template_before_single_forum', array( $this, 'before_single' ) );
			add_action( 'bbp_template_after_single_topic', array( $this, 'after_single' ) );
			add_action( 'bbp_template_after_single_forum', array( $this, 'after_single' ) );
			add_action( 'learn-press/single-course-summary', array( $this, 'forum_link' ), 0 );

			add_filter( 'learnpress/course/metabox/tabs', array( $this, 'add_course_metabox' ), 10, 2 );
			add_action( 'learnpress/admin/metabox/select/save', array( $this, 'custom_save_metabox_forum' ), 10, 3 );
		}

		public function add_course_metabox( $data, $post_id ) {
			$args = array(
				'post_type'   => 'forum',
				'post_status' => 'publish',
				'numberposts' => -1,
			);

			$options     = array();
			$options[''] = esc_html__( 'Create New', 'learnpress-bbpress' );

			$forums_posts = get_posts( $args );

			if ( ! empty( $forums_posts ) ) {
				foreach ( $forums_posts as $forums_post ) {
					$course_id = learn_press_bbp_get_course( get_the_ID() );

					if ( ! $course_id || $course_id == $post_id || LP_COURSE_CPT == get_post_type() ) {
						$options[ $forums_post->ID ] = $forums_post->post_title;
					}
				}
			}

			$value_forum = get_post_meta( $post_id, '_lp_course_forum', true );

			// Check forum is exists.
			if ( ! empty( $value_forum ) && ! get_post( absint( $value_forum ) ) ) {
				$value_forum = '';
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
							'value'       => $value_forum,
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

			$course = LP_Global::course();

			if ( ! $course ) {
				return;
			}

			$forum_id = get_post_meta( $course->get_id(), '_lp_course_forum', true );

			if ( ! $forum_id ) {
				return;
			}

			if ( ! in_array( get_post_type( $forum_id ), array( 'topic', 'forum' ) ) ) {
				return;
			}

			if ( ! $this->can_access_forum( $forum_id, get_post_type( $forum_id ) ) ) {
				return;
			}

			if ( get_post_meta( $course->get_id(), '_lp_bbpress_forum_enable', true ) !== 'yes' ) {
				return;
			}

			learn_press_get_template(
				'forum-link.php',
				array( 'forum_id' => $forum_id ),
				learn_press_template_path() . '/addons/bbpress/',
				LP_ADDON_BBPRESS_TEMPLATE
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
		private function can_access_forum( $id, $type ) {
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
			$ancestor_forums = $forum->ancestors;

			if ( $ancestor_forums ) {
				foreach ( $ancestor_forums as $ancestor_forum_id ) {
					if ( ! $this->_restrict_access( $ancestor_forum_id ) ) {
						return false;
					}
				}
				$can_access = true;
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
			$course_id = learn_press_bbp_get_course( $forum_id );

			// normal publish forum which has no connecting with any courses
			if ( ! $course_id ) {
				return true;
			}

			if ( LP_COURSE_CPT !== get_post_type( $course_id ) ) {
				return;
			}

			$course = learn_press_get_course( $course_id );

			$required_enroll = $course->is_required_enroll();

			// allow access not require enroll course's forum
			if ( ! $required_enroll ) {
				return true;
			}

			if ( $this->is_public_forum( $course_id ) ) {
				return true;
			}

			$user = learn_press_get_current_user();

			if ( ! $user->get_id() ) {
				return false;
			}

			// allow post author access
			if ( $user->get_id() == get_post_field( 'post_author', $course_id ) ) {
				return true;
			}

			// restrict user not enroll
			$user_course_data = $user->get_course_data( $course_id );
			$status           = $user_course_data ? $user_course_data->get_data( 'status' ) : false;
			if ( in_array( $status, array( 'enrolled', 'finished' ) ) ) {
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
			$restrict = get_post_meta( $course_id, '_lp_bbpress_forum_enrolled_user', true );

			if ( is_null( $restrict ) || ( $restrict === false ) || ( $restrict == '' ) || ( $restrict == 'no' ) ) {
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
	}
}
