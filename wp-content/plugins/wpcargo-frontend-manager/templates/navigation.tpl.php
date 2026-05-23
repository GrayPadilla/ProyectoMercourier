<!--Main Navigation-->
<?php
$create_active_class = ( get_the_ID() == wpcfe_admin_page() && isset( $_GET['wpcfe']) && $_GET['wpcfe'] == 'add' ) ? 'active' : '' ; 
$unseen_shipments  = wpcfe_disable_unseen() ? 0 : wpcfe_get_user_unseen_shipments();
$unseen  = $unseen_shipments > 9 ? '9&#43;' : $unseen_shipments ;

?>
<header>
    <!-- Navbar -->
    <nav class="navbar fixed-top navbar-expand-lg navbar-light white scrolling-navbar <?php echo is_rtl() ? 'rtl' : ''; ?>">
        <div class="container-fluid">
			<!-- Brand -->
			<a class="navbar-brand waves-effect d-sm-inline-block d-md-inline-block d-lg-none" href="<?php echo esc_url( add_query_arg( 'noredirect', '1', home_url( '/' ) ) ); ?>">
				<img src="<?php echo wpcfe_dashboard_logo_url(); ?>" class="img-fluid" alt="<?php esc_html_e( 'Site Logo', 'wpcargo-frontend-manager' ); ?>" style="width: auto; margin:0 auto" />
			</a>
            <!-- Collapse -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMobileMenuContent"
                aria-controls="navbarMobileMenuContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- Links -->
            <div class="collapse navbar-collapse" id="navbarMobileMenuContent">
            	<?php if( is_user_logged_in() ): ?>
					<div class="nav-section search-nav mr-auto w-50">
						<!-- Search form -->
						<form class="form-inline md-form form-sm active-cyan-2 my-0" method="GET" action="<?php echo $page_url; ?>">
							<i class="fa fa-search" aria-hidden="true"></i>
							<input type="hidden" name="wpcfe" value="track">
							<input class="form-control form-control-sm my-0 ml-2 w-75" type="text" name="num" placeholder="<?php echo apply_filters('wpcfe_track_shipment',esc_html__('Track Shipment', 'wpcargo-frontend-manager') ); ?>"
							aria-label="<?php echo apply_filters('wpcfe_track_shipment',esc_html__('Track Shipment', 'wpcargo-frontend-manager') ); ?>">					  
						</form>
					</div>
		        <?php endif; ?>
                <?php
					$wpcfe_top_menu_args = array(
						'echo' 			 => FALSE,
						'theme_location' => 'wpcfe-dashboard-top-menu',
						'menu_class'     => 'nav navbar-nav nav-flex-icons ml-auto',
						'link_before'    => '',
						'link_after'     => '',
						'walker'        => new WPCFE_Dashboard_Top_Menu(),
						'fallback_cb'   => false,
						'container'     => ''
					);
					echo wp_nav_menu( $wpcfe_top_menu_args );
	            ?>
                <div class="nav-section mobile-sidebar-menu d-sm-inline-block d-md-inline-block d-lg-none">
					<?php
						do_action( 'wpcfe_before_add_shipment' );
						if( wpcfe_admin_page() ){
							$user_roles = wpcfe_current_user_role();
							?>
							<?php if( !wpcfe_add_shipment_deactivated() ): ?>
								<?php if( can_wpcfe_add_shipment() ): ?>
									<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>/?wpcfe=add" class="list-group-item waves-effect <?php echo $create_active_class; ?>"> <i class="fa fa-plus mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_create_shipment', esc_html__('Create Shipment', 'wpcargo-frontend-manager') ); ?> </a>
									<?php do_action( 'wpcfe_after_create_shipment' ); ?>
								<?php endif;  
								endif;
						if( $unseen_shipments ){ ?>
						<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>" class="list-group-item waves-effect <?php //echo $create_active_class; ?>"> <i class="fa fa-cubes mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_shipments_menu', sprintf( __('Shipments <span class="badge badge-pill bg-danger align-top">%s</span>', 'wpcargo-frontend-manager'), $unseen ) ); ?> </a>
					<?php } else{ ?>
						<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>" class="list-group-item waves-effect <?php //echo $create_active_class; ?>"> <i class="fa fa-cubes mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_shipments_menu', esc_html__('Shipments', 'wpcargo-frontend-manager') ); ?> </a>
					<?php }
						}
						do_action( 'wpcfe_after_add_shipment' );

						if( !empty( wpcfe_after_sidebar_menu_items() ) ){
							foreach( wpcfe_after_sidebar_menu_items() as $item => $additional_items ){
								?>
								<a href="<?php echo $additional_items['permalink']; ?>" class="dashboard-page-menu list-group-item list-group-item-action waves-effect menu-item <?php echo $item; ?>"> 
									<?php if( !empty( $additional_items['icon'] ) ): ?>
										<i class="fa <?php echo $additional_items['icon']; ?> mr-3"></i>
									<?php endif; ?>
									<?php echo $additional_items['label']; ?> 
								</a>
								<?php
							}
						}
					?>       
					<?php
						if( !empty( wpcfe_after_sidebar_menus() ) ){
							foreach( wpcfe_after_sidebar_menus() as $item => $additional_items ){
								?>
								<a href="<?php echo $additional_items['permalink']; ?>" class="list-group-item waves-effect <?php echo $item; ?>"> 
									<?php if( !empty( $additional_items['icon'] ) ): ?>
										<i class="fa <?php echo $additional_items['icon']; ?> mr-3"></i>
									<?php endif; ?>
									<?php echo $additional_items['label']; ?> 
								</a>
								<?php
							}
						}
						$wpcfe_sidebar_menu_args = array(
							'theme_location' => 'wpcfe-dashboard-sidebar-menu',
							'menu_class' 	 => 'list-group list-group-flush',
							'link_before'  	 => '',
							'link_after' 	 => '',
							'walker' 		=> new WPCFE_Dashboard_Sidebar_Menu(),
							'fallback_cb'   => false,
						);
						wp_nav_menu( $wpcfe_sidebar_menu_args );
						do_action( 'wpcfe_after_sidebar_custom_menu' ); 
					?>
		        </div>
		          <?php if( is_user_logged_in() ): ?>
					<div class="nav-section nav-account-dropdown <?php if( empty( wp_nav_menu( $wpcfe_top_menu_args ) ) ) { echo 'ml-auto'; } ?> <?php echo wp_is_mobile() ? 'my-4' : '' ; ?>">
						<?php
							$fullname = $wpcargo->user_fullname( get_current_user_id() );
							$user_avatar = wpcfe_user_avatar_url() ? '<img src="'.wpcfe_user_avatar_url().'" width="30" height="30">' : '<i class="fa fa-user-circle text-primary" style="font-size:30px;vertical-align: middle;"></i>' ;
						?>
						<a href="#" class="nav-wpcfe-account">
							<?php echo $user_avatar; ?>
							<span class="account-label"><?php echo $fullname; ?></span>
						</a>
						<ul class="account-dropdown">
							<li>
								<?php 
									$acount_link = get_the_permalink( wpc_profile_get_frontend_page() );
									$acount_link = apply_filters('profile_acount_link', $acount_link );
								?>
								<a href="<?php echo $acount_link; ?>"><?php esc_html_e( 'My Profile', 'wpcargo-frontend-manager' ); ?></a>
							</li>
							<!--<li><a href="#"><?php esc_html_e( 'Notifications', 'wpcargo-frontend-manager' ); ?></a></li>-->
							<?php do_action( 'wpcfe_after_profile_dropdown', get_current_user_id() ); ?>
							<li><a href="<?php echo wp_logout_url( home_url() ); ?>"><?php esc_html_e( 'Logout', 'wpcargo-frontend-manager' ); ?></a></li>
						</ul>
					</div>
		        <?php endif; ?>
				<?php do_action('wpcfe_after_profile_icon', get_current_user_id());?>
            </div>
        </div>
    </nav>
    <!-- Navbar -->
    <!-- Sidebar -->
    <div class="sidebar-fixed position-fixed">
		<a class="logo-wrapper waves-effect d-block text-center" href="<?php echo esc_url( add_query_arg( 'noredirect', '1', home_url( '/' ) ) ); ?>">
        	<img src="<?php echo wpcfe_dashboard_logo_url(); ?>" class="img-fluid" alt="<?php esc_html_e( 'Site Logo', 'wpcargo-frontend-manager' ); ?>" style="width: auto; margin:0 auto" />
        </a>
        <div class="list-group list-group-flush">
			<?php
				if( wpcfe_admin_page() ){
					$user_roles = wpcfe_current_user_role();
					do_action( 'wpcfe_before_add_shipment' );
					if( !wpcfe_add_shipment_deactivated() ):
						if( can_wpcfe_add_shipment() ): ?>
							<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>?wpcfe=add" class="list-group-item waves-effect <?php echo $create_active_class; ?>"> 
								<i class="fa fa-plus mr-md-3 d-none d-lg-inline-block d-xl-inline-block"></i><?php echo apply_filters( 'wpcfe_create_shipment', esc_html__('Create Shipment', 'wpcargo-frontend-manager') ); ?> 
							</a>
							<?php do_action( 'wpcfe_after_create_shipment' ); ?>
						<?php endif;
					endif; 
					if( $unseen_shipments ){ ?>
						<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>" class="list-group-item waves-effect <?php //echo $create_active_class; ?>"> <i class="fa fa-cubes mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_shipments_menu', sprintf( __('Shipments <span class="badge badge-pill bg-danger align-top">%s</span>', 'wpcargo-frontend-manager'), $unseen ) ); ?> </a>
					<?php } else{ ?>
						<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>" class="list-group-item waves-effect <?php //echo $create_active_class; ?>"> <i class="fa fa-cubes mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_shipments_menu', esc_html__('Shipments', 'wpcargo-frontend-manager') ); ?> </a>
					<?php }

					do_action( 'wpcfe_after_add_shipment' );
					if( !empty( wpcfe_after_sidebar_menu_items() ) ){
						foreach( wpcfe_after_sidebar_menu_items() as $item => $additional_items ){
							$page_id = array_key_exists( 'page-id', $additional_items ) ? $additional_items['page-id'] : 0;
							$active_class = '';
							if( !isset($_GET['wpcfe']) && get_the_ID() == $page_id ){
								$active_class = 'active';
							}
							?>
							<a href="<?php echo $additional_items['permalink']; ?>" class="list-group-item waves-effect <?php echo $item.' '.$active_class; ?>"> 
								<?php if( !empty( $additional_items['icon'] ) ): ?>
									<i class="fa <?php echo $additional_items['icon']; ?> mr-3"></i>
								<?php endif; ?>
								<?php echo $additional_items['label']; ?> 
							</a>
							<?php
						}
					}
				}
			?>
			<?php do_action( 'wpcfe_before_sidebar_custom_menu' ); ?>
			<?php
				if( !empty( wpcfe_after_sidebar_menus() ) ){
					foreach( wpcfe_after_sidebar_menus() as $item => $additional_items ){
						?>
						<a href="<?php echo $additional_items['permalink']; ?>" class="list-group-item waves-effect <?php echo $item; ?>"> 
							<?php if( !empty( $additional_items['icon'] ) ): ?>
								<i class="fa <?php echo $additional_items['icon']; ?> mr-3"></i>
							<?php endif; ?>
							<?php echo $additional_items['label']; ?> 
						</a>
						<?php
					}
				}
				$wpcfe_menu_args = array(
					'theme_location' => 'wpcfe-dashboard-sidebar-menu',
					'menu_class' 	 => 'list-group list-group-flush',
					'link_before'  	 => '',
					'link_after' 	 => '',
					'walker' 		=> new WPCFE_Dashboard_Sidebar_Menu(),
					'fallback_cb'   => false,
				);
				wp_nav_menu( $wpcfe_menu_args );
				do_action( 'wpcfe_after_sidebar_custom_menu' ); 
			?>
        </div>
    </div>
    <!-- Sidebar -->
</header>

<!-- ESTILOS MEJORADOS PARA EL MENÚ -->
<style>
    /* ========== ACCOUNT DROPDOWN MEJORADO ========== */
    .account-dropdown {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .account-dropdown li {
        margin: 0;
        padding: 0;
    }
    
    .account-dropdown li a {
        display: block;
        width: 100%;
        padding: 12px 16px;
        color: #495057;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .account-dropdown li a:hover {
        background: rgba(41, 128, 185, 0.08);
        color: #2980b9;
        padding-left: 20px;
    }
    
    /* ========== SIDEBAR MEJORADO ========== */
    .sidebar-fixed {
        background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        border-right: 1px solid #e9ecef;
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
    }
    
    .sidebar-fixed .logo-wrapper {
        padding: 10px 15px;
        border-bottom: 2px solid #e9ecef;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 60px;
    }
    
    .sidebar-fixed .logo-wrapper:hover {
        background: rgba(41, 128, 185, 0.05);
    }
    
    /* ========== LIST GROUP MEJORADO ========== */
    .sidebar-fixed .list-group {
        background: transparent;
        padding: 10px 0;
    }
    
    .sidebar-fixed .list-group-item {
        border: none;
        border-left: 4px solid transparent;
        padding: 12px 16px;
        margin: 0;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        color: #495057;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        background: transparent;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    /* Iconos en el menú */
    .sidebar-fixed .list-group-item i {
        font-size: 16px;
        width: 20px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    /* Hover state */
    .sidebar-fixed .list-group-item:hover {
        background: rgba(41, 128, 185, 0.08);
        color: #2980b9;
        border-left-color: #2980b9;
        padding-left: 20px;
        transform: translateX(4px);
    }
    
    .sidebar-fixed .list-group-item:hover i {
        transform: scale(1.15);
        color: #2980b9;
    }
    
    /* Active state */
    .sidebar-fixed .list-group-item.active,
    .sidebar-fixed .list-group-item.waves-effect.active {
        background: linear-gradient(135deg, rgba(41, 128, 185, 0.15) 0%, rgba(41, 128, 185, 0.08) 100%);
        color: #2980b9;
        border-left: 4px solid #2980b9;
        font-weight: 600;
        box-shadow: inset 0 2px 4px rgba(41, 128, 185, 0.1);
    }
    
    .sidebar-fixed .list-group-item.active i {
        color: #2980b9;
        transform: scale(1.2);
    }
    
    /* Separadores visuales entre grupos */
    .sidebar-fixed .list-group-item:nth-child(2),
    .sidebar-fixed .list-group-item:nth-child(6),
    .sidebar-fixed .list-group-item:nth-child(10),
    .sidebar-fixed .list-group-item:nth-child(15),
    .sidebar-fixed .list-group-item:nth-child(20) {
        padding-top: 14px;
        border-top: 1px solid #e9ecef;
    }
    
    /* Badges en los items */
    .sidebar-fixed .list-group-item .badge {
        margin-left: auto;
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        font-weight: 600;
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .sidebar-fixed .list-group-item:hover .badge,
    .sidebar-fixed .list-group-item.active .badge {
        transform: scale(1.1);
    }
    
    /* ========== MEJORAS VISUALES ADICIONALES ========== */
    .sidebar-fixed .list-group-item.waves-effect {
        position: relative;
        overflow: hidden;
    }
    
    .sidebar-fixed .list-group-item.waves-effect::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(41, 128, 185, 0.2);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    
    .sidebar-fixed .list-group-item.waves-effect:active::before {
        width: 300px;
        height: 300px;
    }
    
    /* Items especiales con colores personalizados */
    .sidebar-fixed .list-group-item[href*="add"],
    .sidebar-fixed .list-group-item:has(i.fa-plus) {
        background: linear-gradient(135deg, rgba(46, 204, 113, 0.08) 0%, transparent 100%);
        border-left-color: #27ae60;
    }
    
    .sidebar-fixed .list-group-item[href*="add"]:hover,
    .sidebar-fixed .list-group-item:has(i.fa-plus):hover {
        background: linear-gradient(135deg, rgba(46, 204, 113, 0.15) 0%, rgba(46, 204, 113, 0.08) 100%);
        color: #27ae60;
        border-left-color: #27ae60;
    }
    
    .sidebar-fixed .list-group-item[href*="add"] i,
    .sidebar-fixed .list-group-item:has(i.fa-plus) i {
        color: #27ae60;
    }
    
    /* ========== MOBILE MENU MEJORADO ========== */
    .mobile-sidebar-menu {
        max-height: calc(100vh - 120px);
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        padding: 10px 0;
    }
    
    /* Scroll personalizado en mobile */
    .mobile-sidebar-menu::-webkit-scrollbar {
        width: 6px;
    }
    
    .mobile-sidebar-menu::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .mobile-sidebar-menu::-webkit-scrollbar-thumb {
        background: #bbb;
        border-radius: 3px;
    }
    
    .mobile-sidebar-menu::-webkit-scrollbar-thumb:hover {
        background: #888;
    }
    
    .mobile-sidebar-menu .list-group-item {
        border: none;
        border-left: 4px solid transparent;
        padding: 12px 16px;
        margin: 0;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        color: #495057;
        transition: all 0.3s ease;
        background: transparent;
    }
    
    .mobile-sidebar-menu .list-group-item:hover {
        background: rgba(41, 128, 185, 0.08);
        color: #2980b9;
        border-left-color: #2980b9;
        padding-left: 20px;
    }
    
    .mobile-sidebar-menu .list-group-item.active {
        background: rgba(41, 128, 185, 0.15);
        color: #2980b9;
        border-left: 4px solid #2980b9;
    }
    
    /* ========== ANIMACIONES SUAVES ========== */
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .sidebar-fixed .list-group-item {
        animation: slideIn 0.3s ease forwards;
    }
    
    .sidebar-fixed .list-group-item:nth-child(n) {
        animation-delay: calc(0.05s * var(--menu-index, 0));
    }
</style>

<!--Main Navigation-->
