jQuery( function($) {
	$(document).ready( function() {
		init_settings_conditions();
		init_api_logs();
	});

	function init_settings_conditions(){
		let settings_form = $('#smef-settings-form');

		if( settings_form.length ){
			settings_form.find('[data-condition]').each( function(){
				let field = $(this);
				let condition = field.data('condition').split('|');
				let condition_field_id = condition[0];
				let condition_field_val = condition[1];

				if( typeof condition_field_id != 'undefined' && typeof condition_field_val != 'undefined' ){
					let condition_field = settings_form.find('#' + condition_field_id);

					if( condition_field.length ){
						condition_field.on( 'change', function(){
							
							if( condition_field.val() == condition_field_val ){
								field.closest('tr').show();
							}else{
								field.closest('tr').hide();
							}
						});

						condition_field.trigger('change');
					}
				}
			});
		}
	}

	function init_api_logs(){
		$('body').on( 'click', 'a.smef-clear-logs', function(e){
			if( !confirm( smef_localize.are_you_sure ) ){
				e.preventDefault();
				return false;
			}
		});
	}
});
