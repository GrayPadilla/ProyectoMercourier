jQuery(document).ready(function($) {
    $('.delete-field').click(function(e){
		e.preventDefault();
		if(confirm("Are you sure you want to delete this field?"))  {
		var parentContainer = $(this).parent().attr('id');
		var parentList = $(this).parent().parent().parent().attr('id');	
		$.ajax({
			type:"POST",
			data:{
				action:'delete_CF',	
				cfID:$(this).attr('data-id'),
			},
			url : deleteCFhandler.ajax_url,
			beforeSend:function(){
				$('body').append("<div class='wpcargo-loading'>Loading...</div>");
			},
			success:function(data){
				$('#'+ parentList ).remove();
				$('body .wpcargo-loading' ).remove();
			}
		});
		}
	});
   $('#field-type').on('change', function(){
		   var selectedValue = $(this).val();
		   if( selectedValue == 'select' || selectedValue == 'multiselect' || selectedValue == 'radio' || selectedValue == 'checkbox' ){
			   $("#select-list td textarea").removeAttr('readonly');
			   $("#select-list td textarea").prop('required',true);
		   }else{
			   $("#select-list td textarea").attr('readonly','readonly');
			   $("#select-list td textarea").prop('required',false);
		   }
   });
   $('#field-select input').on('change', function(){
       var selectedValue = $(this).val();
		if( selectedValue == 'new' ){
			$('#new').css('display','block');
			$('#new input').attr('name', 'field_key');
			$('#new input').attr('required', 'required');
			
			$('#existing').css('display','none');
			$("#existing option[value='']").attr('selected', true);
			$("#existing select").attr('name', 'dummy');
			$("#existing select").removeAttr('required');
		}else{
			$('#new').css('display','none');
			$('#new input').attr('name', 'dummy');
			$("#new input").removeAttr('required');
			
			$('#existing').css('display','block');
			$("#existing select").attr('name', 'field_key');
			$("#existing select").attr('required', 'required');
		}
   });

	// conditional logic scripts
	$('.condition-repeater').repeater({
		show: function () {
			$(this).slideDown();
		},
		hide: function (deleteElement) {
			if(confirm('Are you sure you want to delete this element?')) {
				$(this).slideUp(deleteElement);
			}
		},
		isFirstItemUndeletable: true
	})

	$('body').on('change', 'input#condition_logic_enable', function(){
		let isChecked = $(this).is(':checked');
		let conditionalLogicSection = $('div.conditional-logic-section');
		let conditionalLogicSectionFields = conditionalLogicSection.find('.main-required');

		// show or hide section
		if(isChecked){
			if(conditionalLogicSection.hasClass('d-none')){
				conditionalLogicSection.removeClass('d-none');
			}
		} else {
			if(!conditionalLogicSection.hasClass('d-none')){
				conditionalLogicSection.addClass('d-none');
			}
		}

		// set section fields required attribute
		conditionalLogicSectionFields.each(function(){
			$(this).prop('required', isChecked).trigger('change');
		});
	});

	$('body').on('change', 'select.condition_checker', function(){
		let theVal = $(this).val();
		let theParent = $(this).closest('div');
		let conditionFieldValue = theParent.find('input.condition_field_value');
		let isRequired = false;
		if(theVal){
			if(theVal === 'is' || theVal === 'is-not'){
				isRequired = true;
			}
		}
		conditionFieldValue.prop('required', isRequired).trigger('change');
	});
   
});