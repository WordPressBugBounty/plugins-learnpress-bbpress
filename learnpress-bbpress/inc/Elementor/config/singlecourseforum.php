<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;
use Elementor\Group_Control_Border;

// Fields tab style
$style_fields = array_merge(
    LPElementorControls::add_fields_in_section(
		'forum_header',
		esc_html__( 'Header Forum', 'learnpress-bbpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'forum_header',
			'.lp-bbpress-forum-header'
		)
	),
    LPElementorControls::add_fields_in_section(
		'title_forum',
		esc_html__( 'Title Forum', 'learnpress-bbpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'title_forum',
			'.lp-bbpress-forum-title'
		)
	),
    LPElementorControls::add_fields_in_section(
		'author',
		esc_html__( 'Author', 'learnpress-bbpress' ),
		Controls_Manager::TAB_STYLE,
        [
            'image_radius'       => LPElementorControls::add_responsive_control_type(
                'image_radius',
                esc_html__( 'Border Radius', 'learnpress-bbpress' ),
                [],
                Controls_Manager::DIMENSIONS,
                [
                    'size_units' => [ 'px', '%', 'custom' ],
                    'selectors'  => array(
                        '{{WRAPPER}} .user-avatar img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ),
                ]
            )
        ]
    ),
    LPElementorControls::add_fields_in_section(
		'description_forum',
		esc_html__( 'Description Forum', 'learnpress-bbpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'description_forum',
			'.lp-bbpress-forum-description'
		)
	),
    LPElementorControls::add_fields_in_section(
		'btn_forum',
		esc_html__( 'Forum Button', 'learnpress-bbpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_button(
			'forum',
			'.lp-bbpress-forum-link'
		)
	)
);

return apply_filters(
	'learn-press/elementor/single-course-forum',
    apply_filters(
        'learn-press/elementor/single-course-forum/tab-styles',
        $style_fields
    )
);