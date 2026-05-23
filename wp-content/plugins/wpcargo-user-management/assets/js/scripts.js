jQuery(document).ready(function ($) {
    console.log('✅ Scripts.js cargado');
    const userDefault = wpcumanageAjaxHandler.userDefault;
    const assignFields = wpcumanageAjaxHandler.assignFields;
    // Assignement Option
    const optEmployee = wpcumanageAjaxHandler.optEmployee;
    const optDriver = wpcumanageAjaxHandler.optDriver;
    const optAgent = wpcumanageAjaxHandler.optAgent;
    const optClient = wpcumanageAjaxHandler.optClient;
    const optBranch = wpcumanageAjaxHandler.optBranch;

    // Assign User default
    if (Object.keys(userDefault).length) {
        var addShipmentFormElem = $('.add-shipment');
        for (let [key, value] of Object.entries(userDefault)) {
            let attrName = assignFields[key].target_name;
            if (attrName) {
                addShipmentFormElem.find('input[name="' + attrName + '"], select[name="' + attrName + '"], textarea[name="' + attrName + '"]').val(value);
            }
        }
    }
    // Client Approval Client
    $('[data-toggle="tooltip"]').tooltip();
    $('.wpcumanage-select2').select2({
        placeholder: wpcumanageAjaxHandler.selectAccessLabel,
    });
    $(".wpcumanage-select2").on("select2:select", function (evt) {
        var element = evt.params.data.element;
        var $element = $(element);
        $element.detach();
        $(this).append($element);
        $(this).trigger("change");
    });
    $('#wpcumanage-add-group').on('click', function () {
        var modal_id = $(this).data('target');
        $(modal_id).find('#wpcumanage_ug_users').select2({
            placeholder: '',
            allowClear: true
        });;
    });
    $('#_groups').select2({
        placeholder: wpcumanageAjaxHandler.selectGroupLabel,
    });
    $("#wpcumanageCheckboxAll").click(function () {
        if ($("#wpcumanageCheckboxAll").is(':checked')) {
            $(".wpcumanage-select2-access > option").prop("selected", true);
            $(".wpcumanage-select2-access").trigger("change");
        } else {
            $(".wpcumanage-select2-access > option").prop("selected", false);
            $(".wpcumanage-select2-access").trigger("change");
        }
    });
    $('.wpcumanage-select2-access').on('select2:clearing', function (e) {
        $("#wpcumanageCheckboxAll").prop("checked", false);
    });

    $('#wpcumanage-user-list').on('click', '.wpcfe-approve-client', function () {
        var currentDOM = $(this);
        var userID = currentDOM.data('id');
        $.ajax({
            type: "POST",
            data: {
                action: 'wpcfe_approve_client',
                userID: userID
            },
            url: wpcumanageAjaxHandler.ajaxurl,
            beforeSend: function () {
                $('body').append('<div class="wpcargo-loading">Loading...</div>');
            },
            success: function (data) {
                currentDOM.closest('.wpcumanage-roles').text(data.role);
                currentDOM.closest('.wpcumanage-status').html('<span style="color:#00a32a;"><span class="dashicons dashicons-email-alt2" ></span> Email Sent!</span>');
                $('body .wpcargo-loading').remove();
            }
        });
    });
    function IsEmail(email) {
        var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if (!regex.test(email)) {
            return false;
        } else {
            return true;
        }
    }
    // Deactivate User Account
    $('.wpcumange-deactivate-account').on('click', function (e) {
        e.preventDefault();
        var currentDOM = $(this);
        var currentRow = currentDOM.closest('.user-row');
        var tbLength = currentRow.children('td').length;
        var userID = currentDOM.data('id');
        
        Swal.fire({
            title: '¿Desactivar cuenta?',
            text: wpcumanageAjaxHandler.userConfimation,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    data: {
                        action: 'deactivate_account',
                        userID: userID
                    },
                    url: wpcumanageAjaxHandler.ajaxurl,
                    beforeSend: function () {
                        $('body').append('<div class="wpcargo-loading">Loading...</div>');
                    },
                    success: function (data) {
                        $('body .wpcargo-loading').remove();
                        if (data.status == 'error') {
                            Swal.fire({icon:'error',title:'Error',text:data.message,confirmButtonColor:'#e74c3c'});
                            return false;
                        }
                        currentRow.html(
                            `
                            <td colspan="${tbLength}" class="text-info">${data.message}</td>
                            `
                        );
                        Swal.fire({icon:'success',title:'¡Éxito!',text:'Cuenta desactivada',confirmButtonColor:'#27ae60'});
                        setTimeout(function () { currentRow.remove(); }, 3000);
                    }
                });
            }
        });
    });
    
    // Desbloquear usuario manualmente con SweetAlert
    var unblockUserID = 0;
    var unblockButtonDOM = null;
    
    $('#wpcumanage-user-list').on('click', '.wpcum-unblock-user', function (e) {
        e.preventDefault();
        console.log('🔓 Click en botón desbloquear');
        unblockUserID = $(this).data('id');
        unblockButtonDOM = $(this);
        console.log('User ID:', unblockUserID);
        
        Swal.fire({
            title: '¿Desbloquear usuario?',
            text: '¿Estás seguro de que deseas desbloquear este usuario?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, desbloquear',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#27ae60',
            cancelButtonColor: '#95a5a6'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    data: {
                        action: 'wpcumanage_unblock_user',
                        user_id: unblockUserID
                    },
                    url: wpcumanageAjaxHandler.ajaxurl,
                    beforeSend: function () { $('body').append('<div class="wpcargo-loading">Loading...</div>'); },
                    success: function (data) {
                        console.log('🟢 AJAX desbloqueo response:', data);
                        $('body .wpcargo-loading').remove();
                        if (data.status == 'error') { 
                            Swal.fire({icon:'error',title:'Error',text:data.message,confirmButtonColor:'#e74c3c'});
                            return false;
                        }
                        // Reemplazar por botón Bloquear
                        if (unblockButtonDOM && unblockButtonDOM.length) {
                            unblockButtonDOM.closest('td').html('<button class="btn btn-sm btn-success wpcum-block-user px-2 m-0 text-white" data-id="' + unblockUserID + '">Bloquear</button>');
                        }
                        Swal.fire({icon:'success',title:'¡Éxito!',text:data.message || 'Usuario desbloqueado correctamente',confirmButtonColor:'#27ae60'});
                    },
                    error: function () {
                        $('body .wpcargo-loading').remove();
                        Swal.fire({icon:'error',title:'Error',text:'Error al desbloquear el usuario',confirmButtonColor:'#e74c3c'});
                    }
                });
            }
        });
    });
    // Bloquear usuario manualmente con SweetAlert
    var blockUserID = 0;
    var blockButtonDOM = null;
    $('#wpcumanage-user-list').on('click', '.wpcum-block-user', function (e) {
        e.preventDefault();
        blockUserID = $(this).data('id');
        blockButtonDOM = $(this);
        console.log('🧭 click .wpcum-block-user', { blockUserID: blockUserID });

        Swal.fire({
            title: '¿Bloquear usuario?',
            text: '¿Deseas bloquear a este usuario manualmente? Esta acción impedirá crear envíos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, bloquear',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#e67e22',
            cancelButtonColor: '#95a5a6'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    data: {
                        action: 'wpcumanage_block_user',
                        user_id: blockUserID
                    },
                    url: wpcumanageAjaxHandler.ajaxurl,
                    beforeSend: function () { $('body').append('<div class="wpcargo-loading">Loading...</div>'); },
                    success: function (data) {
                        console.log('🟢 AJAX bloqueo response:', data);
                        $('body .wpcargo-loading').remove();
                        if (data.status == 'error') { 
                            Swal.fire({icon:'error',title:'Error',text:data.message,confirmButtonColor:'#e74c3c'});
                            return false;
                        }
                        // Reemplazar por botón Desbloquear
                        if (blockButtonDOM && blockButtonDOM.length) {
                            blockButtonDOM.closest('td').html('<button class="btn btn-sm btn-danger wpcum-unblock-user px-2 m-0 text-white" data-id="' + blockUserID + '">Desbloquear</button>');
                        }
                        Swal.fire({icon:'success',title:'¡Éxito!',text:data.message || 'Usuario bloqueado correctamente',confirmButtonColor:'#27ae60'});
                        blockUserID = 0; 
                        blockButtonDOM = null;
                    },
                    error: function (xhr, status, err) { 
                        $('body .wpcargo-loading').remove(); 
                        console.error('❌ AJAX error bloqueo:', status, err, xhr); 
                        Swal.fire({icon:'error',title:'Error',text:'Error al procesar la solicitud',confirmButtonColor:'#e74c3c'});
                    }
                });
            }
        });
    });

    // Email Checker
    $('#umanageUserForm input[name="email"], .profile-section.personal-profile input[name="user_email"]').blur(function () {
        console.log('email');
        var currentField = $(this);
        var email = currentField.val();
        currentField.parent().removeClass('error');
        currentField.parent().find('label .error-message').remove();
        if (IsEmail(email)) {
            $.ajax({
                type: "POST",
                data: {
                    action: 'wpcfe_check_email',
                    email: email
                },
                url: wpcumanageAjaxHandler.ajaxurl,
                beforeSend: function () {
                    $('body').append('<div class="wpcfe-spinner">Loading...</div>');
                    currentField.parent().removeClass('error');
                    currentField.parent().find('.error-feedback').remove();
                    $('#umanageUserForm button[type="submit"]').prop('disabled', true);
                },
                success: function (data) {
                    $('body .wpcfe-spinner').remove();
                    if (data >= 1) {
                        currentField.parent().addClass('error');
                        currentField.parent().append(' <div class="error-feedback">Email already exist!</div>');
                        $('#umanageUserForm button[type="submit"]').prop('disabled', true);
                    } else {
                        $('#umanageUserForm button[type="submit"]').prop('disabled', false);
                    }
                }
            });
        } else if (email != '') {
            currentField.focus();
            currentField.parent().addClass('error');
            currentField.parent().find('label .error-message').remove();
            currentField.parent().find('label').append(' <span class="error-message">' + wpcfeAjaxhandler.errorInCorrectEmail + '</span>');
        }
    });

    // Branch Auto file options
    const cleanDefaultOptions = (parentForm) => {
        parentForm.find('[name="__default_branch_manager"] option:not(:first), [name="__default_client"] option:not(:first), [name="__default_agent"] option:not(:first), [name="__default_employee"] option:not(:first), [name="__default_driver"] option:not(:first)').remove();
    }
    const setDefaultOptions = (fieldName, data = null) => {
        if (!data) {
            if (fieldName == '__default_employee') {
                data = optEmployee;
            }
            if (fieldName == '__default_agent') {
                data = optAgent;
            }
            if (fieldName == '__default_driver') {
                data = optDriver;
            }
            if (fieldName == '__default_client') {
                data = optClient;
            }
        }

        if (!data || Object.keys(data).length == 0) {
            if (fieldName == '__default_branch_manager') {
                $('#wpcumanageAssingmentModal-form').find('select[name="__default_branch_manager"]').attr('readonly', 'readonly');
            }
            return;
        }
        if (fieldName == '__default_branch_manager') {
            $('#wpcumanageAssingmentModal-form').find('select[name="__default_branch_manager"]').removeAttr('readonly');
        }
        $.each(data, function (index, item) {
            $(`[name="${fieldName}"]`).append($('<option>', {
                value: index,
                text: item
            }));
        });
        $("#selectList").append(new Option("option text", "value"));
    }

    if ($('select#__default_branch').length) {
        $('#wpcumanageAssingmentModal-form').on('change', '#__default_branch', function () {
            const parentForm = $(this).closest('form');
            const branchID = $(this).val();
            $.ajax({
                type: "POST",
                data: {
                    action: 'branch_options',
                    branchID: branchID,
                },
                url: wpcBMFrontendAjaxHandler.ajaxurl,
                beforeSend: function () {
                    //** Proccessing
                    $('body').append('<div class="wpc-loading">Loading...</div>');
                    cleanDefaultOptions(parentForm);
                },
                success: function (response) {
                    setDefaultOptions('__default_client', response.data.client);
                    setDefaultOptions('__default_agent', response.data.agent);
                    setDefaultOptions('__default_employee', response.data.employee);
                    setDefaultOptions('__default_driver', response.data.driver);
                    setDefaultOptions('__default_branch_manager', response.data.manager);
                    $('body .wpc-loading').remove();
                }
            });
        });
    }

    // user Profile
    function generatePassword() {
        return Math.random().toString(36).slice(-10);
    }
    $('#upass-generate').on('click', function (e) {
        e.preventDefault();
        $(this).parent().find('.upass-wrapper').removeClass('d-none');
        $(this).parent().find('#upass').val(generatePassword());
        $(this).parent().find('#upass').prop('disabled', false);
    });
    $('#upass-cancel').on('click', function (e) {
        e.preventDefault();
        $(this).closest('.upass-wrapper').addClass('d-none');
        $(this).closest('.upass-wrapper').find('#upass').val();
        $(this).closest('.upass-wrapper').find('#upass').prop('disabled', true);
    });
    // Access Scripts
    var accessModal = $('#wpcumanageAccessModal');
    accessModal.on('hidden.bs.modal', function () {
        accessModal.find('#_userid').val('');
        $("#wpcumanageCheckboxAll").prop("checked", false);
        $(".wpcumanage-select2-access > option").prop("selected", false);
        $(".wpcumanage-select2-access").trigger("change");
    });
    $('#wpcumanage-user-list').on('click', '.wpcumange-update-access', function (e) {
        e.preventDefault();
        var userID = $(this).data('id');
        var access = $(this).attr('data-access');
        if (access.length > 0) {
            $('#_access').val(access.split(','));
            $('#_access').trigger('change');
        }
        accessModal.find('#_userid').val(userID);
    });
    // Form Submission Script
    $('#wpcumanageAccessModal-form').on('submit', function (e) {
        e.preventDefault();
        var formData = $(this).serializeArray();
        $.ajax({
            type: "POST",
            data: {
                action: 'save_user_access',
                formData: formData
            },
            url: wpcumanageAjaxHandler.ajaxurl,
            beforeSend: function () {
                $('body').append('<div class="wpcargo-loading">Loading...</div>');
            },
            success: function (data) {
                $('#wpcumanage-user-list #user-' + data.user_id).find('.wpcumanage-access a').attr('data-access', data.access.join());
                $('#wpcumanage-user-list #user-' + data.user_id).find('.wpcumanage-access a').text(
                    `
                    (${data.access.length}) Access
                    `
                );
                if (data.access.length == 0) {
                    $('#wpcumanage-user-list #user-' + data.user_id).find('.wpcumanage-access a').removeClass('btn-info').addClass('btn-light text-dark');
                } else {
                    $('#wpcumanage-user-list #user-' + data.user_id).find('.wpcumanage-access a').removeClass('btn-light text-dark').addClass('btn-info');
                }

                $('#wpcumanage-user-list #user-' + data.user_id).find('.wpcumange-update-access').attr('data-access', data.access_key.join());
                $('#wpcumanageAccessModal').modal('hide');
                $('body .wpcargo-loading').remove();
            }
        });
    });
    // User Assignment
    var assignmentModal = $('#wpcumanageAssingmentModal');
    assignmentModal.on('hidden.bs.modal', function () {
        assignmentModal.find('input, select, textarea').val('');
    });
    $('#wpcumanage-user-list').on('click', '.wpcumange-update-assign_user', function (e) {
        e.preventDefault();
        var userID = $(this).data('id');
        var defaultUser = JSON.parse($(this).attr('data-default'));

        cleanDefaultOptions($('#wpcumanageAssingmentModal-form'));
        setDefaultOptions('__default_client', null);
        setDefaultOptions('__default_agent', null);
        setDefaultOptions('__default_employee', null);
        setDefaultOptions('__default_driver', null);
        if (defaultUser['__default_branch'] && optBranch[defaultUser['__default_branch']]) {
            setDefaultOptions('__default_branch_manager', optBranch[defaultUser['__default_branch']]);
        } else {
            setDefaultOptions('__default_branch_manager', null);
        }

        if (Object.keys(defaultUser).length) {
            for (let [key, value] of Object.entries(defaultUser)) {
                assignmentModal.find('input[name="' + key + '"], select[name="' + key + '"], textarea[name="' + key + '"]').val(value);
            }
        }
        assignmentModal.find('#_userid').val(userID);
    });
    // Form Submission Script
    $('#wpcumanageAssingmentModal-form').on('submit', function (e) {
        e.preventDefault();
        var formData = $(this).serializeArray();
        $.ajax({
            type: "POST",
            data: {
                action: 'save_user_assignment',
                formData: formData
            },
            url: wpcumanageAjaxHandler.ajaxurl,
            beforeSend: function () {
                $('body').append('<div class="wpcargo-loading">Loading...</div>');
            },
            success: function (data) {
                $('#wpcumanage-user-list #user-' + data.user_id).find('.wpcumanage-default a').attr('data-default', JSON.stringify(data.assigned_ids));
                $('#wpcumanage-user-list #user-' + data.user_id).find('.wpcumanage-default a').text(
                    `
                    (${Object.keys(data.assigned_ids).length}) Defaults
                    `
                );
                if (data.assigned_names.length == 0) {
                    $('#wpcumanage-user-list #user-' + data.user_id).find('.wpcumanage-default a').removeClass('btn-info').addClass('btn-light text-dark');
                } else {
                    $('#wpcumanage-user-list #user-' + data.user_id).find('.wpcumanage-default a').removeClass('btn-light text-dark').addClass('btn-info');
                }
                assignmentModal.modal('hide');
                $('body .wpcargo-loading').remove();
            }
        });
    });
    // Add User Group
    $('#addUserGroup-form, #updateUserGroup-form').on('submit', function (e) {
        e.preventDefault();
        var group_id = $(this).find('#wpcumanage_ug_id').val();
        var label = $(this).find('#wpcumanage_ug_label').val();
        var description = $(this).find('#wpcumanage_ug_desc').val();
        var wpcumanage_ug_users = $(this).find('#wpcumanage_ug_users').val();
        var save_type = $(this).data('type');

        $.ajax({
            url: wpcumanageAjaxHandler.ajaxurl,
            type: 'post',
            data: {
                action: 'wpcumanage_save_user_group',
                group_id: group_id,
                label: label,
                description: description,
                wpcumanage_ug_users: wpcumanage_ug_users,
                save_type: save_type,
            },
            beforeSend: function () {
                $('body').append('<div class="wpcargo-loading">Loading...</div>');
            },
            success: function (response) {
                window.location.reload();
            }
        });
    });
    // UPDATE USER GROUP
    $('.wpcumanage-update-group').on('click', function () {
        var modal = $(this).data('target');
        var group_id = $(this).data('id');
        var form = $(modal).find('form');

        if (group_id) {
            form.attr('data-id', group_id);
            $.ajax({
                url: wpcumanageAjaxHandler.ajaxurl,
                type: 'post',
                data: {
                    action: 'frontend_get_user_group_data',
                    group_id: group_id
                },
                beforeSend: function () {
                    $('body').append('<div class="wpcargo-loading">Loading...</div>');
                },
                success: function (response) {
                    form.find('.modal-body').html(response);
                    form.find('#wpcumanage_ug_users').select2({
                        placeholder: '',
                        allowClear: true
                    });
                    $('body').find('.wpcargo-loading').remove();
                    //window.location.reload();
                }
            });
        }
    });
    // DELETE USER GROUP
    $("body").on('click', '.wpcumanage-delete-group', function (e) {
        e.preventDefault();
        var id = $(this).attr('data-id');
        
        Swal.fire({
            title: '¿Eliminar grupo?',
            text: '¿Estás seguro de que deseas eliminar este grupo de usuario?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: wpcumanageAjaxHandler.ajaxurl,
                    type: 'post',
                    data: {
                        action: 'wpcumanage_delete_user_group',
                        id: id,
                    },
                    beforeSend: function () {
                        $('body').append('<div class="wpcargo-loading">Loading...</div>');
                    },
                    success: function (response) {
                        $('body .wpcargo-loading').remove();
                        Swal.fire({icon:'success',title:'¡Eliminado!',text:'Grupo eliminado correctamente',confirmButtonColor:'#27ae60'}).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function() {
                        $('body .wpcargo-loading').remove();
                        Swal.fire({icon:'error',title:'Error',text:'Error al eliminar el grupo',confirmButtonColor:'#e74c3c'});
                    }
                });
            }
        });
    });
});