jQuery(window).on('elementor:init', function () {
	elementor.hooks.addAction('panel/open_editor/widget/form', function( panel, model, view ){
		function handleMutation( mutationsList ) {
			mutationsList.forEach( function( mutation ) {
				if( mutation.type === 'childList' ){
					mutation.addedNodes.forEach( function( node ){
						if ( jQuery(node).is('.elementor-control-smoove_fields_map_input_template:visible') || jQuery(node).find('.elementor-control-smoove_fields_map_input_template:visible').length) {
							generate_field_map_inputs( panel, model );
						}
					});

					mutation.removedNodes.forEach( function( node ){
						if ( jQuery(node).is('.elementor-control-smoove_fields_map_input_template_clone') ) {
							panel.$el.find('.elementor-control-smoove_fields_map_input').remove();
						}
					});
				}
			});
		}

		const observer = new MutationObserver( handleMutation );

		const targetNode = panel.$el.find('#elementor-panel-page-editor')[0];
		const config = { childList: true, subtree: true };

		if (targetNode) {
			observer.observe(targetNode, config);
		}
	});

	function generate_field_map_inputs( panel, model ){
		let settings = model.get('settings');
		let form_fields = model.getSetting('form_fields').toJSON();

		let input_template = panel.$el.find('.elementor-control-smoove_fields_map_input_template');

		if( input_template.length ){
			input_template.removeClass('elementor-control-smoove_fields_map_input_template');
			input_template.addClass('elementor-control-smoove_fields_map_input_template_clone');
		}else{
			input_template = panel.$el.find('.elementor-control-smoove_fields_map_input_template_clone');
		}

		panel.$el.find('.elementor-control-smoove_fields_map_input').remove();

		if( form_fields.length && input_template.length ){
			let prev_field_input;
			let field_input;

			form_fields = form_fields.filter( field => field.field_type != 'upload' );

			form_fields.forEach((field) => {
				let field_id = field.custom_id;
				let field_setting_name = 'smoove_fields_map_input_' + field_id;
				let field_label = ( field.field_label != '' ) ? field.field_label : field.placeholder;
				let field_value = settings.attributes[field_setting_name];

				field_input = input_template.clone();
			
				let field_input_select = field_input.find('select');

				field_input.removeClass('elementor-control-smoove_fields_map_input_template_clone');
				field_input.addClass('elementor-control-smoove_fields_map_input');
				field_input.addClass('elementor-control-smoove_fields_map_input_' + field.custom_id);
				field_input.find('.elementor-control-title').text( field_label );
				field_input_select.attr( 'data-setting', field_setting_name ).data( 'setting', field_setting_name );
				field_input.find('[id]').removeAttr('id');

				field_input.removeClass('elementor-control-type-select');
				field_input.addClass('elementor-control-type-select2');

				if( field_value ){
					field_input_select.val( field_value );
				}

				if( prev_field_input ){
					field_input.insertAfter( prev_field_input );
				}else{
					field_input.insertAfter( input_template );
				}

				prev_field_input = field_input;
		
				field_input_select.select2({
					placeholder: field_input_select.find('option[value="0"]').first().text(),
					allowClear: true
				});

				field_input_select.on( 'change', function(){
					let select = jQuery(this);
					model.setSetting( select.data('setting'), select.val() );
					input_template.find('select').trigger('change');
				})
			});
		}
	}

	function insert_form_field_options_OLD( panel, model ){
		let settings = model.get('settings');
		let form_fields = model.getSetting('form_fields').toJSON();

		let fields_map_items = panel.$el.find('[class*="elementor-control-smoove_fields_map_input_"]:not(.elementor-control-smoove_fields_map_input_template):visible');

		if( fields_map_items.length && form_fields.length ){
			let options_html;

			form_fields = form_fields.filter( field => field.field_type != 'upload' );

			fields_map_items.each( function(){
				let select = jQuery(this).find('select');

				if( select.length ){
					let field_name = select.data('setting');
					let field_value = settings.attributes[field_name];
					
					options_html = '<option value="">' + smef_localize.select_placeholder + '</option>';

					form_fields.forEach((field) => {
						let selected = ( field_value == field.custom_id ) ? ' selected' : '';

						options_html += '<option value="' + field.custom_id + '"' + selected + '>';
							options_html += ( field.field_label != '' ) ? field.field_label : field.placeholder;
						options_html += '</option>';
					});

					select.html( options_html );
				}
			});
		}
	}
});
