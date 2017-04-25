<?php

/**
* Discogs API base class
*/

namespace WC_Discogs\API\Discogs;

use  \WC_Discogs\Admin\Settings;
use \Discogs\ClientFactory;

abstract class Resource {

	protected $client;

	public function __construct( array $config = [] ) {

		$defaultConfig = [
			'defaults' => [
				'headers' => ['User-Agent' => 'wc-record-store/1.0.0 +https://github.com/aaltomeri/wc-record-store'],
				'debug' =>
					Settings::$options && isset(Settings::$options['debug'])
						? Settings::$options['debug']
						: false,
				'query' => [
					'key' => getenv('DISCOGS_API_CONSUMER_KEY'),
					'secret' => getenv('DISCOGS_API_CONSUMER_SECRET'),
				]
			]
		];

		$config = ClientFactory::mergeRecursive(
			$defaultConfig,
			$config
		);

		$this->client = ClientFactory::factory($config);
		$this->client->getHttpClient()->getEmitter()->attach(new \Discogs\Subscriber\ThrottleSubscriber());

		$this->client->getHttpClient()->getEmitter()->on('complete', function($event) {

			$headers = $event->getResponse()->getHeaders();

			$discogsRatelimit = intval( $headers['X-Discogs-Ratelimit'][0]) ;
			$discogsRatelimitUsed = intval( $headers['X-Discogs-Ratelimit-Used'][0]) ;
			$discogsRatelimitRemaining = intval( $headers['X-Discogs-Ratelimit-Remaining'][0]) ;

			// we need to wait if we are reaching the Discogs API rate limit
			if( $discogsRatelimitRemaining <  5) {
				sleep( 60 );
			}

		});

	}

}
