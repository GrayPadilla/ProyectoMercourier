<?php
	$get_pod_pictures = get_post_meta($shipment_detail->ID, 'wpcargo-pod-image', true);
	$get_pod_signature = get_post_meta($shipment_detail->ID, 'wpcargo-pod-signature', true);						
?>
<div id="pod-info" class="wpcargo-row detail-section my-2">
    <div class="wpcargo-col-md-12">
        <?php if( $get_pod_pictures || $get_pod_signature ): ?>
        <div class="wpcargo-pod-title">
            <p class="pod-title header-title"><strong><?php esc_html_e('Proof of Delivery', 'wpcargo-pod' ); ?></strong></p>
        </div>
        <?php endif; ?>
        <div class="wpcargo-pod-details">
            <div class="wpcargo-pod-signature">
                <?php if( $get_pod_signature ): ?>
                    <p><?php esc_html_e('POD Signature', 'wpcargo-pod' ); ?></p>
                    <img src="<?php echo wp_get_attachment_url( $get_pod_signature ); ?>" />
                <?php endif; ?>
            </div>
            <div class="wpcargo-pod-image">
                <?php if( $get_pod_pictures ): ?>
                    <p><?php esc_html_e('POD Images', 'wpcargo-pod' ); ?></p>
                    <div id="wpcargo-pod-images">
                        <?php
                        if (!empty($get_pod_pictures) || $get_pod_pictures != NULL):
                            $get_images_id = explode(',', $get_pod_pictures);
                            foreach ($get_images_id as $image_id):
                                if(!is_numeric($image_id)) {
                                    continue;
                                }
                                $title = get_the_title($image_id) ? get_the_title($image_id) : basename ( get_attached_file( $image_id ) );
                                ?>
                                <div class="gallery-thumb image" data-id="<?php echo $image_id; ?>">
                                    <div class="single-img">
                                        <a href="<?php echo wp_get_attachment_url($image_id); ?>">
                                            <img style = "width:100% !important;" src="<?php echo wp_get_attachment_url( $image_id ); ?>" alt="<?php echo $title; ?>" />
                                        </a>
                                    </div>
                                </div>
                                <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>