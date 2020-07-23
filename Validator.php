<?php
	declare(strict_types=1);

	/**
	 * Copyright (C) Apis Networks, Inc - All Rights Reserved.
	 *
	 * MIT License
	 *
	 * Written by Matt Saladna <matt@apisnetworks.com>, May 2020
	 */

	namespace Opcenter\Mail\Providers\Mxroute;

	use Opcenter\Mail\Contracts\ServiceProvider;
	use Opcenter\Service\ConfigurationContext;

	class Validator implements ServiceProvider
	{
		/**
		 * Validate service value
		 *
		 * @param ConfigurationContext $ctx
		 * @param                      $var service value
		 * @return bool
		 */
		public function valid(ConfigurationContext $ctx, &$var): bool
		{
			if (\is_array($var)) {
				return error('key must be scalar');
			}

			if (!$var) {
				return error('MXRoute provider requires a key, which is server name');
			}

			$ip = \Net_Gethost::gethostbyname_t("${var}.mxrouting.net", 5000);
			if (!$ip) {
				return error("IP lookup failed for ${var}.mxrouting.net failed - is the server name '$var' correct?");
			}
			return true;
		}
	}
