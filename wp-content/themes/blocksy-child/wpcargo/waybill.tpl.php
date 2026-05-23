<?php
$shipment_id = $shipmentDetails['shipmentID'];

if (!function_exists('shorten_url')) {
    function shorten_url($long_url) {
        $api_url = 'https://is.gd/create.php?format=simple&url=' . urlencode($long_url);
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $short_url = curl_exec($ch);
        curl_close($ch);
        return ($short_url && strpos($short_url, 'Error') === false) ? trim($short_url) : $long_url;
    }
}

if (!function_exists('get_qr_code_url')) {
    function get_qr_code_url($data) {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($data);
    }
}

if (!function_exists('get_maps_url')) {
    function get_maps_url($shipment_id) {
        $link_maps = trim(get_post_meta($shipment_id, 'link_maps', true));
        if (!empty($link_maps)) return $link_maps;

        $address = trim(get_post_meta($shipment_id, 'wpcargo_receiver_address', true));
        if (!empty($address)) {
            $long_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
            return shorten_url($long_url);
        }
        return '';
    }
}
?>

<style>
@page {
    size: 80mm 100mm;
    margin: 0;
}

@media print {
    html, body {
        width: 80mm;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden;
    }
}

* { box-sizing: border-box; }

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 9.5px;
}

/* CONTENEDOR */
.thermal-label {
    width: 76mm;        /* margen simétrico */
    margin: 0 auto;    /* centra izquierda / derecha */
    background: #fff;
    position: relative;
    overflow: hidden;
}

/* MARCA DE AGUA */
.watermark-logo {
    position: absolute;
    top: 60%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0.09;
    pointer-events: none;
}

.watermark-logo img {
    width: 250px;
}

/* CONTENIDO */
.label-content { position: relative; }

.label-header {
    display: flex;
    flex-direction: column;   /* ← clave */
    align-items: center;
    justify-content: center;
    padding: 6px 3px 4px;
    border-bottom: 2px solid #000;
    text-align: center;
}
.logo-wrap {
    width: 100%;
    height: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 3px;
    text-align: center;
}

.logo-wrap {
    width: 100%;
    display: block !important;
    text-align: center !important;
    margin-bottom: 4px;
}

.logo-wrap img {
    display: inline-block !important;
    max-height: 36px !important;
    width: auto !important;
    height: auto !important;
    margin: 0 auto !important;
    float: none !important;
}

.company-info {
    width: 100%;
    text-align: center;
}

/* NOMBRE EMPRESA */
.brand-name {
    font-size: 9px;
    font-weight: bold;
    text-transform: uppercase;
    line-height: 1.1;
}

/* DATOS EMPRESA */
.company-data {
    font-size: 7.5px;
    line-height: 1.1;
    word-break: normal;       /* no romper palabras */
    overflow-wrap: break-word;
    white-space: normal;
}


/* TRACKING */
.tracking-section {
    text-align: center;
    padding: 6px;
    border-bottom: 2px solid #000;
}

.barcode-container img {
    max-width: 100%;
    max-height: 30px;
}

.tracking-number {
    font-size: 12px;
    font-weight: bold;
}

/* DESTINO */
.destination-section {
    background: transparent;
    color: #000;
    text-align: center;
    padding: 5px;
    border-bottom: 2px solid #000;
}

.destination-value {
    font-size: 14px;
    font-weight: bold;
}

/* DATOS */
.receiver-section {
    padding: 6px;
    border-bottom: 2px solid #000;
}

.field-label {
    font-size: 7px;
    font-weight: bold;
    text-transform: uppercase;
}

.field-value {
    font-size: 9.5px;
    font-weight: bold;
}

/* QR */
.qr-section {
    text-align: center;
    padding: 6px;
}

.qr-code {
    width: 85px;
    height: 85px;
}

.qr-label {
    font-size: 7px;
    line-height: 1.2;
    margin-top: 2px;   /* espacio seguro, no rompe página */
}
</style>

<div class="thermal-label">

    <div class="watermark-logo">
        <img src="https://mercourier.com/wp-content/uploads/2025/09/Logo-MERC.png" alt="Mercourier">
    </div>

    <div class="label-content">
        
        <div class="label-header">
            <div class="logo-wrap">
                <?php echo $shipmentDetails['logo']; ?>
            </div>
        
            <div class="company-info">
                <div class="brand-name">
                    <?php echo get_post_meta($shipment_id, 'wpcargo_tiendaname', true); ?>
                </div>
            </div>
        </div>

        <div class="tracking-section">
            <div class="barcode-container">
                <img src="<?php echo $shipmentDetails['barcode']; ?>" alt="Barcode">
            </div>
            <div class="tracking-number">
                <?php echo get_the_title($shipment_id); ?>
            </div>
        </div>

        <div class="destination-section">
            <div class="destination-value">
                <?php echo strtoupper(get_post_meta($shipment_id, 'wpcargo_distrito_destino', true)); ?>
            </div>
        </div>

        <div class="receiver-section">
            <div>
                <span class="field-label">Destinatario</span><br>
                <span class="field-value">
                    <?php echo get_post_meta($shipment_id, 'wpcargo_receiver_name', true); ?>
                </span>
            </div>
            <div>
                <span class="field-label">Teléfono</span><br>
                <span class="field-value">
                    <?php echo get_post_meta($shipment_id, 'wpcargo_receiver_phone', true); ?>
                </span>
            </div>
            <div>
                <span class="field-label">Dirección</span><br>
                <span class="field-value">
                    <?php echo get_post_meta($shipment_id, 'wpcargo_receiver_address', true); ?>
                </span>
            </div>
        </div>

        <?php
        $maps_url = get_maps_url($shipment_id);
        if (!empty($maps_url)):
            $qr_url = get_qr_code_url($maps_url);
        ?>
        <div class="qr-section">
            <img class="qr-code" src="<?php echo esc_url($qr_url); ?>" alt="QR">
            <div class="qr-label">Delivery autorizado por MERCourier. Visítanos: www.mercourier.com</div>
        </div>
        <?php endif; ?>

    </div>
</div>
