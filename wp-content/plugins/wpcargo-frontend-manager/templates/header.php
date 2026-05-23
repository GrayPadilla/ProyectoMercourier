<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="shortcut icon" href="<?php echo wpcfe_dashboard_favicon_url(); ?>" type="image/x-icon">
	<link rel="icon" href="<?php echo wpcfe_dashboard_favicon_url(); ?>" type="image/x-icon">	
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title><?php echo bloginfo( 'name' ) ?> | <?php the_title(); ?></title>
    <?php do_action('wpcfe_dashboard_header'); ?>
    <?php wp_head(); ?>
</head>
<body class="grey lighten-3 wpcargo-dashboard page-<?php echo get_the_ID(); ?>">