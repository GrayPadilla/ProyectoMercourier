/*document.onload = () => {
    hidePackageSection();
}

function hidePackageSection() {

    const packageSection = document.getElementById('package_id');
    packageSection.style.display = 'none';

}*/

jQuery(document).ready(function() {

    jQuery('.wpsr-control').each(function() {
        jQuery(this).select2({minimumInputLength: 3});
    });

});