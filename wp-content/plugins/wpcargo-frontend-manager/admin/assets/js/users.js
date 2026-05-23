jQuery(document).ready(function($){
    var adminURL =  wpcfeAjaxhandler.adminURL;
    // Client Approval Client
    $('.wp-list-table.users').on('click', '.wpcfe-approve-client', function(){
        var currentDOM  = $(this);
        var parentDOM   = currentDOM.parent().parent();
        var userID      = $(this).data('id');
        $.ajax({
            type:"POST",
            data:{
                action  : 'wpcfe_approve_client',    
                userID   : userID
            },
            url : wpcfeAjaxhandler.ajaxurl,
            beforeSend:function(){
                $('body').append('<div class="wpcargo-loading">Loading...</div>');
                currentDOM.prop('disabled', true);
            },
            success:function(data){
                parentDOM.find('.role.column-role').text( data.role ); 
                parentDOM.find('.column-wpcfe_approval_status').append('<span style="color:#00a32a;"><span class="dashicons dashicons-email-alt2" ></span> Email Sent!</span>');
                currentDOM.remove();
                $('body .wpcargo-loading').remove();
            }
        });
    });
});