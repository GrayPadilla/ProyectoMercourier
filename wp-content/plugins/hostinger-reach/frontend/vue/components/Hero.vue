<script setup lang="ts">
import { HNotificationRow } from '@hostinger/hcomponents';
import { computed } from 'vue';

import reachBackgroundImage from '@/assets/images/backgrounds/reach-welcome-background.png';
import reachLogo from '@/assets/images/icons/reach-logo.svg';
import { useReachUrls } from '@/composables/useReachUrls';
import { translate } from '@/utils/translate';

const { reachBaseDomain } = useReachUrls();

const reachDashboardUrl = computed(() => `https://${reachBaseDomain.value}`);

interface Props {
	isConnectedToAnotherSite?: boolean;
	isButtonLoading?: boolean;
	isTemporary?: boolean;
	isNotActive?: boolean;
	domain: string;
	onGetStarted: () => void;
}

const props = withDefaults(defineProps<Props>(), {
	isConnectedToAnotherSite: false,
	isButtonLoading: false,
	isNotActive: false
});
</script>

<template>
	<section class="hero" role="main" aria-labelledby="hero-heading">
		<header class="hero__header">
			<div class="hero__header-content">
				<div class="hero__header-brand">
					<img :src="reachLogo" :alt="translate('hostinger_reach_header_logo_alt')" class="hero__header-logo" />
				</div>
				<HButton
					variant="outline"
					target="_blank"
					color="neutral"
					size="small"
					icon-append="ic-launch-16"
					:to="reachDashboardUrl"
				>
					{{ translate('hostinger_reach_header_go_to_reach_button') }}
				</HButton>
			</div>
		</header>

		<HCard border-radius="20px" padding="0" border-color="neutral--200">
			<div class="hero__image">
				<img
					:src="reachBackgroundImage"
					:alt="translate('hostinger_reach_hero_background_alt')"
					class="hero__background"
					role="img"
				/>
			</div>
			<div
				style="
					background-image: url('/wp-content/plugins/hostinger-reach/frontend/dist/assets/reach-welcome-background.png');
				"
				class="hero__content"
			>
				<div class="hero__content-wrapper">
					<HText id="hero-heading" as="h1" variant="heading-1">
						{{ translate('hostinger_reach_welcome_view_title') }}
					</HText>
					<HText id="hero-description" as="p" variant="body-2 h-mb-24">
						{{ translate('hostinger_reach_welcome_view_description') }}
					</HText>
					<HButton
						v-if="!isConnectedToAnotherSite && !isTemporary && !isNotActive"
						color="primary"
						size="small"
						:is-loading="props.isButtonLoading"
						aria-describedby="hero-description"
						aria-label="Get started with email marketing"
						@click="onGetStarted"
					>
						{{ translate('hostinger_reach_welcome_view_start_button') }}
					</HButton>
				</div>
			</div>
			<div v-if="!isButtonLoading && (isTemporary || isNotActive)" class="hero__info">
				<HNotificationRow
					variant="warning"
					:description="
						isTemporary
							? translate('hostinger_reach_welcome_view_description_temporary')
							: translate('hostinger_reach_welcome_view_description_not_active')
					"
					:primary-action-text="
						isTemporary
							? translate('hostinger_reach_welcome_view_temporary_button')
							: translate('hostinger_reach_welcome_view_not_active_button')
					"
					@primary-action-click="onGetStarted"
				/>
			</div>
			<div class="hero__footer" role="contentinfo" aria-label="Site information">
				<HIcon class="h-mr-8" name="ic-globe-16" color="neutral--300" aria-hidden="true" />
				<HText as="p" variant="body-2-bold" color="neutral--500">
					{{ domain }}
				</HText>
			</div>
		</HCard>
	</section>
</template>

<style scoped lang="scss">
.hero {
	display: flex;
	align-items: center;
	flex-direction: column;
	max-width: 780px;
	margin: 40px auto 16px auto;

	@media (max-width: 1024px) {
		max-width: 90%;
		margin: 32px auto 16px auto;
	}

	@media (max-width: 768px) {
		flex-direction: column;
		text-align: left;
		padding: 24px 16px;
		gap: 32px;
		margin: 20px auto 16px auto;
	}

	@media (max-width: 480px) {
		padding: 16px 8px;
		gap: 24px;
		margin: 16px auto 12px auto;
	}

	&__banner {
		display: flex;
		width: 100%;
		align-items: center;
		justify-content: space-between;
		gap: 16px;

		@media (max-width: 768px) {
			flex-direction: column;
			align-items: flex-start;
			gap: 12px;
		}

		@media (max-width: 480px) {
			gap: 8px;
		}
	}

	&__banner-content {
		display: flex;
		flex-direction: column;
		gap: 4px;

		@media (max-width: 768px) {
			width: 100%;
		}
	}

	&__content {
		flex: 1;
		padding: 28px;
		width: 100%;
		border-radius: 20px;
		background-size: cover;
		background-position: right;
		background-repeat: no-repeat;

		@media (max-width: 768px) {
			padding: 20px;
			background: none;
			border-radius: 0;
		}
	}

	&__image {
		width: 100%;
		display: flex;
		justify-content: center;
		position: relative;
		overflow: hidden;
		margin-bottom: 20px;
		border-bottom: 1px solid var(--neutral--50);

		@media (min-width: 768px) {
			display: none;
		}
	}

	&__background {
		width: 100%;
		height: auto;
		border-top-right-radius: 20px;
		border-top-left-radius: 20px;
		border-bottom-right-radius: 0;
		border-bottom-left-radius: 0;
	}

	&__content-wrapper {
		display: flex;
		flex-direction: column;
		gap: 10px;

		@media (min-width: 768px) {
			width: 50%;
		}
	}

	&__info {
		padding: 24px;
		border-top: 1px solid var(--neutral--50);
	}

	&__footer {
		width: 100%;
		display: flex;
		align-items: center;
		padding: 16px 24px 20px 24px;
		border-bottom-left-radius: 20px;
		background: var(--neutral--50);
		border-bottom-right-radius: 20px;

		@media (max-width: 768px) {
			padding: 16px;
		}
	}

	&__header {
		width: 100%;
		margin-bottom: 20px;

		@media (max-width: 768px) {
			padding: 0 12px;
			margin-bottom: 16px;
		}

		@media (max-width: 480px) {
			padding: 0 8px;
			margin-bottom: 12px;
		}
	}

	&__warning-snackbar {
		display: flex;
		align-items: center;
	}

	&__header-content {
		display: flex;
		justify-content: space-between;
		align-items: center;
		width: 100%;

		@media (max-width: 768px) {
			flex-direction: column;
			gap: 16px;
			align-items: flex-start;
		}

		@media (max-width: 480px) {
			gap: 12px;
		}

		:deep(.h-button-v2) {
			@media (max-width: 768px) {
				width: 100%;
			}
		}
	}

	&__header-brand {
		display: flex;
		align-items: center;
		gap: 12px;

		@media (max-width: 480px) {
			gap: 8px;
		}
	}

	&__header-logo {
		height: 28px;
		width: auto;

		@media (max-width: 768px) {
			height: 24px;
		}

		@media (max-width: 480px) {
			height: 20px;
		}
	}

	&__header-title {
		margin-bottom: 12px;
		color: var(--neutral--700);
	}

	&__warning-snackbar {
		margin-bottom: 24px;
	}
}

:deep(.hero .h-card) {
	background: var(--neutral--0);
	display: flex;
	align-items: center;
	flex-direction: column;
	padding: 0;
	width: 100%;
}

@media (max-width: 320px) {
	.hero {
		padding: 12px 4px;
		gap: 20px;

		&__content {
			padding: 12px 12px 0px 12px;
		}
	}
}
</style>
