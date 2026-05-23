<!-- Import/Expor Tab -->
<div id = "address-import-export">
    <ul id="address-pagination">
        <li class="<?php echo ( $import == 'shipper' ) ? 'active' : ''; ?>"><a href="<?php echo $page_url; ?>?import=shipper"><?php esc_html_e('Shipper Book Address', 'wpcargo-address-book' ); ?></a></li>
        <li class="<?php echo ( $import == 'receiver' ) ? 'active' : ''; ?>"><a href="<?php echo $page_url; ?>?import=receiver"><?php esc_html_e('Receiver Book Address', 'wpcargo-address-book' ); ?></a></li>
    </ul>
</div>

<!-- Import/Export Template -->
<div class = "col-md-12 md-4 wpcfe-address">
    <section class = "row">
        <section class = "col-md-6 md-4 one-halves">
            <div id="export-section" class="one-halves wpcfe-address-book">
                <div class="col-md-12">
                    <h5><?php esc_html_e('Export ', 'wpcargo-address-book' ); ?><?php echo ucfirst( $import ); ?><?php esc_html_e(' Address Book', 'wpcargo-address-book' ); ?> </h5>
                    <form id="wpcfe-export-address-book">
                        <!-- hidden input -->
                        <input type = "hidden" name = "booktype" value = "<?php echo $import; ?>">
                        <div class="input-group mb-3 date">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="date-from"><?php esc_html_e( 'Date From', 'wpcargo-address-book' ); ?></span>
                            </div>
                            <input name = "date_from" type="text" class="form-control wpcfe-adrress-datepicker" placeholder="yyyy/mm/dd" aria-label="date-from" aria-describedby="date-from">
                        </div>

                        <div class="input-group mb-3 date">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="date-to"><?php esc_html_e( 'Date To', 'wpcargo-address-book' ); ?></span>
                            </div>
                            <input name = "date_to" type="text" class="form-control wpcfe-adrress-datepicker" placeholder="yyyy/mm/dd" aria-label="date-to" aria-describedby="date-to">
                        </div>

                        <?php if( is_address_admin() ): ?>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="user_address"><?php esc_html_e( 'User Name', 'wpcargo-address-book' ); ?></span>
                                </div>
                                <select name="user_address" class="form-control browser-default custom-select user_address">
                                </select>
                            </div> 
                        <?php endif; ?>
                        <input id = "export-button" class="btn btn-success" type="submit" name="export-address" value="<?php esc_html_e('Export ', 'wpcargo-address-book' ); ?><?php echo ucfirst( $import ); ?><?php esc_html_e(' Address Book', 'wpcargo-address-book' ); ?>">
                    </form>
                </div>
            </div>
        </section>

        <section class = "col-md-6 md-4 two-half">
            <div id="import-section" class="one-half wpcfe-address-book">
                <div class="inside">
                    <h2><?php esc_html_e('Import Address Book', 'wpcargo-address-book' ); ?></h2>
                    <p class="description"><?php esc_html_e('Note: Please download .csv format in importing address book based on Book Type.', 'wpcargo-address-book' ); ?></p>
                    <ul id="wpcab-csv-template">
                        <li><a data-id="shipper" href="#"><?php esc_html_e('Download Shipper CSV template', 'wpcargo-address-book' ); ?></a></li>
                        <li><a data-id="receiver" href="#"><?php esc_html_e('Download Receiver CSV template', 'wpcargo-address-book' ); ?></a></li>
                    </ul>
                    <form id="import-address-book" enctype="multipart/form-data" method="post" action="<?php echo admin_url('admin.php?page=ei-address-book'); ?>">
                        <?php wp_nonce_field( 'import_address_book_action', 'import_address_field' ); ?>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Upload Book', 'wpcargo-address-book' ); ?></th>
                                <td><input id="address_file" type="file" name="address_file"></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Select Book Type to Import', 'wpcargo-address-book' ); ?></th>
                                <td>
                                    <select name="book_type" required="required">
                                        <option value=""><?php esc_html_e('Select Book', 'wpcargo-address-book' ); ?></option>
                                        <option value="receiver"><?php esc_html_e('Receiver', 'wpcargo-address-book' ); ?></option>
                                        <option value="shipper"><?php esc_html_e('Shipper', 'wpcargo-address-book' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Mac / International language settings compatibility', 'wpcargo-address-book' ); ?></th>
                                <td>
                                    <input type="checkbox" name="compatibility" value="1">
                                    <p class="description"><?php esc_html_e( 'Please this checkbox if you\'re using Mac OS or you\'re having issue importing due to international language settings setup.' , 'wpcargo-address-book'); ?></p>
                                </td>
                            </tr>
                        </table>	
                        <input class="button button-primary button-large" type="submit" name="export-address" value="<?php esc_html_e('Import Address Book', 'wpcargo-address-book' ); ?>">
                    </form>
                    <div id="import-result"></div>
                </div>
            </div>
        </section>
    </section>
</div>


<style>
    /**** IMPORT/EXPORT STYLES *****/
    div#address-import-export li {
        list-style: none;
        flex: 0 0 49%;
        text-align: center;
        background-color: #eeeeee;
        padding: 10px 10px;
        color: #333;
        border: 2px solid #fff;
        margin: 0 10px;
        box-shadow: 2px 5px 5px #cecece;
    }

    ul#address-pagination {
        display: flex;
        justify-content: center;
        padding: 0;
    }

    div#address-import-export li a {
        color: #333;
        text-transform: uppercase;
        font-weight: 500;
    }

    ul#address-pagination .active {
        background-color: #4285f4;
    }

    ul#address-pagination .active a {
        color: #fff;
    }
    section.col-md-6.md-4.one-halves {
        border-right: 1px solid #cecece;
    }
    span.select2-selection{
        height: 38px !important;
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        border: 1px solid #cecece !important;
        width: 460px;
    }
    span.select2-selection__clear {
        padding: 0 20px;
    }

    /********* CALENDAR STYLES *******/
    div#ui-datepicker-div {
        background-color: #f0f0f0;
        width: 25%;
        display: flex;
        flex-wrap: wrap;
    }

    .ui-datepicker-header.ui-widget-header.ui-helper-clearfix.ui-corner-all {
        flex: 0 0 100%;
        justify-content: space-around;
        display: flex;
        background-color: #007bff;
    }

    .ui-datepicker-header.ui-widget-header.ui-helper-clearfix.ui-corner-all a>span, .ui-datepicker-header.ui-widget-header.ui-helper-clearfix.ui-corner-all span {
        color: #fff;
        font-weight: 600;
    }

    table.ui-datepicker-calendar {
        width: 100%;
    }

    table.ui-datepicker-calendar th, table.ui-datepicker-calendar td {
        text-align: center;
        border: 1px solid #fff;
    }
</style>