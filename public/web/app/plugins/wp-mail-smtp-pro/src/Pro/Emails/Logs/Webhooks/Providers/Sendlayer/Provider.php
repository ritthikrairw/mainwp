<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Sendlayer;

use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractProvider;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractSubscriber;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractProcessor;

/**
 * Class Provider.
 *
 * @since 3.4.0
 */
class Provider extends AbstractProvider {

	/**
	 * Get the webhook processor.
	 *
	 * @since 3.4.0
	 *
	 * @return AbstractProcessor
	 */
	public function get_processor() {

		if ( is_null( $this->processor ) ) {
			$this->processor = new Processor( $this );
		}

		return $this->processor;
	}

	/**
	 * Get the webhook subscription manager.
	 *
	 * @since 3.4.0
	 *
	 * @return AbstractSubscriber
	 */
	public function get_subscriber() {

		if ( is_null( $this->subscriber ) ) {
			$this->subscriber = new Subscriber( $this );
		}

		return $this->subscriber;
	}
}
