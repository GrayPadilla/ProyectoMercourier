<!-- Full Height Modal Left Info Demo-->
<div class="modal fade" id="wpcfe-registration" tabindex="-1" role="dialog"
    aria-labelledby="<?php esc_html_e( 'Registration', 'wpcargo-frontend-manager' ); ?>" aria-hidden="true" data-backdrop="false">
    <div class="modal-dialog modal-lg modal-notify modal-info" role="document">
        <!--Content-->
        <div class="modal-content">
            <!--Header-->
            <div class="modal-header primary-color-dark">
                <p class="heading lead"><i class="fa fa-user-circle text-white"></i> <?php esc_html_e( 'Registration', 'wpcargo-frontend-manager' ); ?></p>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_html_e( 'Close', 'wpcargo-frontend-manager' ); ?>">
                    <span aria-hidden="true" class="white-text">&times;</span>
                </button>
            </div>
            <!--Body-->
            <div class="modal-body">
              <?php require_once( WPCFE_PATH.'templates/registration-form.tpl.php'); ?>
              
              <!-- Términos y Condiciones Checkbox -->
              <div class="form-check mt-3 pt-3 border-top">
                <input type="checkbox" class="form-check-input" id="terminos_condiciones" name="terminos_condiciones" required>
                <label class="form-check-label" for="terminos_condiciones">
                  Acepto los <a href="https://www.mercourier.com/terminos" target="_blank" rel="noopener noreferrer">Términos y Condiciones</a>
                </label>
                <div class="invalid-feedback">
                  Debes aceptar los Términos y Condiciones para registrarte.
                </div>
              </div>
            </div>
            <!--Footer-->
            <div class="modal-footer justify-content-center">
            </div>
        </div>
        <!--/.Content-->
    </div>
</div>
<!-- Full Height Modal Right Info Demo-->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar checkbox de términos y condiciones en el formulario de registro
    const registrationModal = document.getElementById('wpcfe-registration');
    if (registrationModal) {
        // Buscar el formulario de registro dentro del modal
        const registrationForm = registrationModal.querySelector('form');
        const terminosCheckbox = document.getElementById('terminos_condiciones');
        
        if (registrationForm && terminosCheckbox) {
            // Validar antes de submit
            registrationForm.addEventListener('submit', function(e) {
                if (!terminosCheckbox.checked) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Mostrar mensaje de error
                    terminosCheckbox.classList.add('is-invalid');
                    terminosCheckbox.parentElement.classList.add('was-validated');
                    
                    console.log('❌ Debe aceptar los Términos y Condiciones para registrarse');
                    return false;
                } else {
                    terminosCheckbox.classList.remove('is-invalid');
                    console.log('✅ Términos y Condiciones aceptados');
                }
            });
            
            // Remover clase de error cuando el usuario marca el checkbox
            terminosCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    this.classList.remove('is-invalid');
                }
            });
        }
    }
});
</script>