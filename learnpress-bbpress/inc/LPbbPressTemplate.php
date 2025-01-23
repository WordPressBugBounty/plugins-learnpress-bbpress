<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/bbPress/Classes
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\UserTemplate;

class LPbbPressTemplate {
	use Singleton;

	public function init() {
	}

	public function html_title( int $forum_id, string $tag_html = 'h3' ): string {
		$tilte        = get_post_field( 'post_title', $forum_id );
		$tag_html     = sanitize_key( $tag_html );
		$html_wrapper = apply_filters(
			'learn-press/single-course-bbpress-forum/html-title',
			array(
				"<{$tag_html} class='lp-bbpress-forum-title'>" => "</{$tag_html}>",
			),
			$forum_id
		);
		return Template::instance()->nest_elements( $html_wrapper, $tilte );
	}

	public function html_description( int $forum_id, string $tag_html = 'p' ): string {
		$forum_content = get_post_field( 'post_content', $forum_id );
		$tag_html      = sanitize_key( $tag_html );
		$html_wrapper  = apply_filters(
			'learn-press/single-course-bbpress-forum/html-description',
			array(
				"<{$tag_html} class='lp-bbpress-forum-description'>" => "</{$tag_html}>",
			),
			$forum_id
		);
		return Template::instance()->nest_elements( $html_wrapper, $forum_content );
	}

	public function html_forum_author( int $forum_id, int $size = 50 ): string {
		$tc_int      = bbp_get_forum_topic_count( $forum_id, true, true );
		$topic_count = bbp_get_forum_topic_count( $forum_id, true, false );
		$author_id   = get_post_field( 'post_author', $forum_id );
		$user_model  = UserModel::find( $author_id, true );
		if ( ! $user_model ) {
			return '';
		}

		$author_url   = bbp_get_user_profile_url( $author_id );
		$userTemplate = new UserTemplate();
		$avatar_html  = $userTemplate->html_avatar(
			$user_model,
			array(
				'height' => $size,
				'width'  => $size,
			)
		);
		$last_active  = bbp_get_forum_last_active_id( $forum_id );
		$time_since   = '';
		if ( ! empty( $last_active ) ) {
			$topic_text  = bbp_get_forum_topics_link( $forum_id );
			$time_since  = __( 'last updated ', 'learnpress-bbpress' );
			$time_since .= bbp_get_forum_freshness_link( $forum_id );
			// $last_updated_by = bbp_get_author_link( array( 'post_id' => $last_active, 'size' => $size ) );
		} else {
			$topic_text = sprintf( _n( '%s topic', '%s topics', $tc_int, 'bbpress' ), $topic_count );
		}

		$topic_text = sprintf( '<span class="topic">%s</span>', $topic_text );
		if ( ! empty( $time_since ) ) {
			$time_since = sprintf( '<span class="time-since"> - %s</span>', $time_since );
		}

		$author_text = __( 'Created by ', 'learnpress-bbpress' ) . sprintf( '<a href="%1$s">%2$s</a>', $author_url, $user_model->display_name );
		$section     = apply_filters(
			'learn-press/single-course-bbpress-forum/forum-author-sections',
			array(
				'wrapper'                  => '<div class="lp-bbpress-forum-author">',
				'left_col_start'           => '<div class="left">',
				'author_avatar_html'       => $avatar_html,
				'left_col_end'             => '</div>',
				'right_col_start'          => '<div class="right">',
				'author_name_wrapper'      => '<p class="author-name">',
				'author_name'              => $author_text,
				'author_name_wrapper_end'  => '</p>',
				'forum_detail_wrapper'     => '<p class="forum-details">',
				'forum_detail_text'        => $topic_text . $time_since,
				'forum_detail_wrapper_end' => '</p>',
				'right_col_end'            => '</div>',
				'wrapper_end'              => '</div>',
			),
			$forum_id,
			$user_model
		);
		return Template::combine_components( $section );
	}

	public function html_forum_link( int $forum_id ): string {
		$forum_link = get_permalink( $forum_id );
		$html       = sprintf(
			'<a class="lp-bbpress-forum-link lp-button" href="%1$s">%2$s</a>',
			esc_url_raw( $forum_link ),
			__( 'Join Forum', 'learnpress-bbpress' )
		);

		return $html;
	}

	public function render_forum_html( int $forum_id ): string {
		$sections = apply_filters(
			'learn-press/single-course-bbpress-forum/forum-content',
			array(
				'wrapper'     => '<div class="lp-bbpress-forum-wrapper">',
				'header'      => sprintf( '<h3 class="lp-bbpress-forum-header">%s</h3>', __( 'Forum', 'learnpress-bbpress' ) ),
				'title'       => $this->html_title( $forum_id ),
				'author'      => $this->html_forum_author( $forum_id, 50 ),
				'description' => $this->html_description( $forum_id ),
				'button_link' => $this->html_forum_link( $forum_id ),
				'wrapper_end' => '</div>',
			),
			$forum_id
		);

		return Template::combine_components( $sections );
	}
}
