<?php declare(strict_types=1);
/*
 * Copyright (C) Apis Networks, Inc - All Rights Reserved.
 *
 * Unauthorized copying of this file, via any medium, is
 * strictly prohibited without consent. Any dissemination of
 * material herein is prohibited.
 *
 * For licensing inquiries email <licensing@apisnetworks.com>
 *
 * Written by Matt Saladna <matt@apisnetworks.com>, October 2020
 */


namespace Opcenter\Mail\Providers\Mxroute;

use GuzzleHttp\Client;

class Api
{

	public function getCanonicalName(string $nickname): ?string
	{
		return array_get((array)$this->getMetadata($nickname), 'hostname');
	}

	/**
	 * Get metadata from server
	 *
	 * @param string $nickname
	 * @return array|null
	 */
	public function getMetadata(string $nickname): ?array
	{
		static $cache = [];
		if (isset($cache[$nickname])) {
			return $cache[$nickname];
		}
		return $cache[$nickname ] = collect($this->request('/project/items/mailservers'))->first(static function ($v) use ($nickname) {
			return $v['servername'] === $nickname;
		});
	}

	public function getSPF(): string
	{
		return array_get($this->request('/project/items/spf'), '0.txt');
	}

	private function request(string $uri): array
	{
		return array_get((array)json_decode((new Client(['base_uri' => 'https://api.mxrouteapps.com']))->request('GET', $uri, [
			'headers' => [
				'User-Agent' => PANEL_BRAND . ' ' . APNSCP_VERSION,
				'Accept'     => 'application/json',
			],
		])->getBody()->getContents(), true), 'data', []);
	}
}