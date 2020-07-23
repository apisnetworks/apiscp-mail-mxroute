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

			// post-crossbox change in URL layout
			$mxserver = silence(static function () use ($server) {
				return (new \Net_Gethost(5000))->lookup("${server}.mxrouting.net", DNS_TXT);
			}) ? "mxrouting.net" : "mxlogin.com";

			$records = [
				new \Opcenter\Dns\Record($domain, [
					'name'      => $subdomain,
					'ttl'       => $ttl,
					'rr'        => 'TXT',
					'parameter' => 'v=spf1 include:mxlogin.com -all'
				]),
				new \Opcenter\Dns\Record($domain,
					['name' => $subdomain, 'ttl' => $ttl, 'rr' => 'MX', 'parameter' => "10 ${server}.${mxserver}."]),
				new \Opcenter\Dns\Record($domain,
					['name' => $subdomain, 'ttl' => $ttl, 'rr' => 'MX', 'parameter' => "20 {$server}-relay.${mxserver}."]),
				new \Opcenter\Dns\Record($domain,
					['name' => rtrim("mail.${subdomain}",'.'), 'ttl' => $ttl, 'rr' => 'CNAME', 'parameter' => "${server}.${mxserver}."]),
				new \Opcenter\Dns\Record($domain, [
					'name'      => rtrim("webmail.${subdomain}", '.'),
					'ttl'       => $ttl,
					'rr'        => 'CNAME',
					'parameter' => "${server}.${mxserver}."
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