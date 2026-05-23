jQuery(document).ready(function( $ ){
    var sectionID;
    $('body').on('click', '.wpccf-upload-attachment', function(){
        $('#upload-attachment-modal').addClass('wpcargo-show-modal');
        sectionID = $(this).data('section');
    });
    $('body').on('click', '#upload-attachment-modal .close', function(){
        $('#upload-attachment-modal').removeClass('wpcargo-show-modal');
    }); 

    Dropzone.autoDiscover = false;
    $("#wpccf-dropzone-form").dropzone({ 
        url: dzoneAjaxHandler.ajaxurl,
        acceptedFiles: dzoneAjaxHandler.acceptedFiles,
        maxFiles: dzoneAjaxHandler.maxFileUpload,
        uploadMultiple: false,
        maxFilesize: dzoneAjaxHandler.maxFilesize, // 5 MB
        addRemoveLinks: true,
        dictRemoveFile: dzoneAjaxHandler.removeLabel,
        success : function(file, xhr, formData){
            if( file.status !== 'error'){
                var fileData = JSON.parse(xhr);
                dropzone_insert_attachment( fileData.attactment_id, file.dataURL, fileData.basename );
            }
        },
        sending : function(file, xhr, formData) {
            formData.append("name", "value"); // Append all the additional input data of your form here!
        },
        complete : function(file) {
            if( file.status !== 'error'){
                this.removeFile(file);
                $('#upload-attachment-modal .close').trigger('click');
            }
        }
    });
    
    function dropzone_insert_attachment( id, url, name ){
        var imageGalleryIDs = $( '#wpcpq_upload_ids_'+sectionID );
        var galleryIDs 		= $( '#wpcpq_upload_ids_'+sectionID ).val().split(',');
        var galleryImages	= $( '#wpcargo-gallery-container_'+sectionID ).find( 'ul.wpccf_uploads' );
        if( typeof url === "undefined" ){
            url = dzoneAjaxHandler.media_icon;
        }
        galleryImages.append( '<li class="image" data-attachment_id="' + id + '">'+
                        '<img width="120" height="120" src="' + url + '" />'+
                        '<span class="img-title">'+name.substr(0,18)+'</span>'+
                        '<span class="actions"><a href="#" class="delete" data-imgID="'+id+'" data-section="'+sectionID+'">x</a></span>'+
                        '</li>' );
        galleryIDs.push( Number(id) );
        imageGalleryIDs.val( galleryIDs.join() );
    }
    $( '.wpccf_uploads' ).on( 'click', 'a.delete', function() {   
        var section = $(this).data('section');
        $( this ).closest( 'li.image' ).remove();
        var imageGalleryIDs = $( '#wpcpq_upload_ids_'+section );
        var attachment_ids  = [];
        $( '#wpcargo-gallery-container_'+section ).find( 'ul.wpccf_uploads li.image' ).css( 'cursor', 'default' ).each( function( index ) {
            var attachment_id = jQuery( this ).attr( 'data-attachment_id' );
            attachment_ids.push( attachment_id );
        });
        imageGalleryIDs.val( attachment_ids.join() );
        $( '#tiptip_holder' ).removeAttr( 'style' );
        $( '#tiptip_arrow' ).removeAttr( 'style' );
        return false;
    });
       
});
