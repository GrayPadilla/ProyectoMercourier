<div class="table-top form-group">
    <form id="wpcfe-search" class="float-md-none float-lg-right" action="<?php echo get_permalink( wpcumanage_users_page() ); ?>" method="get">
        <div class="form-sm d-flex gap-2" style="align-items: center;">
            <label for="search-shipment" class="sr-only"><?php _e('Search User', 'wpcargo-invoice' ); ?></label>
            <input type="text" class="form-control form-control-sm" name="_user" id="search-shipment" placeholder="<?php _e('Search User', 'wpcargo-invoice' ); ?>" value="<?php echo $searched_user; ?>" style="flex: 1; min-width: 150px;">
            
            <!-- Filtro por Motorizado Asignado -->
            <select name="merc_driver_filter" class="form-control form-control-sm" id="merc_driver_filter" style="min-width: 180px;">
                <option value="">-- Todos los Motorizados --</option>
                <?php 
                $drivers = get_users(['role' => 'wpcargo_driver', 'number' => 100]);
                foreach ($drivers as $driver) {
                    $selected = (isset($_GET['merc_driver_filter']) && $_GET['merc_driver_filter'] == $driver->ID) ? 'selected' : '';
                    echo '<option value="' . esc_attr($driver->ID) . '" ' . $selected . '>' . esc_html($driver->display_name) . '</option>';
                }
                ?>
            </select>
            
            <button type="submit" class="btn btn-primary btn-sm px-0" style="white-space: nowrap; text-align: center;width: -webkit-fill-available;"><?php _e('Buscar', 'wpcargo-invoice' ); ?></button>
        </div>
    </form>
</div>