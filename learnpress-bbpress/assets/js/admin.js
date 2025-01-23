document.addEventListener('DOMContentLoaded', (e) => {
	let lpBbpressTabWrapper = document.querySelector('#lp_bbpress_course_data');
	if ( ! lpBbpressTabWrapper ) {
		return;
	}
	const hideShowBbPressLpSettings = (value) => {
		const selectForumWraper = document.querySelector( '.form-field._lp_course_forum_field' ),
		restrictUserWraper = document.querySelector( '.form-field._lp_bbpress_forum_enrolled_user_field ' );
		if ( value ) {
			selectForumWraper.style.display = 'flex';
			restrictUserWraper.style.display = 'flex';
		} else {
			selectForumWraper.style.display = 'none';
			restrictUserWraper.style.display = 'none';
		}
	}
	let enableForumCheckbox = document.querySelector('#_lp_bbpress_forum_enable');
	hideShowBbPressLpSettings( enableForumCheckbox.checked );
    enableForumCheckbox.addEventListener('change', function() {
    	hideShowBbPressLpSettings( this.checked );
    });
});