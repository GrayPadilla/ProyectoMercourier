<?php
/**
 * Plantilla personalizada para la selección del tipo de servicio antes de crear un envío.
 * Evita cargar el formulario pesado de WPCargo en segundo plano hasta que se seleccione el servicio.
 */
?>
<div class="merc-service-selector-container">
    <div class="merc-service-selector-inner animate-popup">
        <div class="header-action mb-4 d-flex justify-content-between align-items-center">
            <h2 class="m-0" style="color: #1976D2; font-weight: 600;">Selecciona el tipo de envío</h2>
            <button class="btn btn-outline-secondary" onclick="history.back()">
                <i class="fa fa-arrow-left"></i> Volver
            </button>
        </div>
        
        <div class="row justify-content-center">
            <!-- Opción 1: MERC EMPRENDEDOR -->
            <div class="col-md-3 text-center mx-3 option-box-shipment"
                style="cursor: pointer;"
                onclick="selectShipmentType('normal')"
            >
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/envio-normal.png" alt="Normal" class="img-fluid mb-3">
                <h4>MERC EMPRENDEDOR</h4>
                <p>Usa este modo para registrar un envío estándar.</p>
            </div>
            
            <!-- Opción 2: MERC AGENCIA -->
            <div class="col-md-3 text-center mx-3 option-box-shipment"
                style="cursor: pointer;"
                onclick="selectShipmentType('express')"
            >
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/envio-express.png" alt="Express" class="img-fluid mb-3">
                <h4>MERC AGENCIA</h4>
                <p>Ideal para entregas urgentes o de prioridad alta.</p>
            </div>
            
            <!-- Opción 3: MERC FULL FITMENT -->
            <div class="col-md-3 text-center mx-3 option-box-shipment"
                style="cursor: pointer;"
                onclick="selectShipmentType('full_fitment')"
            >
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/envio-express.png" alt="Full Fitment" class="img-fluid mb-3">
                <h4>MERC FULL FITMENT</h4>
                <p>Envío con producto del almacén asignado.</p>
            </div>
            
            <!-- Opción 4: MERC EXPRESS (WhatsApp) -->
            <div class="col-md-3 text-center mx-3 option-box-shipment"
                style="cursor: pointer;"
                onclick="window.open('https://wa.me/51931430389', '_blank')">
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/whatsapp.png" alt="WhatsApp" class="img-fluid mb-3">
                <h4>MERC EXPRESS</h4>
                <p>Consulta o solicita ayuda directa por chat.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .merc-service-selector-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 70vh;
        padding: 40px 15px;
        background: #f8f9fa;
    }
    .merc-service-selector-inner {
        background: rgba(255, 255, 255, 0.98);
        border-radius: 20px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 10px 50px rgba(0, 0, 0, 0.1);
        max-width: 1000px;
        width: 100%;
        padding: 40px 30px;
        text-align: center;
        color: #000;
    }
    .animate-popup { animation: popupIn 0.4s ease forwards; }
    @keyframes popupIn {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .merc-service-selector-inner .row {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
        margin: 0 -15px;
    }
    .option-box-shipment {
        background: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 16px;
        padding: 25px 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        color: #000;
        flex: 1 1 220px;
        max-width: 280px;
        min-width: 200px;
    }
    .option-box-shipment:hover {
        transform: translateY(-5px);
        background: #fff;
        border-color: #2196F3;
        box-shadow: 0 8px 25px rgba(33,150,243,0.15);
    }
    .option-box-shipment img { max-height: 120px; width: auto; height: auto; margin-bottom: 15px; }
    .option-box-shipment h4 { color: #1976D2; font-weight: 600; margin-bottom: 10px; font-size: 18px; }
    .option-box-shipment p  { color: #666; font-size: 14px; line-height: 1.4; margin-bottom: 0; }
    @media (max-width: 768px) {
        .option-box-shipment { flex: 1 1 calc(50% - 20px); max-width: none; padding: 20px 10px; }
        .option-box-shipment img { max-height: 80px; }
        .option-box-shipment h4  { font-size: 16px; }
    }
    @media (max-width: 576px) {
        .merc-service-selector-container { padding: 15px 5px; }
        .merc-service-selector-inner  { padding: 25px 15px; border-radius: 12px; }
        .option-box-shipment { flex: 1 1 100%; max-width: none; margin: 0 !important; }
        .option-box-shipment img { max-height: 70px; margin-bottom: 10px; }
    }
</style>

<script>
    function selectShipmentType(type) {
        const url = new URL(window.location.href);
        url.searchParams.set('type', type);
        window.location.href = url.toString();
    }
</script>


