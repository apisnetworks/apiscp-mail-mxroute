<?php declare(strict_types=1);

	/**
	 * Copyright (C) Apis Networks, Inc - All Rights Reserved.
	 *
	 * MIT License
	 *
	 * Written by Matt Saladna <matt@apisnetworks.com>, May 2020
	 */

	namespace Opcenter\Mail\Providers\Mxroute;

	// stub to make apnscp happy
	use GuzzleHttp\Client;

	class Module extends \Opcenter\Mail\Providers\Null\Module
	{
		protected const DKIM_RECORD = 'x._domainkey';
		/**
		 * Get DNS records
		 *
		 * @param string $domain
		 * @param string $subdomain
		 * @return array
		 */
		public function provisioning_records(string $domain, string $subdomain = ''): array
		{
			$ttl = $this->dns_get_default('ttl');

			$server = $this->getServiceValue('mail', 'key', MAIL_PROVIDER_KEY);

			if (!$server) {
				fatal('MXRoute server unset?');
			}

			$meta = (new Api())->getMetadata($server);

			$records = [
				new \Opcenter\Dns\Record($domain, [
					'name'      => $subdomain,
					'ttl'       => $ttl,
					'rr'        => 'TXT',
					'parameter' => (new Api())->getSPF()
				]),
				new \Opcenter\Dns\Record($domain,
					['name' => $subdomain, 'ttl' => $ttl, 'rr' => 'MX', 'parameter' => "10 " . $meta['hostname']]),
				new \Opcenter\Dns\Record($domain,
					['name' => $subdomain, 'ttl' => $ttl, 'rr' => 'MX', 'parameter' => "20 " . $meta['backuphost']]),
				new \Opcenter\Dns\Record($domain, [
					'name' => rtrim("mail.${subdomain}",'.'),
					'ttl' => $ttl,
					'rr' => 'CNAME',
					'parameter' => $meta['hostname'] . "."
				]),
				new \Opcenter\Dns\Record($domain, [
					'name'      => rtrim("webmail.${subdomain}", '.'),
					'ttl'       => $ttl,
					'rr'        => 'CNAME',
					'parameter' => $meta['hostname'] . "."
				]),
			];

			$hostname = self::DKIM_RECORD . '.' . ltrim("${subdomain}.${domain}", '.');
			$parameter = silence(static function () use ($hostname) {
				return (new \Net_Gethost(5000))->lookup($hostname, DNS_TXT);
			});
			if ($parameter) {
				$records[] = new \Opcenter\Dns\Record($domain, [
					'name'      => rtrim(self::DKIM_RECORD . ".${subdomain}", '.'),
					'ttl'       => $ttl,
					'rr'        => 'TXT',
					'parameter' => $parameter
				]);
			}
			return $records;
		}

	}