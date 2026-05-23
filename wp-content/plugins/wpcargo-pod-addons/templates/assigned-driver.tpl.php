<div id="assigned-driver-wrapper" class="col-md-12 mb-4">
	<div class="card">
		<?php do_action( 'wpc_pod_before_driver_default_section', $shipment_id ); ?>
		<section class="card-header">
			<?php echo apply_filters( 'pod_proof_delivery_label', __('Proof of Delivery', 'wpcargo-pod' ) ); ?>
		</section>
		<section class="card-body">
			<div class="row">
				<section id="pod-signature-wrapper" class="col-md-6">
					<p class="h6 text-center"><?php echo apply_filters( 'pod_signature_label', __('Signature', 'wpcargo-pod' ) ); ?></p>
					<div id="pod-signature">
						<img src="<?php echo wp_get_attachment_url( $signature ); ?>" class="signature-generated-img" />
					</div>
				</section>
				<section id="pod-images-wrapper" class="col-md-6">
					<p class="h6 text-center" ><?php echo apply_filters( 'pod_images_label', __('Images', 'wpcargo-pod' ) ); ?></p>
					<?php if (!empty($images)): ?>
					<div class="container">	
						<div id="pod-images" class="row">
								<?php
								$images = explode(',', $images);
								foreach ($images as $image):
									if(!is_numeric($image)) {
										continue;
									}
									$title = get_the_title($image) ? get_the_title($image) : basename ( get_attached_file( $image ) );
									?>
									<section class="col-md-4">
										<a href="<?php echo wp_get_attachment_url($image); ?>"><img width="250" src="<?php echo wp_get_attachment_url( $image ); ?>" alt="<?php  echo $title; ?>" /></a>
									</section>
									<?php
								endforeach;
								?>
						</div>
					</div>
					<?php endif; ?>
				</section>
			</div>
		</section>
		<?php do_action( 'wpc_pod_after_driver_default_section', $shipment_id ); ?>
	</div>
</div>