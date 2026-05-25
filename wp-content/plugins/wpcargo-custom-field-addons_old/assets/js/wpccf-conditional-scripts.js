jQuery(document).ready(function($){

  let {
    is_wpcfe_add_shipment_page,
    is_wpcfe_update_shipment_page
  } = wpccfConditionalLocalize;

  // initialize conditionizeJS
  $('.wpccf-conditionize').each(function(){
    let el = $(this);

    el.conditionize({
      onload: true,
      updateOn: ['change', 'input'],
      ifTrue: function(element){
        let tagName = element.get(0).tagName.toLowerCase();
        let theVal = element.val();
        let isJautoCalcField = element.attr('jautocalc');
        let isValueRemovable = true;
        switch (tagName) {
          case 'select':
            isValueRemovable = false;
            break;
          case 'input':
            let theType = element.attr('type');
            if(theType == 'radio' || theType == 'checkbox') {
              isValueRemovable = false;
            } else {
              if(isJautoCalcField) {
                isValueRemovable = false;
              }
            }
            break;
          default:
            break;
        }
        let parent = element.closest('section');
        const {is_conditional, is_required, is_show} = element.data('condition_opts');
        if(is_show === 'show'){
          parent.removeClass('d-none');
          if(is_required){
            element.prop('required', true);
          }
        } else {
          parent.addClass('d-none');
          if(isValueRemovable) {
            setTimeout(() => {
              element.val('').trigger('change');
            }, 1);
          }
          if(is_required){
            element.prop('required', false);
          }
        }
      },
      ifFalse: function(element){
        let tagName = element.get(0).tagName.toLowerCase();
        let theVal = element.val();
        let isJautoCalcField = element.attr('jautocalc');
        let isValueRemovable = true;
        switch (tagName) {
          case 'select':
            isValueRemovable = false;
            break;
          case 'input':
            let theType = element.attr('type');
            if(theType == 'radio' || theType == 'checkbox') {
              isValueRemovable = false;
            } else {
              if(isJautoCalcField) {
                isValueRemovable = false;
              }
            }
            break;
          default:
            break;
        }
        let parent = element.closest('section');
        const {is_conditional, is_required, is_show} = element.data('condition_opts');
        if(is_show === 'show'){
          parent.addClass('d-none');
          if(isValueRemovable) {
            setTimeout(() => {
              element.val('').trigger('change');
            }, 1);
          }
          if(is_required){
            element.prop('required', false);
          }
        } else {
          parent.removeClass('d-none');
          if(is_required){
            element.prop('required', true);
          }
        }
      },
    });
  });
});