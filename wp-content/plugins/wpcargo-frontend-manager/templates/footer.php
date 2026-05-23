<?php wp_footer(); ?>
<?php do_action('wpcfe_dashboard_footer'); ?>
<!-- Initializations -->
    <script type="text/javascript">
        // Animations initialization
        new WOW().init();
        if ( jQuery.isFunction(jQuery.fn.sideNav) ) {
            jQuery('.navbar-toggler').sideNav({
                edge: 'right', // Choose the horizontal origin
                closeOnClick: true // Closes side-nav on &lt;a&gt; clicks, useful for Angular/Meteor
            });
        }
	</script>
</body>
</html>