<?php
$shipment_number = wpcargo_can_track_shipment();
$result_page_id  = (int) sanitize_text_field( $atts['id'] );
$get_action      = ! empty( $result_page_id ) ? get_page_link( $result_page_id ) : '';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
	/* ─── Reset & Container ─────────────────────── */
	.wpcargo-track-wrapper * {
		box-sizing: border-box;
		margin: 0;
		padding: 0;
	}

	.wpcargo-track-wrapper {
		font-family: 'DM Sans', sans-serif;
		display: flex;
		justify-content: center;
		align-items: center;
		padding: 3rem 1rem;
	}

	/* ─── Card ──────────────────────────────────── */
	.wpcargo-card {
		background: #0f1b2d;
		border: 1px solid rgba(255,255,255,0.07);
		border-radius: 20px;
		padding: 3rem 3.5rem;
		width: 100%;
		max-width: 620px;
		position: relative;
		overflow: hidden;
		box-shadow:
			0 4px 24px rgba(0,0,0,0.35),
			0 1px 0 rgba(255,255,255,0.04) inset;
	}

	/* Subtle top-right glow accent */
	.wpcargo-card::before {
		content: '';
		position: absolute;
		top: -80px;
		right: -80px;
		width: 280px;
		height: 280px;
		background: radial-gradient(circle, rgba(220,38,38,0.18) 0%, transparent 70%);
		pointer-events: none;
	}

	/* ─── Header ────────────────────────────────── */
	.wpcargo-header {
		margin-bottom: 2.25rem;
	}

	.wpcargo-eyebrow {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 0.75rem;
	}

	.wpcargo-eyebrow-dot {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		background: #dc2626;
		box-shadow: 0 0 8px #dc2626;
		animation: pulse-dot 2.4s ease-in-out infinite;
	}

	@keyframes pulse-dot {
		0%, 100% { opacity: 1; transform: scale(1); }
		50%       { opacity: 0.5; transform: scale(0.7); }
	}

	.wpcargo-eyebrow-text {
		font-size: 0.72rem;
		font-weight: 600;
		letter-spacing: 0.14em;
		text-transform: uppercase;
		color: #dc2626;
	}

	.wpcargo-title {
		font-size: 1.65rem;
		font-weight: 600;
		color: #dc2626 !important;
		letter-spacing: -0.02em;
		line-height: 1.25;
	}

	.wpcargo-subtitle {
		margin-top: 0.45rem;
		font-size: 0.9rem;
		color: #64748b;
		font-weight: 400;
	}

	/* ─── Divider ───────────────────────────────── */
	.wpcargo-divider {
		height: 1px;
		background: linear-gradient(to right, rgba(255,255,255,0.06), transparent);
		margin-bottom: 2rem;
	}

	/* ─── Form ──────────────────────────────────── */
	.wpcargo-form-body {
		display: flex;
		flex-direction: column;
		gap: 1rem;
	}

	.wpcargo-field-label {
		display: block;
		font-size: 0.78rem;
		font-weight: 500;
		letter-spacing: 0.06em;
		text-transform: uppercase;
		color: #94a3b8;
		margin-bottom: 0.5rem;
	}

	.wpcargo-input-wrap {
		position: relative;
	}

	/* Icon */
	.wpcargo-input {
		width: 100%;
		padding: 15px 16px;
		background: #111f35;
		border: 1px solid rgba(255,255,255,0.08);
		border-radius: 12px;
		color: #f0f4f8;
		font-family: 'DM Mono', monospace;
		font-size: 0.95rem;
		letter-spacing: 0.05em;
		outline: none;
		transition:
			border-color 0.2s ease,
			background 0.2s ease,
			box-shadow 0.2s ease;
	}

	.wpcargo-input::placeholder {
		color: #2d4059;
		font-family: 'DM Sans', sans-serif;
		letter-spacing: 0;
	}

	.wpcargo-input:focus {
		border-color: #dc2626;
		background: #0d1929;
		box-shadow: 0 0 0 3px rgba(220,38,38,0.12);
	}

	/* ─── Example badge ─────────────────────────── */
	.wpcargo-example {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-size: 0.78rem;
		color: #475569;
		margin-top: 0.6rem;
	}

	.wpcargo-example code {
		font-family: 'DM Mono', monospace;
		font-size: 0.76rem;
		background: rgba(220,38,38,0.08);
		color: #f87171;
		padding: 2px 7px;
		border-radius: 5px;
		border: 1px solid rgba(220,38,38,0.15);
	}

	/* ─── Submit ─────────────────────────────────── */
	.wpcargo-submit-btn {
		width: 100%;
		padding: 15px;
		margin-top: 0.5rem;
		background: #dc2626;
		color: #fff;
		font-family: 'DM Sans', sans-serif;
		font-size: 0.9rem;
		font-weight: 600;
		letter-spacing: 0.06em;
		text-transform: uppercase;
		border: none;
		border-radius: 12px;
		cursor: pointer;
		position: relative;
		overflow: hidden;
		transition:
			background 0.2s ease,
			transform 0.15s ease,
			box-shadow 0.2s ease;
		box-shadow: 0 4px 16px rgba(220,38,38,0.35);
	}

	.wpcargo-submit-btn::after {
		content: '';
		position: absolute;
		inset: 0;
		background: linear-gradient(to bottom, rgba(255,255,255,0.06), transparent);
		border-radius: inherit;
		pointer-events: none;
	}

	.wpcargo-submit-btn:hover {
		background: #b91c1c;
		transform: translateY(-1px);
		box-shadow: 0 6px 22px rgba(220,38,38,0.45);
	}

	.wpcargo-submit-btn:active {
		transform: translateY(0);
		box-shadow: 0 2px 10px rgba(220,38,38,0.3);
	}

	/* ─── Footer note ────────────────────────────── */
	.wpcargo-footer-note {
		margin-top: 1.75rem;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 6px;
		font-size: 0.78rem;
		color: #334155;
	}

	.wpcargo-footer-note svg {
		flex-shrink: 0;
	}

	/* ─── Responsive ─────────────────────────────── */
	@media (max-width: 520px) {
		.wpcargo-card {
			padding: 2rem 1.5rem;
			border-radius: 16px;
		}
		.wpcargo-title {
			font-size: 1.35rem;
		}
	}
</style>

<div class="wpcargo-track-wrapper wpcargo">
	<div class="wpcargo-card">

		<div class="wpcargo-header">
			<div class="wpcargo-eyebrow">
				<span class="wpcargo-eyebrow-dot"></span>
				<span class="wpcargo-eyebrow-text"><?php echo apply_filters('wpcargo_tn_eyebrow', esc_html__('Shipment Tracking', 'wpcargo')); ?></span>
			</div>
			<h2 class="wpcargo-title"><?php echo apply_filters('wpcargo_tn_form_title', esc_html__('Rastrear mi envío', 'wpcargo')); ?></h2>
			<p class="wpcargo-subtitle"><?php esc_html_e('Ingresa tu número de consignación para ver el estado de tu paquete en tiempo real.', 'wpcargo'); ?></p>
		</div>

		<div class="wpcargo-divider"></div>

		<form method="post" name="wpcargo-track-form" action="<?php echo esc_url($get_action); ?>">
			<?php wp_nonce_field('wpcargo_track_shipment_action', 'track_shipment_nonce'); ?>

			<div class="wpcargo-form-body">
				<?php do_action('wpcargo_add_form_fields'); ?>

				<div>
					<label class="wpcargo-field-label" for="wpcargo-tracking-input">
						<?php esc_html_e('Número de seguimiento', 'wpcargo'); ?>
					</label>

					<div class="wpcargo-input-wrap">

						<input
							id="wpcargo-tracking-input"
							class="wpcargo-input input_track_num"
							type="text"
							name="<?php echo wpcargo_track_meta(); ?>"
							value="<?php echo esc_attr($shipment_number); ?>"
							autocomplete="off"
							spellcheck="false"
							placeholder="<?php echo apply_filters('wpcargo_tn_placeholder', esc_attr__('Ej: MERC-00123', 'wpcargo')); ?>"
							required
						>
					</div>

					<?php echo apply_filters('wpcargo_example_text',
						'<p class="wpcargo-example">'
						. '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
						. esc_html__('Formato de ejemplo:', 'wpcargo') . ' <code>MERC-00123</code>'
						. '</p>'
					); ?>
				</div>

				<input
					id="submit_wpcargo"
					class="wpcargo-submit-btn"
					name="wpcargo-submit"
					type="submit"
					value="<?php echo apply_filters('wpcargo_tn_submit_val', esc_attr__('RASTREAR ENVÍO', 'wpcargo')); ?>"
				>
			</div>
		</form>

		<div class="wpcargo-footer-note">
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
			<?php esc_html_e('Conexión segura · Datos cifrados', 'wpcargo'); ?>
		</div>

	</div>
</div>